<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\DTOs\ProfileUpdateDTO;
use App\Models\User;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthException;
use App\Exceptions\ConflictException;
use App\Core\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service for handling user profile operations
 */
class ProfileService
{
    private Database $database;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->database = Database::getInstance();
        $this->logger = LoggerFactory::getInstance();
    }

    /**
     * Update user profile
     *
     * @param int $userId User ID
     * @param ProfileUpdateDTO $profileData Profile update data
     * @return array Updated user data
     * @throws ValidationException
     * @throws AuthException
     * @throws ConflictException
     */
    public function updateProfile(int $userId, ProfileUpdateDTO $profileData): array
    {
        // Validate the input data
        $validationErrors = $profileData->validate();
        if (!empty($validationErrors)) {
            $this->logger->warning('Profile update validation failed', [
                'user_id' => $userId,
                'errors' => $validationErrors
            ]);
            throw new ValidationException($validationErrors, 'Validation failed');
        }

        $pdo = $this->database->getConnection();
        
        try {
            $pdo->beginTransaction();

            // Get current user data
            $currentUser = $this->getCurrentUser($userId);
            if (!$currentUser) {
                throw new AuthException('User not found');
            }

            $updateData = [];
            $logContext = ['user_id' => $userId];

            // Get profile update data
            if ($profileData->hasUpdates()) {
                $profileUpdateData = $profileData->getProfileData();
                
                // Check for conflicts
                $this->checkForConflicts($userId, $profileUpdateData);
                
                $updateData = array_merge($updateData, $profileUpdateData);
                $logContext['profile_updates'] = array_keys($profileUpdateData);
            }

            // Perform the update
            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->updateUserData($userId, $updateData);
                
                $this->logger->info('User profile updated successfully', $logContext);
            }

            // Get updated user data
            $updatedUser = $this->getCurrentUser($userId);
            
            // Update session if this is the current user
            $sessionUserId = Session::get('user')['id'] ?? null;
            if ($sessionUserId == $userId) {
                $this->updateSession($updatedUser);
            }

            $pdo->commit();

            // Return sanitized user data (no password)
            return $this->sanitizeUserData($updatedUser);

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            $this->logger->error('Profile update failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get current user profile
     *
     * @param int $userId User ID
     * @return array User profile data
     * @throws AuthException
     */
    public function getProfile(int $userId): array
    {
        try {
            $user = $this->getCurrentUser($userId);
            
            if (!$user) {
                throw new AuthException('User not found');
            }

            return $this->sanitizeUserData($user);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Change user password (admin function)
     *
     * @param int $userId User ID
     * @param string $newPassword New password
     * @param int $adminUserId Admin user ID
     * @return bool Success status
     * @throws AuthException
     */
    public function changePassword(int $userId, string $newPassword, int $adminUserId): bool
    {
        // Validate password strength
        if (strlen($newPassword) < 8) {
            throw new ValidationException(['password' => ['Password must be at least 8 characters long']]);
        }

        $pdo = $this->database->getConnection();
        
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateData = [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->updateUserData($userId, $updateData);

            $this->logger->warning('Password changed by admin', [
                'target_user_id' => $userId,
                'admin_user_id' => $adminUserId
            ]);

            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            $this->logger->error('Admin password change failed', [
                'target_user_id' => $userId,
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get current user data from database
     *
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    private function getCurrentUser(int $userId): ?array
    {
        $pdo = $this->database->getConnection();
        
        // Try to query with profile_picture_id first
        try {
            $sql = "
                SELECT 
                    u.*,
                    r.name as role_name,
                    up.id as profile_picture_upload_id,
                    up.doc_name as profile_picture_name,
                    up.url as profile_picture_url,
                    up.file_type as profile_picture_type,
                    up.file_size as profile_picture_size,
                    up.uploaded_at as profile_picture_uploaded_at,
                    sig.id as signature_upload_id,
                    sig.doc_id as signature_doc_id,
                    sig.url as signature_url,
                    sig.file_type as signature_type,
                    CASE 
                        WHEN LOWER(r.name) = 'student' THEN CONCAT(st.first_name, ' ', COALESCE(st.other_name, ''), ' ', st.last_name)
                        ELSE CONCAT(sf.first_name, ' ', COALESCE(sf.other_name, ''), ' ', sf.last_name)
                    END as full_name_from_table,
                    CASE 
                        WHEN LOWER(r.name) = 'student' THEN sc.phone
                        ELSE sf.phone
                    END as phone_from_table
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                LEFT JOIN uploads up ON u.profile_picture_id = up.doc_id
                LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
                LEFT JOIN student_contact sc ON u.user_id = sc.student_no AND LOWER(r.name) = 'student'
                LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
                LEFT JOIN uploads sig ON sf.signature_id = sig.doc_id AND LOWER(r.name) != 'student'
                WHERE u.id = ?
            ";

            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Use full_name from students/staff table if available, otherwise use from users table
            if ($user) {
                if (!empty($user['full_name_from_table'])) {
                    $user['full_name'] = trim($user['full_name_from_table']);
                }
                if (!empty($user['phone_from_table'])) {
                    $user['phone'] = $user['phone_from_table'];
                }
                // Clean up temporary fields
                unset($user['full_name_from_table']);
                unset($user['phone_from_table']);
            }
            
            return $user ?: null;
            
        } catch (\PDOException $e) {
            // If profile_picture_id column doesn't exist, fall back to query without it
            $this->logger->debug('Profile picture column not found, using fallback query', [
                'error' => $e->getMessage()
            ]);
            
            try {
                $sql = "
                    SELECT 
                        u.*, 
                        r.name as role_name,
                        sig.id as signature_upload_id,
                        sig.doc_id as signature_doc_id,
                        sig.url as signature_url,
                        sig.file_type as signature_type,
                        CASE 
                            WHEN LOWER(r.name) = 'student' THEN CONCAT(st.first_name, ' ', COALESCE(st.other_name, ''), ' ', st.last_name)
                            ELSE CONCAT(sf.first_name, ' ', COALESCE(sf.other_name, ''), ' ', sf.last_name)
                        END as full_name_from_table,
                        CASE 
                            WHEN LOWER(r.name) = 'student' THEN sc.phone
                            ELSE sf.phone
                        END as phone_from_table
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.role_id 
                    LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
                    LEFT JOIN student_contact sc ON u.user_id = sc.student_no AND LOWER(r.name) = 'student'
                    LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
                    LEFT JOIN uploads sig ON sf.signature_id = sig.doc_id AND LOWER(r.name) != 'student'
                    WHERE u.id = ?
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId]);
                
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Use full_name from students/staff table if available, otherwise use from users table
                if ($user) {
                    if (!empty($user['full_name_from_table'])) {
                        $user['full_name'] = trim($user['full_name_from_table']);
                    }
                    if (!empty($user['phone_from_table'])) {
                        $user['phone'] = $user['phone_from_table'];
                    }
                    // Clean up temporary fields
                    unset($user['full_name_from_table']);
                    unset($user['phone_from_table']);
                }
                
                return $user ?: null;
                
            } catch (\PDOException $e2) {
                // If signature_id column also doesn't exist, use minimal query
                $this->logger->debug('Signature column not found, using minimal query', [
                    'error' => $e2->getMessage()
                ]);
                
                $sql = "
                    SELECT 
                        u.*, 
                        r.name as role_name,
                        CASE 
                            WHEN LOWER(r.name) = 'student' THEN CONCAT(st.first_name, ' ', COALESCE(st.other_name, ''), ' ', st.last_name)
                            ELSE CONCAT(sf.first_name, ' ', COALESCE(sf.other_name, ''), ' ', sf.last_name)
                        END as full_name_from_table,
                        CASE 
                            WHEN LOWER(r.name) = 'student' THEN sc.phone
                            ELSE sf.phone
                        END as phone_from_table
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.role_id 
                    LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
                    LEFT JOIN student_contact sc ON u.user_id = sc.student_no AND LOWER(r.name) = 'student'
                    LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
                    WHERE u.id = ?
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId]);
                
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Use full_name from students/staff table if available, otherwise use from users table
                if ($user) {
                    if (!empty($user['full_name_from_table'])) {
                        $user['full_name'] = trim($user['full_name_from_table']);
                    }
                    if (!empty($user['phone_from_table'])) {
                        $user['phone'] = $user['phone_from_table'];
                    }
                    // Clean up temporary fields
                    unset($user['full_name_from_table']);
                    unset($user['phone_from_table']);
                }
                
                return $user ?: null;
            }
        }
    }

    /**
     * Check for username/email conflicts
     *
     * @param int $userId Current user ID
     * @param array $updateData Update data to check
     * @throws ConflictException
     */
    private function checkForConflicts(int $userId, array $updateData): void
    {
        $pdo = $this->database->getConnection();

        // Check username conflict
        if (isset($updateData['username'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$updateData['username'], $userId]);
            
            if ($stmt->fetch()) {
                throw new ConflictException('Username is already taken');
            }
        }

        // Check email conflict
        if (isset($updateData['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$updateData['email'], $userId]);
            
            if ($stmt->fetch()) {
                throw new ConflictException('Email address is already in use');
            }
        }
    }

    /**
     * Update user data in database
     *
     * @param int $userId User ID
     * @param array $updateData Data to update
     * @return bool Success status
     */
    private function updateUserData(int $userId, array $updateData): bool
    {
        if (empty($updateData)) {
            return true;
        }

        $pdo = $this->database->getConnection();
        
        $fields = array_keys($updateData);
        $placeholders = array_map(fn($field) => "{$field} = ?", $fields);
        $sql = "UPDATE users SET " . implode(', ', $placeholders) . " WHERE id = ?";
        
        $values = array_values($updateData);
        $values[] = $userId;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Update session with new user data
     *
     * @param array $userData Updated user data
     * @return void
     */
    private function updateSession(array $userData): void
    {
        $sessionData = $this->sanitizeUserData($userData);
        Session::set('user', $sessionData);
        
        $this->logger->debug('Session updated with new user data', [
            'user_id' => $userData['id']
        ]);
    }

    /**
     * Remove sensitive data from user array
     *
     * @param array $userData Raw user data
     * @return array Sanitized user data
     */
    private function sanitizeUserData(array $userData): array
    {
        // Remove sensitive fields
        unset($userData['password']);
        unset($userData['failed_login_attempts']);
        unset($userData['locked_until']);
        unset($userData['locked_by_admin']);

        // Build profile picture object if exists
        $profilePicture = null;
        if (isset($userData['profile_picture_id']) && !empty($userData['profile_picture_id'])) {
            // Generate full URL for profile picture
            $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            $appUrl = rtrim($appUrl, '/');
            
            // If we have the upload_id, generate the full URL to the public file serving endpoint
            // Use public endpoint so images can be displayed without authentication
            $fullUrl = null;
            if (isset($userData['profile_picture_upload_id']) && !empty($userData['profile_picture_upload_id'])) {
                $fullUrl = $appUrl . '/api/v1/uploads/public/' . $userData['profile_picture_upload_id'];
            }
            
            $profilePicture = [
                'doc_id' => $userData['profile_picture_id'],
                'upload_id' => isset($userData['profile_picture_upload_id']) ? (int)$userData['profile_picture_upload_id'] : null,
                'name' => $userData['profile_picture_name'] ?? null,
                'url' => $fullUrl,
                'type' => $userData['profile_picture_type'] ?? null,
                'size' => isset($userData['profile_picture_size']) ? (int)$userData['profile_picture_size'] : null,
                'uploaded_at' => $userData['profile_picture_uploaded_at'] ?? null,
            ];
        }

        // Build signature object with base64 data if exists (staff only)
        $signature = null;
        if (isset($userData['signature_url']) && !empty($userData['signature_url'])) {
            $signatureBase64 = null;
            
            // Convert signature file to base64
            try {
                $storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;
                $signaturePath = $storagePath . str_replace('/', DIRECTORY_SEPARATOR, $userData['signature_url']);
                
                if (file_exists($signaturePath)) {
                    $signatureContent = file_get_contents($signaturePath);
                    $signatureBase64 = base64_encode($signatureContent);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load signature file', [
                    'user_id' => $userData['id'],
                    'signature_url' => $userData['signature_url'],
                    'error' => $e->getMessage()
                ]);
            }
            
            $signature = [
                'doc_id' => $userData['signature_doc_id'] ?? null,
                'upload_id' => isset($userData['signature_upload_id']) ? (int)$userData['signature_upload_id'] : null,
                'type' => $userData['signature_type'] ?? null,
                'base64' => $signatureBase64,
            ];
        }

        // Remove profile picture and signature fields from main array
        unset($userData['profile_picture_upload_id']);
        unset($userData['profile_picture_name']);
        unset($userData['profile_picture_url']);
        unset($userData['profile_picture_type']);
        unset($userData['profile_picture_size']);
        unset($userData['profile_picture_uploaded_at']);
        unset($userData['signature_upload_id']);
        unset($userData['signature_doc_id']);
        unset($userData['signature_url']);
        unset($userData['signature_type']);

        $result = [
            'id' => (int)$userData['id'],
            'user_id' => $userData['user_id'],
            'username' => $userData['username'],
            'full_name' => $userData['full_name'] ?? null,
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? null,
            'role_id' => $userData['role_id'] ? (int)$userData['role_id'] : null,
            'role_name' => $userData['role_name'] ?? null,
            'status' => $userData['status'],
            'is_super_admin' => (bool)$userData['is_super_admin'],
            'created_at' => $userData['created_at'],
            'updated_at' => $userData['updated_at'],
        ];

        // Only add profile_picture fields if the column exists in the database
        if (isset($userData['profile_picture_id'])) {
            $result['profile_picture_id'] = $userData['profile_picture_id'];
            $result['profile_picture'] = $profilePicture;
        }

        // Add signature for staff users
        if ($signature !== null) {
            $result['signature'] = $signature;
        }

        return $result;
    }

    /**
     * Validate user permissions for profile update
     *
     * @param int $targetUserId User being updated
     * @param int $currentUserId User performing the update
     * @return bool True if allowed
     * @throws AuthException
     */
    public function validateUpdatePermissions(int $targetUserId, int $currentUserId): bool
    {
        // Users can always update their own profile
        if ($targetUserId === $currentUserId) {
            return true;
        }

        // Check if current user is admin (for future admin functionality)
        $currentUser = $this->getCurrentUser($currentUserId);
        if ($currentUser && $currentUser['is_super_admin']) {
            return true;
        }

        throw new AuthException('You do not have permission to update this profile');
    }

    /**
     * Update user profile image
     *
     * @param int $userId User ID
     * @param int $uploadId Upload ID from uploads table
     * @return array Updated user data
     * @throws AuthException
     */
    public function updateProfileImage(int $userId, int $uploadId): array
    {
        $pdo = $this->database->getConnection();
        
        try {
            $pdo->beginTransaction();

            // Verify upload exists and is a profile picture
            $stmt = $pdo->prepare("SELECT id, doc_type FROM uploads WHERE id = ?");
            $stmt->execute([$uploadId]);
            $upload = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$upload) {
                throw new AuthException('Upload not found');
            }

            // Update user's profile image
            $updateData = [
                'profile_image_id' => $uploadId,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->updateUserData($userId, $updateData);

            $this->logger->info('User profile image updated', [
                'user_id' => $userId,
                'upload_id' => $uploadId
            ]);

            // Get updated user data
            $updatedUser = $this->getCurrentUser($userId);
            
            // Update session if this is the current user
            $sessionUserId = Session::get('user')['id'] ?? null;
            if ($sessionUserId == $userId) {
                $this->updateSession($updatedUser);
            }

            $pdo->commit();

            return $this->sanitizeUserData($updatedUser);

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            $this->logger->error('Profile image update failed', [
                'user_id' => $userId,
                'upload_id' => $uploadId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Remove user profile image
     *
     * @param int $userId User ID
     * @return array Updated user data
     * @throws AuthException
     */
    public function removeProfileImage(int $userId): array
    {
        $pdo = $this->database->getConnection();
        
        try {
            $pdo->beginTransaction();

            // Update user's profile image to null
            $updateData = [
                'profile_image_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->updateUserData($userId, $updateData);

            $this->logger->info('User profile image removed', [
                'user_id' => $userId
            ]);

            // Get updated user data
            $updatedUser = $this->getCurrentUser($userId);
            
            // Update session if this is the current user
            $sessionUserId = Session::get('user')['id'] ?? null;
            if ($sessionUserId == $userId) {
                $this->updateSession($updatedUser);
            }

            $pdo->commit();

            return $this->sanitizeUserData($updatedUser);

        } catch (\Exception $e) {
            $pdo->rollBack();
            
            $this->logger->error('Profile image removal failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}