<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Staff Import",
    description: "API endpoints for bulk staff import via CSV"
)]
class StaffImportController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Post(
        path: "/api/v1/staff/import",
        summary: "Bulk import staff from CSV",
        description: "Import multiple staff records from CSV file. Creates entries in validation_staff and staff_personal_info tables. Email format: firstname.lastname@ghs.gov.gh",
        tags: ["Staff Import"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "CSV file with staff data"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Staff imported successfully"),
            new OA\Response(response: 400, description: "Invalid CSV file or data"),
            new OA\Response(response: 403, description: "Forbidden - Admin only")
        ]
    )]
    public function importStaff(Request $request, Response $response): Response
    {
        // Disable PHP error display to prevent HTML errors in JSON response
        ini_set('display_errors', '0');
        // Increase time limit for large imports
        set_time_limit(300);
        
        // Log that we received the request
        error_log("=== IMPORT STAFF REQUEST RECEIVED ===");
        error_log("Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'not set'));
        error_log("Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set'));
        error_log("Headers already sent: " . (headers_sent() ? 'YES' : 'NO'));
        
        // Send CORS headers immediately (only if not already sent)
        if (!headers_sent()) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:3000';
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-API-KEY, X-Api-Key");
            header("Access-Control-Allow-Credentials: true");
            error_log("CORS headers sent");
        } else {
            error_log("Headers already sent, cannot add CORS headers");
        }
        
        try {
            $user = $request->getAttribute('user');
            $userRole = $user['role'] ?? '';
            
            error_log("User role: $userRole");
            
            // Only admin can import staff
            if ($userRole !== 'admin') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Only Admin can import staff'
                ], 403);
            }

            // Check if file was uploaded
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                error_log("File upload error: " . ($_FILES['file']['error'] ?? 'no file'));
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No file uploaded or upload error occurred'
                ], 400);
            }

            $file = $_FILES['file'];
            error_log("File uploaded: " . $file['name']);
            
            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExtension !== 'csv') {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Only CSV files are allowed'
                ], 400);
            }

            // Read and parse CSV
            error_log("Parsing CSV file...");
            $csvData = $this->parseCSV($file['tmp_name']);
            error_log("CSV parsed. Rows: " . count($csvData));
            
            if (empty($csvData)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'CSV file is empty or invalid'
                ], 400);
            }

            // DEBUG: Return first row to see what we're getting
            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'debug' => true,
                    'total_rows' => count($csvData),
                    'first_row_keys' => array_keys($csvData[0]),
                    'first_row_sample' => array_slice($csvData[0], 0, 10),
                    'second_row_keys' => isset($csvData[1]) ? array_keys($csvData[1]) : [],
                    'second_row_sample' => isset($csvData[1]) ? array_slice($csvData[1], 0, 10) : []
                ]);
            }

            // Process staff import
            error_log("Processing staff import...");
            $result = $this->processStaffImport($csvData);
            error_log("Import completed. Imported: " . $result['imported'] . ", Skipped: " . $result['skipped']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Staff import completed',
                'summary' => $result
            ]);

        } catch (\PDOException $e) {
            error_log("Database error in importStaff: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            error_log("Error in importStaff: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to import staff: ' . $e->getMessage()
            ], 500);
        }
    }

    private function parseCSV(string $filePath): array
    {
        $data = [];
        $header = null;
        $normalizedHeader = null;
        
        // Column name mapping - maps various possible names to our expected names
        $columnMapping = [
            'staff_id' => ['staff_id', 'staff id', 'staffid', 'id', 'employee_id', 'employee id'],
            'title' => ['title', 'salutation'],
            'first_name' => ['first_name', 'first name', 'firstname', 'fname'],
            'middle_name' => ['middle_name', 'middle name', 'middlename', 'mname', 'other names', 'other_names'],
            'last_name' => ['last_name', 'last name', 'lastname', 'surname', 'lname'],
            'Staff Grade' => ['staff grade', 'staff_grade', 'grade', 'position', 'rank'],
            'Unit' => ['unit', 'department', 'dept', 'unit name', 'unit_name'],
            'email' => ['email', 'e-mail', 'email address', 'email_address'],
            'incharge' => ['incharge', 'in charge', 'in_charge', 'is_incharge', 'supervisor'],
            'dob' => ['dob', 'd.o.b', 'date of birth', 'date_of_birth', 'birth date', 'birth_date', 'birthdate'],
            'gender' => ['gender', 'sex'],
            'marital_status' => ['marital_status', 'marital status', 'marital', 'marriage status'],
            'nationality' => ['nationality', 'country', 'nation'],
            'nation_id_type' => ['nation_id_type', 'national_id_type', 'id type', 'id_type', 'identification type'],
            'nationa_id_number' => ['nationa_id_number', 'national_id_number', 'national id', 'national_id', 'id number', 'id_number'],
            'ssnit_number' => ['ssnit_number', 'ssnit number', 'ssnit', 'ssnit no', 'ssnit_no'],
            'tin_number' => ['tin_number', 'tin number', 'tin', 'tin no', 'tin_no', 'tax id'],
            'profile_photo_url' => ['profile_photo_url', 'profile photo', 'photo', 'photo url', 'photo_url', 'image']
        ];

        
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $rowIndex = 0;
            while (($row = fgetcsv($handle, 10000, ',', '"', '\\')) !== false) {
                if (!$header) {
                    // First row is header - clean and normalize
                    $header = array_map(function($col) {
                        // Remove BOM, trim whitespace, normalize spaces
                        $col = trim($col);
                        $col = str_replace("\xEF\xBB\xBF", '', $col); // Remove UTF-8 BOM
                        $col = preg_replace('/\s+/', ' ', $col); // Normalize multiple spaces to single space
                        return $col;
                    }, $row);
                    
                    // Create normalized header by mapping column names
                    $normalizedHeader = [];
                    foreach ($header as $col) {
                        $colLower = strtolower($col);
                        $mapped = false;
                        
                        foreach ($columnMapping as $expectedName => $variations) {
                            if (in_array($colLower, $variations)) {
                                $normalizedHeader[] = $expectedName;
                                $mapped = true;
                                break;
                            }
                        }
                        
                        // If no mapping found, use original column name
                        if (!$mapped) {
                            $normalizedHeader[] = $col;
                        }
                    }
                    
                    // Log headers for debugging
                    error_log("CSV Original Headers: " . json_encode($header));
                    error_log("CSV Normalized Headers: " . json_encode($normalizedHeader));
                } else {
                    // Skip empty rows
                    if (count(array_filter($row, fn($val) => !empty(trim($val)))) === 0) {
                        continue;
                    }
                    
                    // Ensure row has same number of columns as header
                    if (count($row) !== count($normalizedHeader)) {
                        error_log("Row $rowIndex has " . count($row) . " columns but header has " . count($normalizedHeader) . " columns");
                        // Pad or trim row to match header length
                        $row = array_pad(array_slice($row, 0, count($normalizedHeader)), count($normalizedHeader), '');
                    }
                    
                    // Combine normalized header with row data
                    $rowData = array_combine($normalizedHeader, array_map('trim', $row));
                    if ($rowData !== false) {
                        $data[] = $rowData;
                    }
                }
                $rowIndex++;
            }
            fclose($handle);
        }
        
        return $data;
    }

    private function processStaffImport(array $csvData): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        //echo json_encode($csvData);exit;

        $this->db->beginTransaction();

        try {
            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header
                
                try {
                    // Validate required fields (row is passed by reference and will be modified)
                    $validationResult = $this->validateStaffRow($row, $rowNumber);
                    if (!$validationResult['valid']) {
                        $errors[] = $validationResult['error'];
                        $skipped++;
                        continue;
                    }

                    // Handle missing last_name - use middle_name or first_name as fallback
                    $lastName = trim($row['last_name'] ?? '');
                    if (empty($lastName)) {
                        $lastName = trim($row['middle_name'] ?? '');
                        if (empty($lastName)) {
                            $lastName = trim($row['first_name']);
                        }
                    }

                    // Generate email if not provided
                    $email = !empty($row['email']) ? trim($row['email']) : null;
                    if (!$email) {
                        $firstName = strtolower(trim($row['first_name']));
                        $lastNameForEmail = strtolower($lastName);
                        $email = "{$firstName}.{$lastNameForEmail}@ghs.gov.gh";
                    }

                    // Use email as password - cost 8 is faster for bulk imports (still secure)
                    $password = password_hash($email, PASSWORD_BCRYPT, ['cost' => 8]);

                    // Check if email already exists
                    $stmt = $this->db->prepare("SELECT id FROM validation_staff WHERE email = :email");
                    $stmt->execute(['email' => $email]);
                    if ($stmt->fetch()) {
                        $errors[] = "Row {$rowNumber}: Email {$email} already exists";
                        $skipped++;
                        continue;
                    }

                    // Get unit_id - search by code or name, auto-create if not found
                    $unitId = null;
                    if (!empty($row['Unit'])) {
                        $unitValue = trim($row['Unit']);
                        // Try matching by code first, then by name
                        $stmt = $this->db->prepare("SELECT id FROM units WHERE code = :code OR name = :name LIMIT 1");
                        $stmt->execute(['code' => $unitValue, 'name' => $unitValue]);
                        $unit = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($unit) {
                            $unitId = $unit['id'];
                        } else {
                            // Auto-create the unit using the code as both code and name
                            $stmt = $this->db->prepare("INSERT INTO units (name, code) VALUES (:name, :code)");
                            $stmt->execute(['name' => $unitValue, 'code' => $unitValue]);
                            $unitId = $this->db->lastInsertId();
                        }
                    }

                    // Determine role based on incharge field
                    $role = 'staff';
                    if (!empty($row['incharge']) && strtolower(trim($row['incharge'])) === 'yes') {
                        $role = 'incharge';
                    }

                    // Normalize title to match DB enum: Mr, Mrs, Miss, Dr, Prof, Rev, Hon
                    $rawTitle = trim($row['title'] ?? 'Mr');
                    $validTitles = ['Mr', 'Mrs', 'Miss', 'Dr', 'Prof', 'Rev', 'Hon'];
                    $cleanTitle = rtrim($rawTitle, '.'); // strip trailing dot (e.g. "Mr.")
                    $title = in_array($cleanTitle, $validTitles) ? $cleanTitle : 'Mr';

                    // Build full name
                    $fullName = $title . ' ' . trim($row['first_name']) . ' ' . 
                                (isset($row['middle_name']) && !empty($row['middle_name']) ? trim($row['middle_name']) . ' ' : '') . 
                                $lastName;
                    
                    // Insert into validation_staff
                    $stmt = $this->db->prepare("
                        INSERT INTO validation_staff (name, email, password, role, unit_id)
                        VALUES (:name, :email, :password, :role, :unit_id)
                    ");
                    
                    $stmt->execute([
                        'name' => $fullName,
                        'email' => $email,
                        'password' => $password,
                        'role' => $role,
                        'unit_id' => $unitId
                    ]);

                    $staffId = $this->db->lastInsertId();

                    // Insert into staff_personal_info
                    $stmt = $this->db->prepare("
                        INSERT INTO staff_personal_info (
                            staff_id, title, first_name, middle_name, last_name, 
                            date_of_birth, gender, marital_status, nationality,
                            national_id_type, national_id_number, ssnit_number, tin_number,
                            profile_photo_url
                        ) VALUES (
                            :staff_id, :title, :first_name, :middle_name, :last_name,
                            :date_of_birth, :gender, :marital_status, :nationality,
                            :national_id_type, :national_id_number, :ssnit_number, :tin_number,
                            :profile_photo_url
                        )
                    ");

                    $stmt->execute([
                        'staff_id' => $staffId,
                        'title' => $title,
                        'first_name' => trim($row['first_name']),
                        'middle_name' => $row['middle_name'] ?? null,
                        'last_name' => $lastName,
                        'date_of_birth' => !empty($row['dob']) ? $row['dob'] : '1900-01-01',
                        'gender' => !empty($row['gender']) ? $row['gender'] : 'Other',
                        'marital_status' => in_array($row['marital_status'] ?? '', ['Single','Married','Divorced','Widowed','Separated']) ? $row['marital_status'] : 'Single',
                        'nationality' => $row['nationality'] ?? 'Ghanaian',
                        'national_id_type' => in_array($row['nation_id_type'] ?? '', ['Ghana Card','Passport','Voters ID','Drivers License','SSNIT']) ? $row['nation_id_type'] : 'Ghana Card',
                        'national_id_number' => $row['nationa_id_number'] ?? null,
                        'ssnit_number' => $row['ssnit_number'] ?? null,
                        'tin_number' => $row['tin_number'] ?? null,
                        'profile_photo_url' => $row['profile_photo_url'] ?? null
                    ]);

                    // If Staff Grade is provided, create employment info
                    if (!empty($row['Staff Grade'])) {
                        $stmt = $this->db->prepare("
                            INSERT INTO staff_employment_info (
                                staff_id, employee_number, staff_category, employment_type,
                                employment_status, date_of_first_appointment, unit_id,
                                position_title, job_grade
                            ) VALUES (
                                :staff_id, :employee_number, :staff_category, :employment_type,
                                :employment_status, :date_of_first_appointment, :unit_id,
                                :position_title, :job_grade
                            )
                        ");

                        $stmt->execute([
                            'staff_id' => $staffId,
                            'employee_number' => $row['staff_id'] ?? null,
                            'staff_category' => 'Senior Staff',
                            'employment_type' => 'Permanent',
                            'employment_status' => 'Active',
                            'date_of_first_appointment' => $row['dob'], // Using DOB as placeholder
                            'unit_id' => $unitId,
                            'position_title' => $row['Staff Grade'] ?? 'Staff',
                            'job_grade' => $row['Staff Grade'] ?? null
                        ]);
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $skipped++;
                }
            }

            $this->db->commit();

            return [
                'total_rows' => count($csvData),
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validateStaffRow(array &$row, int $rowNumber): array
    {
        // Log the row keys for debugging (first row only)
        if ($rowNumber === 2) {
            error_log("Row 2 keys: " . json_encode(array_keys($row)));
            error_log("Row 2 values (first 10): " . json_encode(array_slice($row, 0, 10, true)));
        }
        
        $requiredFields = ['first_name'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($row[$field])) {
                $missingFields[] = $field . " (not in array)";
            } elseif (empty(trim($row[$field]))) {
                $missingFields[] = $field . " (empty value)";
            }
        }
        
        if (!empty($missingFields)) {
            error_log("Row {$rowNumber}: Missing fields: " . implode(', ', $missingFields));
            error_log("Row {$rowNumber}: Available keys: " . json_encode(array_keys($row)));
            return [
                'valid' => false,
                'error' => "Row {$rowNumber}: Missing required field '" . explode(' ', $missingFields[0])[0] . "'"
            ];
        }

        // Validate and convert date format
        $dobValue = trim($row['dob'] ?? '');
        if (!empty($dobValue)) {
            // Try multiple date formats
            $formats = [
                'Y-m-d' => 'Y-m-d',
                'n/j/Y' => 'Y-m-d',  // 5/15/1990 -> 1990-05-15
                'j/n/Y' => 'Y-m-d',  // 15/5/1990 -> 1990-05-15
                'm/d/Y' => 'Y-m-d',  // 05/15/1990 -> 1990-05-15
                'd/m/Y' => 'Y-m-d',  // 15/05/1990 -> 1990-05-15
                'Y/m/d' => 'Y-m-d',
                'm-d-Y' => 'Y-m-d',
                'd-m-Y' => 'Y-m-d'
            ];
            
            $parsedDate = null;
            
            foreach ($formats as $inputFormat => $outputFormat) {
                $date = \DateTime::createFromFormat($inputFormat, $dobValue);
                if ($date) {
                    // Verify the date is valid
                    $lastErrors = \DateTime::getLastErrors();
                    $warningCount = is_array($lastErrors) ? $lastErrors['warning_count'] : 0;
                    $errorCount = is_array($lastErrors) ? $lastErrors['error_count'] : 0;
                    if ($warningCount == 0 && $errorCount == 0) {
                        $parsedDate = $date;
                        break;
                    }
                }
            }
            
            if (!$parsedDate) {
                return [
                    'valid' => false,
                    'error' => "Row {$rowNumber}: Invalid date format for dob (got '{$dobValue}'). Supported formats: YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY"
                ];
            }
            
            // Store normalized date back in row
            $row['dob'] = $parsedDate->format('Y-m-d');
        }

        // Validate gender if provided
        if (isset($row['gender']) && !empty(trim($row['gender']))) {
            $gender = trim($row['gender']);
            
            // Normalize gender
            $genderUpper = strtoupper($gender);
            if ($genderUpper === 'M' || $genderUpper === 'MALE') {
                $row['gender'] = 'Male';
            } elseif ($genderUpper === 'F' || $genderUpper === 'FEMALE') {
                $row['gender'] = 'Female';
            } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
                return [
                    'valid' => false,
                    'error' => "Row {$rowNumber}: Invalid gender value (must be Male, Female, or Other, got '{$gender}')"
                ];
            }
        } else {
            // Gender is missing or empty - set default
            $row['gender'] = null;
        }

        return ['valid' => true];
    }

    #[OA\Get(
        path: "/api/v1/staff/import/template",
        summary: "Download CSV template",
        description: "Download a CSV template file for staff import",
        tags: ["Staff Import"],
        responses: [
            new OA\Response(response: 200, description: "CSV template file")
        ]
    )]
    public function downloadTemplate(Request $request, Response $response): Response
    {
        $headers = [
            'staff_id', 'title', 'first_name', 'middle_name', 'last_name', 'Staff Grade',
            'Unit', 'email', 'incharge', 'dob', 'gender', 'marital_status',
            'nationality', 'nation_id_type', 'nationa_id_number', 'ssnit_number',
            'tin_number', 'profile_photo_url'
        ];

        $sampleData = [
            [
                'EMP001', 'Mr', 'John', 'Kwame', 'Mensah', 'Senior Staff',
                'Human Resources', 'john.mensah@ghs.gov.gh', 'no', '1990-05-15', 'Male', 'Single',
                'Ghanaian', 'Ghana Card', 'GHA-123456789-0', 'C123456789',
                'TIN123456', ''
            ],
            [
                'EMP002', 'Mrs', 'Mary', 'Ama', 'Asante', 'Junior Staff',
                'Finance', 'mary.asante@ghs.gov.gh', 'yes', '1985-08-20', 'Female', 'Married',
                'Ghanaian', 'Ghana Card', 'GHA-987654321-0', 'C987654321',
                'TIN987654', ''
            ]
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers, ',', '"', '\\');
        foreach ($sampleData as $row) {
            fputcsv($csv, $row, ',', '"', '\\');
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', 'attachment; filename="staff_import_template.csv"');
        $response->setContent($content);
        return $response;
    }

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        // Add CORS headers
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
        $allowedOrigins = array_map('trim', $allowedOrigins);
        
        if (!empty($origin) && (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins))) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-CSRF-TOKEN,X-API-KEY,X-Api-Key');
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode($statusCode);
        return $response;
    }
}
