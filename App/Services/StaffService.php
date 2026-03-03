<?php

namespace App\Services;

use App\Core\Database;
use App\DTOs\StaffDTO;
use App\DTOs\StaffAddressDTO;
use App\DTOs\StaffAcademicHistoryDTO;
use App\DTOs\StaffAppointmentDTO;
use App\Repositories\StaffRepository;
use App\Exceptions\ValidationException;
use PDO;

class StaffService
{
    private Database $database;
    private StaffRepository $staffRepository;
    private LoggingService $logger;

    public function __construct(
        StaffRepository $staffRepository,
        LoggingService $logger
    ) {
        $this->database = Database::getInstance();
        $this->staffRepository = $staffRepository;
        $this->logger = $logger;
    }

    /**
     * Register a new staff member
     */
    public function registerStaff(array $data): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Validate required fields
            $this->validateStaffData($data);

            // Check for duplicates
            if ($this->staffRepository->emailExists($data['email'])) {
                throw new ValidationException(
                    ['email' => ['Email already exists']],
                    'Email already registered'
                );
            }

            if ($this->staffRepository->idNumberExists($data['id_no'])) {
                throw new ValidationException(
                    ['id_no' => ['ID number already exists']],
                    'ID number already registered'
                );
            }

            if ($this->staffRepository->phoneExists($data['phone'])) {
                throw new ValidationException(
                    ['phone' => ['Phone number already exists']],
                    'Phone number already registered'
                );
            }

            if (!empty($data['snnit_no']) && $this->staffRepository->ssnitNoExists($data['snnit_no'])) {
                throw new ValidationException(
                    ['snnit_no' => ['SSNIT number already exists']],
                    'SSNIT number already registered'
                );
            }

            // Generate staff ID
            $staffId = $this->staffRepository->generateStaffId();

            // Create staff DTO
            $staffDTO = StaffDTO::fromArray([
                'staff_id' => $staffId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'other_name' => $data['other_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'id_type' => $data['id_type'],
                'id_no' => $data['id_no'],
                'snnit_no' => $data['snnit_no'] ?? null,
                'date_of_joining' => $data['date_of_joining'] ?? date('Y-m-d'),
                'status' => 'active',
                'added_by' => $data['added_by'] ?? 'system'
            ]);

            // Create staff record
            if (!$this->staffRepository->createStaff($staffDTO)) {
                throw new \Exception('Failed to create staff record');
            }

            // Create staff address if provided
            if (!empty($data['address'])) {
                $addressDTO = StaffAddressDTO::fromArray([
                    'staff_id' => $staffId,
                    'country' => $data['address']['country'] ?? '',
                    'city' => $data['address']['city'] ?? '',
                    'hometown' => $data['address']['hometown'] ?? '',
                    'residence' => $data['address']['residence'] ?? '',
                    'house_no' => $data['address']['house_no'] ?? '',
                    'gps_no' => $data['address']['gps_no'] ?? ''
                ]);

                if (!$this->staffRepository->createStaffAddress($addressDTO)) {
                    throw new \Exception('Failed to create staff address');
                }
            }

            // Create academic history records if provided
            if (!empty($data['academic_history']) && is_array($data['academic_history'])) {
                foreach ($data['academic_history'] as $history) {
                    if (!empty($history['school_name']) && !empty($history['qualification'])) {
                        $historyDTO = StaffAcademicHistoryDTO::fromArray([
                            'staff_id' => $staffId,
                            'school_name' => $history['school_name'] ?? '',
                            'program_offered' => $history['program_offered'] ?? '',
                            'qualification' => $history['qualification'] ?? '',
                            'year_completed' => $history['year_completed'] ?? ''
                        ]);

                        if (!$this->staffRepository->createStaffAcademicHistory($historyDTO)) {
                            throw new \Exception('Failed to create academic history');
                        }
                    }
                }
            }

            // Create appointment history if provided
            if (!empty($data['appointment'])) {
                $appointmentDTO = StaffAppointmentDTO::fromArray([
                    'staff_id' => $staffId,
                    'appointment_date' => $data['appointment']['appointment_date'] ?? date('Y-m-d'),
                    'appointment_status' => $data['appointment']['appointment_status'] ?? 'appointed',
                    'class_teacher_for' => $data['appointment']['class_teacher_for'] ?? null,
                    'created_by' => $data['added_by']
                ]);

                if (!$this->staffRepository->createStaffAppointment($appointmentDTO)) {
                    throw new \Exception('Failed to create appointment history');
                }

                // Assign classes
                if (!empty($data['appointment']['assigned_classes']) && is_array($data['appointment']['assigned_classes'])) {
                    foreach ($data['appointment']['assigned_classes'] as $class) {
                        $classId = is_array($class) ? ($class['class_id'] ?? null) : $class;
                        if (!empty($classId)) {
                            if (!$this->staffRepository->assignClassToStaff($staffId, $classId, $data['added_by'])) {
                                throw new \Exception('Failed to assign class: ' . $classId);
                            }
                        }
                    }
                }

                // Assign subjects
                if (!empty($data['appointment']['assigned_subjects']) && is_array($data['appointment']['assigned_subjects'])) {
                    foreach ($data['appointment']['assigned_subjects'] as $subject) {
                        if (!empty($subject['subject_id']) && !empty($subject['class_id'])) {
                            if (!$this->staffRepository->assignSubjectToStaff(
                                $staffId,
                                $subject['subject_id'],
                                $subject['class_id'],
                                $data['added_by']
                            )) {
                                throw new \Exception('Failed to assign subject: ' . $subject['subject_id']);
                            }
                        }
                    }
                }

                // Assign roles
                if (!empty($data['appointment']['roles']) && is_array($data['appointment']['roles'])) {
                    foreach ($data['appointment']['roles'] as $roleId) {
                        if (!empty($roleId)) {
                            if (!$this->staffRepository->assignRoleToStaff($staffId, (int)$roleId)) {
                                throw new \Exception('Failed to assign role: ' . $roleId);
                            }
                        }
                    }
                }
            }

            // Create user account for staff
            $tempPassword = $this->createStaffUserAccount($staffId, $data['email'], $data['role_id'] ?? null);

            $db->commit();

            $this->logger->logAudit(
                'staff_registration',
                'Staff registered successfully: ' . $staffId,
                $data['added_by'] ?? 'system'
            );

            return [
                'staff_id' => $staffId,
                'email' => $data['email'],
                'login_credentials' => [
                    'username' => $data['email'],
                    'temporary_password' => $tempPassword,
                    'note' => 'Please change your password after first login'
                ],
                'message' => 'Staff registered successfully'
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $this->logger->logAudit(
                'staff_registration_error',
                'Staff registration failed: ' . $e->getMessage(),
                $data['added_by'] ?? 'unknown'
            );

            throw $e;
        }
    }

    /**
     * Validate staff registration data
     */
    private function validateStaffData(array $data): void
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = ['First name is required'];
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = ['Last name is required'];
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Valid email is required'];
        }

        if (empty($data['phone'])) {
            $errors['phone'] = ['Phone number is required'];
        } elseif (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
            $errors['phone'] = ['Phone number must be 10-15 digits'];
        }

        if (empty($data['id_type'])) {
            $errors['id_type'] = ['ID type is required'];
        }

        if (empty($data['id_no'])) {
            $errors['id_no'] = ['ID number is required'];
        }

        // Validate address if provided
        if (!empty($data['address'])) {
            $address = $data['address'];
            
            if (empty($address['country'])) {
                $errors['address.country'] = ['Country is required'];
            }
            
            if (empty($address['hometown'])) {
                $errors['address.hometown'] = ['Hometown is required'];
            }
            
            if (empty($address['residence'])) {
                $errors['address.residence'] = ['Residence is required'];
            }
            
            if (empty($address['house_no'])) {
                $errors['address.house_no'] = ['House number is required'];
            }
            
            if (empty($address['gps_no'])) {
                $errors['address.gps_no'] = ['GPS number is required'];
            }
        }

        // Validate academic history if provided
        if (!empty($data['academic_history']) && is_array($data['academic_history'])) {
            foreach ($data['academic_history'] as $index => $history) {
                if (empty($history['school_name'])) {
                    $errors["academic_history.{$index}.school_name"] = ['School name is required'];
                }
                
                if (empty($history['program_offered'])) {
                    $errors["academic_history.{$index}.program_offered"] = ['Program offered is required'];
                }
                
                if (empty($history['qualification'])) {
                    $errors["academic_history.{$index}.qualification"] = ['Qualification is required'];
                }
                
                if (empty($history['year_completed'])) {
                    $errors["academic_history.{$index}.year_completed"] = ['Year of completion is required'];
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'Validation failed');
        }
    }

    /**
     * Create user account for staff member
     */
    private function createStaffUserAccount(string $staffId, string $email, ?int $roleId = null): string
    {
        $pdo = $this->database->getConnection();

        // Generate a secure temporary password (8 characters)
        $tempPassword = bin2hex(random_bytes(4)); // 8 character hex string
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        // Default to staff role if not specified (role_id 18 or adjust based on your system)
        $roleId = $roleId ?? 18;

        $sql = "INSERT INTO users (user_id, username, email, password, role_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $staffId,
            $email,
            $email,
            $hashedPassword,
            $roleId
        ]);

        $this->logger->logAudit(
            'staff_user_account_created',
            'Staff user account created: ' . $staffId . ' (temp password generated)',
            $staffId
        );

        // Return the temporary password
        return $tempPassword;
    }

    /**
     * Get staff by ID
     */
    public function getStaffById(string $staffId): ?array
    {
        $staff = $this->staffRepository->getStaffById($staffId);
        
        if (!$staff) {
            return null;
        }

        return $this->formatStaffResponse($staff, $staffId);
    }

    /**
     * Get all staff with pagination
     */
    public function getAllStaff(int $page = 1, int $limit = 10, string $status = 'active'): array
    {
        $offset = ($page - 1) * $limit;
        $staffList = $this->staffRepository->getAllStaff($limit, $offset, $status);
        $total = $this->staffRepository->countStaff($status);

        // Format each staff record with simplified data
        $formattedStaff = [];
        foreach ($staffList as $staff) {
            $formattedStaff[] = $this->formatStaffListItem($staff, $staff['staff_id']);
        }

        return [
            'data' => $formattedStaff,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Format staff list item with minimal data
     */
    private function formatStaffListItem(array $staff, string $staffId): array
    {
        try {
            // Get only essential appointment data
            $assignedClasses = [];
            $assignedSubjects = [];
            $assignedRoles = [];
            
            try {
                $assignedClasses = $this->staffRepository->getStaffClasses($staffId);
            } catch (\Exception $e) {
                // Silently fail
            }
            
            try {
                $assignedSubjects = $this->staffRepository->getStaffSubjects($staffId);
            } catch (\Exception $e) {
                // Silently fail
            }
            
            try {
                $assignedRoles = $this->staffRepository->getStaffRoles($staffId);
            } catch (\Exception $e) {
                // Silently fail
            }

            return [
                'staff_id' => $staff['staff_id'] ?? null,
                'first_name' => $staff['first_name'] ?? null,
                'last_name' => $staff['last_name'] ?? null,
                'other_name' => $staff['other_name'] ?? null,
                'roles' => $assignedRoles,
                'classes_assigned' => $assignedClasses,
                'subjects_assigned' => $assignedSubjects,
                'date_of_joining' => $staff['date_of_joining'] ?? null,
                'status' => $staff['status'] ?? null,
            ];
        } catch (\Exception $e) {
            // Log error and return basic info
            $this->logger->logAudit(
                'format_staff_list_item_error',
                'Error formatting staff list item for ' . $staffId . ': ' . $e->getMessage()
            );

            return [
                'staff_id' => $staff['staff_id'] ?? null,
                'first_name' => $staff['first_name'] ?? null,
                'last_name' => $staff['last_name'] ?? null,
                'other_name' => $staff['other_name'] ?? null,
                'roles' => [],
                'classes_assigned' => [],
                'subjects_assigned' => [],
                'date_of_joining' => $staff['date_of_joining'] ?? null,
                'status' => $staff['status'] ?? null,
            ];
        }
    }

    /**
     * Format staff response with grouped data
     */
    private function formatStaffResponse(array $staff, string $staffId): array
    {
        try {
            // Get academic history
            $academicHistory = [];
            try {
                $academicHistory = $this->staffRepository->getStaffAcademicHistory($staffId);
            } catch (\Exception $e) {
                $this->logger->logAudit(
                    'get_academic_history_error',
                    'Error fetching academic history for ' . $staffId . ': ' . $e->getMessage()
                );
            }

            // Get appointment details
            $appointment = null;
            $assignedClasses = [];
            $assignedSubjects = [];
            $assignedRoles = [];
            
            try {
                $appointment = $this->staffRepository->getStaffAppointment($staffId);
            } catch (\Exception $e) {
                $this->logger->logAudit(
                    'get_appointment_error',
                    'Error fetching appointment for ' . $staffId . ': ' . $e->getMessage()
                );
            }
            
            try {
                $assignedClasses = $this->staffRepository->getStaffClasses($staffId);
            } catch (\Exception $e) {
                $this->logger->logAudit(
                    'get_classes_error',
                    'Error fetching classes for ' . $staffId . ': ' . $e->getMessage()
                );
            }
            
            try {
                $assignedSubjects = $this->staffRepository->getStaffSubjects($staffId);
            } catch (\Exception $e) {
                $this->logger->logAudit(
                    'get_subjects_error',
                    'Error fetching subjects for ' . $staffId . ': ' . $e->getMessage()
                );
            }
            
            try {
                $assignedRoles = $this->staffRepository->getStaffRoles($staffId);
            } catch (\Exception $e) {
                $this->logger->logAudit(
                    'get_roles_error',
                    'Error fetching roles for ' . $staffId . ': ' . $e->getMessage()
                );
            }

            // Group personal & contact info
            $personalContact = [
                'staff_id' => $staff['staff_id'] ?? null,
                'first_name' => $staff['first_name'] ?? null,
                'last_name' => $staff['last_name'] ?? null,
                'other_name' => $staff['other_name'] ?? null,
                'email' => $staff['email'] ?? null,
                'phone' => $staff['phone'] ?? null,
                'id_type' => $staff['id_type'] ?? null,
                'id_no' => $staff['id_no'] ?? null,
                'snnit_no' => $staff['snnit_no'] ?? null,
                'date_of_joining' => $staff['date_of_joining'] ?? null,
                'status' => $staff['status'] ?? null,
                'signature_id' => $staff['signature_id'] ?? null,
            ];

            // Group address info
            $address = [
                'country' => $staff['country'] ?? null,
                'city' => $staff['city'] ?? null,
                'hometown' => $staff['hometown'] ?? null,
                'residence' => $staff['residence'] ?? null,
                'house_no' => $staff['house_no'] ?? null,
                'gps_no' => $staff['gps_no'] ?? null,
            ];

            // Group appointment info
            $appointmentHistory = null;
            if ($appointment) {
                $appointmentHistory = [
                    'appointment_date' => $appointment['appointment_date'] ?? null,
                    'appointment_status' => $appointment['appointment_status'] ?? null,
                    'class_teacher_for' => $appointment['class_teacher_for'] ?? null,
                    'assigned_classes' => $assignedClasses,
                    'assigned_subjects' => $assignedSubjects,
                    'roles' => $assignedRoles,
                ];
            }

            return [
                'personal_contact' => $personalContact,
                'address' => $address,
                'academic_history' => $academicHistory,
                'appointment_history' => $appointmentHistory,
                'added_on' => $staff['added_on'] ?? null,
                'added_by' => $staff['added_by'] ?? null,
            ];
        } catch (\Exception $e) {
            // Log error and return basic staff info
            $this->logger->logAudit(
                'format_staff_response_error',
                'Critical error formatting staff response for ' . $staffId . ': ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString()
            );

            // Return basic info without failing
            return [
                'personal_contact' => [
                    'staff_id' => $staff['staff_id'] ?? null,
                    'first_name' => $staff['first_name'] ?? null,
                    'last_name' => $staff['last_name'] ?? null,
                    'other_name' => $staff['other_name'] ?? null,
                    'email' => $staff['email'] ?? null,
                    'phone' => $staff['phone'] ?? null,
                    'id_type' => $staff['id_type'] ?? null,
                    'id_no' => $staff['id_no'] ?? null,
                    'snnit_no' => $staff['snnit_no'] ?? null,
                    'date_of_joining' => $staff['date_of_joining'] ?? null,
                    'status' => $staff['status'] ?? null,
                    'signature_id' => $staff['signature_id'] ?? null,
                ],
                'address' => [
                    'country' => $staff['country'] ?? null,
                    'city' => $staff['city'] ?? null,
                    'hometown' => $staff['hometown'] ?? null,
                    'residence' => $staff['residence'] ?? null,
                    'house_no' => $staff['house_no'] ?? null,
                    'gps_no' => $staff['gps_no'] ?? null,
                ],
                'academic_history' => [],
                'appointment_history' => null,
                'added_on' => $staff['added_on'] ?? null,
                'added_by' => $staff['added_by'] ?? null,
                'error' => 'Some data could not be loaded'
            ];
        }
    }

    /**
     * Update staff member
     */
    public function updateStaff(string $staffId, array $data): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Check if staff exists
            $existingStaff = $this->staffRepository->getStaffById($staffId);
            if (!$existingStaff) {
                throw new \Exception('Staff not found');
            }

            // Validate required fields
            $this->validateStaffData($data);

            // Check for duplicate email (excluding current staff)
            if ($this->staffRepository->emailExists($data['email'], $staffId)) {
                throw new ValidationException(
                    ['email' => ['Email already exists']],
                    'Email already registered to another staff'
                );
            }

            // Check for duplicate ID number (excluding current staff)
            if ($this->staffRepository->idNumberExists($data['id_no'], $staffId)) {
                throw new ValidationException(
                    ['id_no' => ['ID number already exists']],
                    'ID number already registered to another staff'
                );
            }

            // Check for duplicate phone (excluding current staff)
            if ($this->staffRepository->phoneExists($data['phone'], $staffId)) {
                throw new ValidationException(
                    ['phone' => ['Phone number already exists']],
                    'Phone number already registered to another staff'
                );
            }

            // Check for duplicate SSNIT number (excluding current staff)
            if (!empty($data['snnit_no']) && $this->staffRepository->ssnitNoExists($data['snnit_no'], $staffId)) {
                throw new ValidationException(
                    ['ssnit_no' => ['SSNIT number already exists']],
                    'SSNIT number already registered to another staff'
                );
            }

            // Update staff DTO
            $staffDTO = StaffDTO::fromArray([
                'staff_id' => $staffId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'other_name' => $data['other_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'id_type' => $data['id_type'],
                'id_no' => $data['id_no'],
                'snnit_no' => $data['snnit_no'] ?? null,
                'date_of_joining' => $data['date_of_joining'] ?? date('Y-m-d'),
                'status' => $data['status'] ?? 'active',
                'added_by' => $data['added_by'] ?? 'system'
            ]);

            // Update staff record
            if (!$this->staffRepository->updateStaff($staffDTO)) {
                throw new \Exception('Failed to update staff record');
            }

            // Update staff address if provided
            if (!empty($data['address'])) {
                $addressDTO = StaffAddressDTO::fromArray([
                    'staff_id' => $staffId,
                    'country' => $data['address']['country'] ?? '',
                    'city' => $data['address']['city'] ?? '',
                    'hometown' => $data['address']['hometown'] ?? '',
                    'residence' => $data['address']['residence'] ?? '',
                    'house_no' => $data['address']['house_no'] ?? '',
                    'gps_no' => $data['address']['gps_no'] ?? ''
                ]);

                if (!$this->staffRepository->updateStaffAddress($addressDTO)) {
                    throw new \Exception('Failed to update staff address');
                }
            }

            // Update academic history if provided
            if (isset($data['academic_history']) && is_array($data['academic_history'])) {
                // Delete existing academic history
                $this->staffRepository->deleteStaffAcademicHistory($staffId);
                
                // Add new academic history records
                foreach ($data['academic_history'] as $history) {
                    if (!empty($history['school_name']) && !empty($history['qualification'])) {
                        $historyDTO = StaffAcademicHistoryDTO::fromArray([
                            'staff_id' => $staffId,
                            'school_name' => $history['school_name'] ?? '',
                            'program_offered' => $history['program_offered'] ?? '',
                            'qualification' => $history['qualification'] ?? '',
                            'year_completed' => $history['year_completed'] ?? ''
                        ]);

                        if (!$this->staffRepository->createStaffAcademicHistory($historyDTO)) {
                            throw new \Exception('Failed to update academic history');
                        }
                    }
                }
            }

            // Update appointment history if provided
            if (!empty($data['appointment'])) {
                // Update appointment record
                $appointmentDTO = StaffAppointmentDTO::fromArray([
                    'staff_id' => $staffId,
                    'appointment_date' => $data['appointment']['appointment_date'] ?? date('Y-m-d'),
                    'appointment_status' => $data['appointment']['appointment_status'] ?? 'appointed',
                    'class_teacher_for' => $data['appointment']['class_teacher_for'] ?? null,
                    'created_by' => $data['added_by']
                ]);

                // Delete and recreate appointment
                $this->staffRepository->deleteStaffAppointment($staffId);
                if (!$this->staffRepository->createStaffAppointment($appointmentDTO)) {
                    throw new \Exception('Failed to update appointment history');
                }

                // Update class assignments
                if (isset($data['appointment']['assigned_classes'])) {
                    $this->staffRepository->deleteStaffClasses($staffId);
                    
                    if (is_array($data['appointment']['assigned_classes'])) {
                        foreach ($data['appointment']['assigned_classes'] as $class) {
                            $classId = is_array($class) ? ($class['class_id'] ?? null) : $class;
                            if (!empty($classId)) {
                                if (!$this->staffRepository->assignClassToStaff($staffId, $classId, $data['added_by'])) {
                                    throw new \Exception('Failed to assign class: ' . $classId);
                                }
                            }
                        }
                    }
                }

                // Update subject assignments
                if (isset($data['appointment']['assigned_subjects'])) {
                    $this->staffRepository->deleteStaffSubjects($staffId);
                    
                    if (is_array($data['appointment']['assigned_subjects'])) {
                        foreach ($data['appointment']['assigned_subjects'] as $subject) {
                            if (!empty($subject['subject_id']) && !empty($subject['class_id'])) {
                                if (!$this->staffRepository->assignSubjectToStaff(
                                    $staffId,
                                    $subject['subject_id'],
                                    $subject['class_id'],
                                    $data['added_by']
                                )) {
                                    throw new \Exception('Failed to assign subject: ' . $subject['subject_id']);
                                }
                            }
                        }
                    }
                }

                // Update role assignments
                if (isset($data['appointment']['roles'])) {
                    $this->staffRepository->deleteStaffRoles($staffId);
                    
                    if (is_array($data['appointment']['roles'])) {
                        foreach ($data['appointment']['roles'] as $roleId) {
                            if (!empty($roleId)) {
                                if (!$this->staffRepository->assignRoleToStaff($staffId, (int)$roleId)) {
                                    throw new \Exception('Failed to assign role: ' . $roleId);
                                }
                            }
                        }
                    }
                }
            }

            $db->commit();

            $this->logger->logAudit(
                'staff_update',
                'Staff updated successfully: ' . $staffId,
                $data['added_by'] ?? 'system'
            );

            return [
                'staff_id' => $staffId,
                'email' => $data['email'],
                'message' => 'Staff updated successfully'
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $this->logger->logAudit(
                'staff_update_error',
                'Staff update failed: ' . $e->getMessage(),
                $data['added_by'] ?? 'unknown'
            );

            throw $e;
        }
    }
    /**
     * Activate or deactivate staff member
     */
    public function toggleStaffStatus(string $staffId, string $status, string $reason = null, string $updatedBy = 'system'): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Check if staff exists
            $staff = $this->staffRepository->getStaffById($staffId);
            if (!$staff) {
                throw new \Exception('Staff not found');
            }

            // Validate status
            $validStatuses = ['active', 'inactive', 'suspended', 'terminated'];
            if (!in_array($status, $validStatuses)) {
                throw new ValidationException(
                    ['status' => ['Invalid status. Must be: active, inactive, suspended, or terminated']],
                    'Invalid status'
                );
            }

            // Update staff status
            if (!$this->staffRepository->updateStaffStatus($staffId, $status)) {
                throw new \Exception('Failed to update staff status');
            }

            // Update user account status if exists
            $this->staffRepository->updateUserStatus($staffId, $status === 'active' ? 'active' : 'inactive');

            // Log the status change
            $this->staffRepository->logStatusChange($staffId, $staff['status'], $status, $reason, $updatedBy);

            $db->commit();

            $this->logger->logAudit(
                'staff_status_change',
                "Staff {$staffId} status changed from {$staff['status']} to {$status}",
                $updatedBy
            );

            return [
                'staff_id' => $staffId,
                'old_status' => $staff['status'],
                'new_status' => $status,
                'message' => "Staff status updated to {$status}"
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $this->logger->logAudit(
                'staff_status_change_error',
                'Staff status change failed: ' . $e->getMessage(),
                $updatedBy
            );

            throw $e;
        }
    }

    /**
     * Delete staff member (soft delete by archiving)
     */
    public function deleteStaff(string $staffId, string $reason = null, string $deletedBy = 'system'): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Check if staff exists
            $staff = $this->staffRepository->getStaffById($staffId);
            if (!$staff) {
                throw new \Exception('Staff not found');
            }

            // Archive the staff (soft delete)
            if (!$this->staffRepository->archiveStaff($staffId, $reason, $deletedBy)) {
                throw new \Exception('Failed to archive staff');
            }

            // Deactivate user account
            $this->staffRepository->updateUserStatus($staffId, 'inactive');

            $db->commit();

            $this->logger->logAudit(
                'staff_deleted',
                "Staff {$staffId} archived/deleted by {$deletedBy}. Reason: " . ($reason ?? 'Not specified'),
                $deletedBy
            );

            return [
                'staff_id' => $staffId,
                'message' => 'Staff archived successfully'
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $this->logger->logAudit(
                'staff_delete_error',
                'Staff deletion failed: ' . $e->getMessage(),
                $deletedBy
            );

            throw $e;
        }
    }

    /**
     * Permanently delete staff member (hard delete)
     * This will cascade delete all related records due to foreign keys
     */
    public function permanentlyDeleteStaff(string $staffId, string $deletedBy = 'system'): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Check if staff exists
            $staff = $this->staffRepository->getStaffById($staffId);
            if (!$staff) {
                throw new \Exception('Staff not found');
            }

            // Permanently delete (CASCADE will handle related records)
            if (!$this->staffRepository->permanentlyDeleteStaff($staffId)) {
                throw new \Exception('Failed to permanently delete staff');
            }

            $db->commit();

            $this->logger->logAudit(
                'staff_permanently_deleted',
                "Staff {$staffId} permanently deleted by {$deletedBy}",
                $deletedBy
            );

            return [
                'staff_id' => $staffId,
                'message' => 'Staff permanently deleted successfully'
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $this->logger->logAudit(
                'staff_permanent_delete_error',
                'Staff permanent deletion failed: ' . $e->getMessage(),
                $deletedBy
            );

            throw $e;
        }
    }

    /**
     * Get staff by filter (role, class, subject, or search)
     */
    public function getStaffByFilter(array $filters, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $status = $filters['status'] ?? 'active';
        
        // Determine which filter to apply
        if (!empty($filters['role_id'])) {
            $staffList = $this->staffRepository->getStaffByRole((int)$filters['role_id'], $limit, $offset, $status);
            $total = $this->staffRepository->countStaffByRole((int)$filters['role_id'], $status);
        } elseif (!empty($filters['class_id'])) {
            $staffList = $this->staffRepository->getStaffByClass($filters['class_id'], $limit, $offset, $status);
            $total = $this->staffRepository->countStaffByClass($filters['class_id'], $status);
        } elseif (!empty($filters['subject_id'])) {
            $staffList = $this->staffRepository->getStaffBySubject($filters['subject_id'], $limit, $offset, $status);
            $total = $this->staffRepository->countStaffBySubject($filters['subject_id'], $status);
        } elseif (!empty($filters['search'])) {
            $staffList = $this->staffRepository->searchStaff($filters['search'], $limit, $offset, $status);
            $total = $this->staffRepository->countSearchResults($filters['search'], $status);
        } else {
            // No filter, return all staff
            $staffList = $this->staffRepository->getAllStaff($limit, $offset, $status);
            $total = $this->staffRepository->countStaff($status);
        }

        // Format each staff record with simplified data
        $formattedStaff = [];
        foreach ($staffList as $staff) {
            $formattedStaff[] = $this->formatStaffListItem($staff, $staff['staff_id']);
        }

        return [
            'data' => $formattedStaff,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ],
            'filters' => $filters
        ];
    }

    /**
     * Assign classes to staff
     */
    public function assignClasses(string $staffId, array $classIds, string $assignedBy): array
    {
        // Validate staff exists
        $staff = $this->staffRepository->getStaffById($staffId);
        if (!$staff) {
            throw new NotFoundException("Staff not found");
        }

        // Validate classes exist and check for duplicates
        $newAssignments = [];
        $alreadyAssigned = [];
        
        foreach ($classIds as $classId) {
            if ($this->staffRepository->isClassAssigned($staffId, $classId)) {
                $alreadyAssigned[] = $classId;
            } else {
                $newAssignments[] = $classId;
            }
        }

        // Assign new classes
        if (!empty($newAssignments)) {
            $this->staffRepository->assignClasses($staffId, $newAssignments, $assignedBy);
        }

        return [
            'assigned' => $newAssignments,
            'already_assigned' => $alreadyAssigned,
            'total_assigned' => count($newAssignments)
        ];
    }

    /**
     * Assign subjects to staff for specific classes
     */
    public function assignSubjects(string $staffId, array $assignments, string $assignedBy): array
    {
        // Validate staff exists
        $staff = $this->staffRepository->getStaffById($staffId);
        if (!$staff) {
            throw new NotFoundException("Staff not found");
        }

        // Validate and filter assignments
        $newAssignments = [];
        $alreadyAssigned = [];
        
        foreach ($assignments as $assignment) {
            $subjectId = $assignment['subject_id'];
            $classId = $assignment['class_id'];
            
            if ($this->staffRepository->isSubjectAssigned($staffId, $subjectId, $classId)) {
                $alreadyAssigned[] = "{$subjectId} for {$classId}";
            } else {
                $newAssignments[] = $assignment;
            }
        }

        // Assign new subjects
        if (!empty($newAssignments)) {
            $this->staffRepository->assignSubjects($staffId, $newAssignments, $assignedBy);
        }

        return [
            'assigned' => $newAssignments,
            'already_assigned' => $alreadyAssigned,
            'total_assigned' => count($newAssignments)
        ];
    }

    /**
     * Get staff assignments (classes and subjects)
     */
    public function getStaffAssignments(string $staffId): array
    {
        // Validate staff exists
        $staff = $this->staffRepository->getStaffById($staffId);
        if (!$staff) {
            throw new NotFoundException("Staff not found");
        }

        $classes = $this->staffRepository->getStaffClassAssignments($staffId);
        $subjects = $this->staffRepository->getStaffSubjectAssignments($staffId);

        // Group subjects by class
        $subjectsByClass = [];
        foreach ($subjects as $subject) {
            $classId = $subject['class_id'];
            if (!isset($subjectsByClass[$classId])) {
                $subjectsByClass[$classId] = [
                    'class_id' => $classId,
                    'class_name' => $subject['class_name'],
                    'subjects' => []
                ];
            }
            $subjectsByClass[$classId]['subjects'][] = [
                'subject_id' => $subject['subject_id'],
                'subject_name' => $subject['subject_name'],
                'assigned_by' => $subject['assigned_by_name'],
                'assigned_on' => $subject['assigned_on']
            ];
        }

        return [
            'staff_id' => $staffId,
            'staff_name' => $staff['first_name'] . ' ' . $staff['last_name'],
            'classes' => $classes,
            'subjects_by_class' => array_values($subjectsByClass),
            'total_classes' => count($classes),
            'total_subjects' => count($subjects)
        ];
    }

    /**
     * Remove class assignment
     */
    public function removeClassAssignment(string $staffId, string $classId): bool
    {
        // Validate staff exists
        $staff = $this->staffRepository->getStaffById($staffId);
        if (!$staff) {
            throw new NotFoundException("Staff not found");
        }

        // Remove all subject assignments for this class first
        $this->staffRepository->removeSubjectAssignmentsByClass($staffId, $classId);

        // Then remove the class assignment
        return $this->staffRepository->removeClassAssignment($staffId, $classId);
    }

    /**
     * Remove subject assignment
     */
    public function removeSubjectAssignment(string $staffId, string $subjectId, string $classId): bool
    {
        // Validate staff exists
        $staff = $this->staffRepository->getStaffById($staffId);
        if (!$staff) {
            throw new NotFoundException("Staff not found");
        }

        return $this->staffRepository->removeSubjectAssignment($staffId, $subjectId, $classId);
    }

}
