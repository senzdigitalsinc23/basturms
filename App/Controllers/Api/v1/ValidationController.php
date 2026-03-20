<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Validation",
    description: "API endpoints for staff validation management"
)]
class ValidationController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Post(
        path: "/api/v1/validations",
        summary: "Validate staff members",
        description: "Mark staff members as validated for a specific month and year with status and comments",
        tags: ["Validation"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["staffIds", "month", "year", "validationStatus"],
                properties: [
                    new OA\Property(property: "staffIds", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "month", type: "string", example: "January"),
                    new OA\Property(property: "year", type: "integer", example: 2026),
                    new OA\Property(property: "validationStatus", type: "string", enum: ["At Post", "Not At Post"]),
                    new OA\Property(property: "comments", type: "string", example: "Staff is present at post")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Staff validated successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function validate(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $staffIds = $data['staffIds'] ?? [];
            $month = $data['month'] ?? '';
            $year = $data['year'] ?? 0;
            $validationStatus = $data['validationStatus'] ?? null;
            $comments = $data['comments'] ?? null;

            // Get authenticated user from JWT token
            $user = $request->getAttribute('user');
            $validatedBy = $user['user_id'] ?? null;
            $userRole = $user['role'] ?? '';
            $userUnitId = $user['unit_id'] ?? null;

            if (empty($staffIds) || empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }

            if (empty($validationStatus) || !in_array($validationStatus, ['At Post', 'Not At Post'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Validation status must be either "At Post" or "Not At Post"'
                ], 400);
            }

            // Security check: If user is incharge, verify all staff belong to their unit
            // Exception: HR Incharge has full access like admin
            if ($userRole === 'incharge' && $userUnitId) {
                // Check if this is HR incharge
                $stmt = $this->db->prepare("
                    SELECT u.name as unit_name
                    FROM validation_staff s
                    LEFT JOIN units u ON s.unit_id = u.id
                    WHERE s.id = :user_id
                ");
                $stmt->execute(['user_id' => $validatedBy]);
                $userUnit = $stmt->fetch(PDO::FETCH_ASSOC);

                // Only restrict non-HR incharges
                if (!$userUnit || $userUnit['unit_name'] !== 'Human Resources') {
                    // Check if all provided staff IDs belong to the incharge's unit
                    $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as count 
                        FROM validation_staff 
                        WHERE id IN ($placeholders) 
                        AND unit_id = ?
                        AND deleted_at IS NULL
                    ");

                    $params = array_merge($staffIds, [$userUnitId]);
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    // If the count doesn't match the number of staff IDs, some are outside the unit
                    if ($result['count'] != count($staffIds)) {
                        return $this->jsonResponse($response, [
                            'success' => false,
                            'message' => 'You can only validate staff in your unit'
                        ], 403);
                    }
                }
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO validations (staff_id, month, year, validated, validation_status, comments, validated_by, validated_at)
                VALUES (:staff_id, :month, :year, TRUE, :validation_status, :comments, :validated_by, NOW())
                ON DUPLICATE KEY UPDATE
                    updated_at = NOW()
            ");

            foreach ($staffIds as $staffId) {
                $stmt->execute([
                    'staff_id' => $staffId,
                    'month' => $month,
                    'year' => $year,
                    'validation_status' => $validationStatus,
                    'comments' => $comments,
                    'validated_by' => $validatedBy
                ]);
            }

            $this->db->commit();

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Staff validated successfully'
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to validate staff: ' . $e->getMessage()
            ], 500);
        }
    }


    #[OA\Get(
        path: "/api/v1/validations",
        summary: "Get validations",
        description: "Retrieve validation records for a specific month and year",
        tags: ["Validation"],
        parameters: [
            new OA\Parameter(name: "month", in: "query", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Validations retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function getValidations(Request $request, Response $response): Response
    {
        try {
            $month = $request->getQuery('month');
            $year = $request->getQuery('year');
            
            // Get authenticated user
            $user = $request->getAttribute('user');
            $userRole = $user['role'] ?? '';
            $userUnitId = $user['unit_id'] ?? null;

            if (empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Month and year are required'
                ], 400);
            }

            // Build query based on role
            // HR Incharge has full access like admin
            if ($userRole === 'incharge' && $userUnitId) {
                // Check if this is HR incharge
                $stmt = $this->db->prepare("
                    SELECT u.name as unit_name
                    FROM validation_staff s
                    LEFT JOIN units u ON s.unit_id = u.id
                    WHERE s.id = :user_id
                ");
                $stmt->execute(['user_id' => $user['user_id']]);
                $userUnit = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // HR Incharge sees all validations (like admin)
                if ($userUnit && $userUnit['unit_name'] === 'Human Resources') {
                    $stmt = $this->db->prepare("
                        SELECT 
                            v.id,
                            v.staff_id as staffId,
                            v.month,
                            v.year,
                            v.validated,
                            v.validation_status as validationStatus,
                            v.comments,
                            v.validated_by as validatedBy,
                            v.validated_at as validatedAt,
                            s.name as staffName,
                            s.unit_id as unitId,
                            u.name as unitName,
                            vb.name as validatedByName
                        FROM validations v
                        INNER JOIN validation_staff s ON v.staff_id = s.id
                        LEFT JOIN units u ON s.unit_id = u.id
                        LEFT JOIN validation_staff vb ON v.validated_by = vb.id
                        WHERE v.month = :month AND v.year = :year
                        AND s.deleted_at IS NULL
                        ORDER BY v.validated_at DESC
                    ");
                    
                    $stmt->execute(['month' => $month, 'year' => $year]);
                } else {
                    // Other incharges can only see validations for staff in their unit
                    $stmt = $this->db->prepare("
                        SELECT 
                            v.id,
                            v.staff_id as staffId,
                            v.month,
                            v.year,
                            v.validated,
                            v.validation_status as validationStatus,
                            v.comments,
                            v.validated_by as validatedBy,
                            v.validated_at as validatedAt,
                            s.name as staffName,
                            s.unit_id as unitId,
                            u.name as unitName,
                            vb.name as validatedByName
                        FROM validations v
                        INNER JOIN validation_staff s ON v.staff_id = s.id
                        LEFT JOIN units u ON s.unit_id = u.id
                        LEFT JOIN validation_staff vb ON v.validated_by = vb.id
                        WHERE v.month = :month AND v.year = :year
                        AND s.unit_id = :unit_id
                        AND s.deleted_at IS NULL
                        ORDER BY v.validated_at DESC
                    ");
                    
                    $stmt->execute([
                        'month' => $month, 
                        'year' => $year,
                        'unit_id' => $userUnitId
                    ]);
                }
            } else {
                // Admin and Accountant can see all validations
                $stmt = $this->db->prepare("
                    SELECT 
                        v.id,
                        v.staff_id as staffId,
                        v.month,
                        v.year,
                        v.validated,
                        v.validation_status as validationStatus,
                        v.comments,
                        v.validated_by as validatedBy,
                        v.validated_at as validatedAt,
                        s.name as staffName,
                        s.unit_id as unitId,
                        u.name as unitName,
                        vb.name as validatedByName
                    FROM validations v
                    INNER JOIN validation_staff s ON v.staff_id = s.id
                    LEFT JOIN units u ON s.unit_id = u.id
                    LEFT JOIN validation_staff vb ON v.validated_by = vb.id
                    WHERE v.month = :month AND v.year = :year
                    AND s.deleted_at IS NULL
                    ORDER BY v.validated_at DESC
                ");
                
                $stmt->execute(['month' => $month, 'year' => $year]);
            }

            $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'validations' => $validations
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve validations: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validations/unit-statistics",
        summary: "Get validation statistics by unit",
        description: "Retrieve validation statistics grouped by unit for a specific month and year",
        tags: ["Validation"],
        parameters: [
            new OA\Parameter(name: "month", in: "query", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Unit statistics retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function getUnitStatistics(Request $request, Response $response): Response
    {
        try {
            $month = $request->getQuery('month');
            $year = $request->getQuery('year');
            
            if (empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Month and year are required'
                ], 400);
            }

            // Get statistics grouped by unit
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as unitId,
                    u.name as unitName,
                    COUNT(DISTINCT s.id) as totalStaff,
                    COUNT(DISTINCT v.id) as totalValidated,
                    SUM(CASE WHEN v.validation_status = 'At Post' THEN 1 ELSE 0 END) as totalAtPost,
                    SUM(CASE WHEN v.validation_status = 'Not At Post' THEN 1 ELSE 0 END) as totalNotAtPost
                FROM units u
                LEFT JOIN validation_staff s ON u.id = s.unit_id AND s.deleted_at IS NULL
                LEFT JOIN validations v ON s.id = v.staff_id 
                    AND v.month = :month 
                    AND v.year = :year
                GROUP BY u.id, u.name
                ORDER BY u.name
            ");
            
            $stmt->execute(['month' => $month, 'year' => $year]);
            $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve unit statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validations/export",
        summary: "Export validations",
        description: "Export validation data in CSV, Excel or PDF format",
        tags: ["Validation"],
        parameters: [
            new OA\Parameter(name: "month", in: "query", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "format", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["csv", "excel", "pdf"], default: "csv"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Export file generated successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function exportValidations(Request $request, Response $response): Response
    {
        try {
            $month = $request->getQuery('month');
            $year  = $request->getQuery('year');
            $format = strtolower($request->getQuery('format') ?: 'csv');

            $user       = $request->getAttribute('user');
            $userRole   = $user['role'] ?? '';
            $userUnitId = $user['unit_id'] ?? null;

            if (empty($month) || empty($year)) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'Month and year are required'], 400);
            }

            // ── Build query ──────────────────────────────────────────────
            $baseSelect = "
                SELECT
                    s.name              AS 'Staff Name',
                    s.email             AS 'Email',
                    u.name              AS 'Unit',
                    d.name              AS 'Department',
                    v.validation_status AS 'Validation Status',
                    v.comments          AS 'Comments',
                    vb.name             AS 'Validated By',
                    v.validated_at      AS 'Validated At'
                FROM validations v
                INNER JOIN validation_staff s  ON v.staff_id    = s.id
                LEFT  JOIN units u             ON s.unit_id     = u.id
                LEFT  JOIN staff_employment_info e ON s.id      = e.staff_id
                LEFT  JOIN departments d       ON e.department_id = d.id
                LEFT  JOIN validation_staff vb ON v.validated_by = vb.id
                WHERE v.month = :month AND v.year = :year
                AND s.deleted_at IS NULL
            ";

            $limitToUnit = false;
            if ($userRole === 'incharge' && $userUnitId) {
                $chk = $this->db->prepare("SELECT u.name as unit_name FROM validation_staff s LEFT JOIN units u ON s.unit_id = u.id WHERE s.id = :uid");
                $chk->execute(['uid' => $user['user_id']]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                $limitToUnit = !($row && $row['unit_name'] === 'Human Resources');
            }

            if ($limitToUnit) {
                $stmt = $this->db->prepare($baseSelect . " AND s.unit_id = :unit_id ORDER BY s.name");
                $stmt->execute(['month' => $month, 'year' => $year, 'unit_id' => $userUnitId]);
            } else {
                $stmt = $this->db->prepare($baseSelect . " ORDER BY u.name, s.name");
                $stmt->execute(['month' => $month, 'year' => $year]);
            }

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'No validation data found for the specified period'], 404);
            }

            $headers = array_keys($data[0]);
            $title   = "Validation Report – {$month} {$year}";

            // ── CSV ──────────────────────────────────────────────────────
            if ($format === 'csv') {
                $tmp = fopen('php://temp', 'r+');
                fputcsv($tmp, $headers);
                foreach ($data as $row) fputcsv($tmp, $row);
                rewind($tmp);
                $content = stream_get_contents($tmp);
                fclose($tmp);

                $response->setHeader('Content-Type', 'text/csv');
                $response->setHeader('Content-Disposition', "attachment; filename=\"validations_{$month}_{$year}.csv\"");
                $response->setHeader('Cache-Control', 'max-age=0');
                $response->setContent($content);
                $response->setStatusCode(200);
                return $response;
            }

            // ── Excel (.xlsx) ────────────────────────────────────────────
            if ($format === 'excel') {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Validations');

                // Title row
                $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1');
                $sheet->setCellValue('A1', $title);
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E40AF']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Column headers (row 2)
                foreach ($headers as $col => $header) {
                    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '2';
                    $sheet->setCellValue($cell, $header);
                }
                $headerRange = 'A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '2';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF3B82F6']],
                ]);

                // Data rows
                foreach ($data as $rowIdx => $row) {
                    $excelRow = $rowIdx + 3;
                    foreach (array_values($row) as $col => $value) {
                        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $excelRow;
                        $sheet->setCellValue($cell, $value ?? '');
                    }
                    // Zebra striping
                    if ($rowIdx % 2 === 0) {
                        $rowRange = 'A' . $excelRow . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . $excelRow;
                        $sheet->getStyle($rowRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F9FF');
                    }
                }

                // Auto-size columns
                foreach (range(1, count($headers)) as $col) {
                    $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                }

                // Color-code validation status column (col 5 = "Validation Status")
                $statusColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5);
                foreach ($data as $rowIdx => $row) {
                    $excelRow = $rowIdx + 3;
                    $status = $row['Validation Status'] ?? '';
                    $argb = match($status) {
                        'At Post'     => 'FFD1FAE5',
                        'Not At Post' => 'FFFEE2E2',
                        default       => null,
                    };
                    if ($argb) {
                        $sheet->getStyle($statusColLetter . $excelRow)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($argb);
                    }
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                ob_start();
                $writer->save('php://output');
                $content = ob_get_clean();

                $response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $response->setHeader('Content-Disposition', "attachment; filename=\"validations_{$month}_{$year}.xlsx\"");
                $response->setHeader('Cache-Control', 'max-age=0');
                $response->setContent($content);
                $response->setStatusCode(200);
                return $response;
            }

            // ── PDF ──────────────────────────────────────────────────────
            if ($format === 'pdf') {
                $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->SetCreator('AGH Validation System');
                $pdf->SetTitle($title);
                $pdf->SetMargins(10, 10, 10);
                $pdf->SetAutoPageBreak(true, 10);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();

                // Title
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->SetTextColor(30, 64, 175);
                $pdf->Cell(0, 10, $title, 0, 1, 'C');
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(3);

                // Table header
                $colWidths = [50, 55, 35, 35, 30, 40, 35, 37];
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->SetFillColor(59, 130, 246);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($headers as $i => $header) {
                    $pdf->Cell($colWidths[$i] ?? 30, 7, $header, 1, 0, 'C', true);
                }
                $pdf->Ln();

                // Data rows
                $pdf->SetFont('helvetica', '', 7);
                $pdf->SetTextColor(0, 0, 0);
                foreach ($data as $rowIdx => $row) {
                    $fill = ($rowIdx % 2 === 0);
                    if ($fill) $pdf->SetFillColor(240, 249, 255);
                    else        $pdf->SetFillColor(255, 255, 255);

                    $values = array_values($row);
                    foreach ($values as $i => $value) {
                        $status = $row['Validation Status'] ?? '';
                        if ($headers[$i] === 'Validation Status') {
                            if ($status === 'At Post')     $pdf->SetFillColor(209, 250, 229);
                            elseif ($status === 'Not At Post') $pdf->SetFillColor(254, 226, 226);
                        }
                        $pdf->Cell($colWidths[$i] ?? 30, 6, $value ?? '', 1, 0, 'L', true);
                        // Reset fill after status cell
                        if ($headers[$i] === 'Validation Status') {
                            if ($fill) $pdf->SetFillColor(240, 249, 255);
                            else        $pdf->SetFillColor(255, 255, 255);
                        }
                    }
                    $pdf->Ln();
                }

                $content = $pdf->Output('', 'S');

                $response->setHeader('Content-Type', 'application/pdf');
                $response->setHeader('Content-Disposition', "attachment; filename=\"validations_{$month}_{$year}.pdf\"");
                $response->setHeader('Cache-Control', 'max-age=0');
                $response->setContent($content);
                $response->setStatusCode(200);
                return $response;
            }

            return $this->jsonResponse($response, ['success' => false, 'message' => 'Invalid format. Use csv, excel or pdf.'], 400);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to export validations: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/validations",
        summary: "Cancel staff validation",
        description: "Cancel/remove validation for staff members (only allowed during validation period)",
        tags: ["Validation"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["staffIds", "month", "year"],
                properties: [
                    new OA\Property(property: "staffIds", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "month", type: "string", example: "January"),
                    new OA\Property(property: "year", type: "integer", example: 2026)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Validation cancelled successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 403, description: "Validation period has ended")
        ]
    )]
    public function cancelValidation(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $staffIds = $data['staffIds'] ?? [];
            $month = $data['month'] ?? '';
            $year = $data['year'] ?? 0;

            // Get authenticated user from JWT token
            $user = $request->getAttribute('user');
            $validatedBy = $user['user_id'] ?? null;
            $userRole = $user['role'] ?? '';
            $userUnitId = $user['unit_id'] ?? null;

            if (empty($staffIds) || empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }

            // Check if validation period is still active
            $stmt = $this->db->prepare("
                SELECT start_date, end_date
                FROM validation_settings
                WHERE month = :month AND year = :year
            ");
            $stmt->execute(['month' => $month, 'year' => $year]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            $now = date('Y-m-d H:i:s');
            
            if (!$settings) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No validation period set for this month'
                ], 403);
            }

            if ($now > $settings['end_date']) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Cannot cancel validation - validation period has ended'
                ], 403);
            }

            // Security check: If user is incharge, verify all staff belong to their unit
            // Exception: HR Incharge has full access like admin
            if ($userRole === 'incharge' && $userUnitId) {
                // Check if this is HR incharge
                $stmt = $this->db->prepare("
                    SELECT u.name as unit_name
                    FROM validation_staff s
                    LEFT JOIN units u ON s.unit_id = u.id
                    WHERE s.id = :user_id
                ");
                $stmt->execute(['user_id' => $validatedBy]);
                $userUnit = $stmt->fetch(PDO::FETCH_ASSOC);

                // Only restrict non-HR incharges
                if (!$userUnit || $userUnit['unit_name'] !== 'Human Resources') {
                    // Check if all provided staff IDs belong to the incharge's unit
                    $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as count 
                        FROM validation_staff 
                        WHERE id IN ($placeholders) 
                        AND unit_id = ?
                        AND deleted_at IS NULL
                    ");

                    $params = array_merge($staffIds, [$userUnitId]);
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    // If the count doesn't match the number of staff IDs, some are outside the unit
                    if ($result['count'] != count($staffIds)) {
                        return $this->jsonResponse($response, [
                            'success' => false,
                            'message' => 'You can only cancel validation for staff in your unit'
                        ], 403);
                    }
                }
            }

            $this->db->beginTransaction();

            // Delete validation records
            $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
            $stmt = $this->db->prepare("
                DELETE FROM validations 
                WHERE staff_id IN ($placeholders) 
                AND month = ? 
                AND year = ?
            ");

            $params = array_merge($staffIds, [$month, $year]);
            $stmt->execute($params);

            $this->db->commit();

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Validation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to cancel validation: ' . $e->getMessage()
            ], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode($statusCode);
        return $response;
    }


}
