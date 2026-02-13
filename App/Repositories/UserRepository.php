<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Cache;
use App\Core\Logger;
use App\DTOs\UserDTO;
use PDO;
use PDOException;

class UserRepository
{
    private PDO $db;
    private Cache $cache;
    private Logger $logger;
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = new Cache();
        $this->logger = new Logger(__DIR__ . '/../../storage/logs');
    }

    public function create(array $userData): ?UserDTO
    {
        try {
            $sql = "INSERT INTO users (user_id, username, email, password, role_id, status, created_at, updated_at) 
                    VALUES (:user_id, :username, :email, :password, :role_id, :status, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                'user_id' => $userData['user_id'] ?? uniqid('user_'),
                'username' => $userData['username'] ?? $userData['email'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role_id' => $userData['role_id'] ?? 1,
                'status' => $userData['status'] ?? 'active'
            ]);

            if ($success) {
                $id = (int) $this->db->lastInsertId();
                return $this->findById($id);
            }

            return null;
        } catch (PDOException $e) {
            throw new \Exception("Database error creating user: " . $e->getMessage());
        }
    }

    public function getRole(int $role_id): ?UserDTO
    {
        $sql = "SELECT name FROM roles WHERE role_id = :role_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role_id' => $role_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? UserDTO::fromArray($result) : null;
    }

    public function findById(int $id): ?UserDTO
    {
        $cacheKey = "user:id:{$id}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached instanceof UserDTO ? $cached : UserDTO::fromArray($cached);
        }
        
        $sql = "SELECT users.*, roles.name as role_name FROM users LEFT JOIN roles ON users.role_id = roles.role_id WHERE users.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $userDTO = $result ? UserDTO::fromArray($result) : null;
        
        if ($userDTO) {
            $this->cache->set($cacheKey, $userDTO->toArray(), self::CACHE_TTL);
        }
        
        return $userDTO;
    }

    public function findByEmail(string $email): ?UserDTO
    {
        $cacheKey = "user:email:" . md5($email);

        // Temporarily disable cache to prevent stale data issues
        // $cached = $this->cache->get($cacheKey);
        // if ($cached !== null) {
        //     return $cached instanceof UserDTO ? $cached : UserDTO::fromArray($cached);
        // }
        
        $sql = "SELECT users.*, roles.name as role_name FROM users LEFT JOIN roles ON users.role_id = roles.role_id WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
//echo json_encode($result);exit;
        $userDTO = $result ? UserDTO::fromArray($result) : null;

        // Temporarily disable cache setting
        // if ($userDTO) {
        //     $this->cache->set($cacheKey, $userDTO->toArray(), self::CACHE_TTL);
        // }

        return $userDTO;
    }

    /**
     * Clear user cache by email
     */
    public function clearUserCache(string $email): void
    {
        $cacheKey = "user:email:" . md5($email);
        $this->cache->forget($cacheKey);
        $this->logger->info("UserRepository: Cleared cache for email {$email}");
    }

    public function findByUserId(string $userId): ?UserDTO
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? UserDTO::fromArray($result) : null;
    }

    public function getAllUsers(): array
    {
        $sql = "SELECT u.*, r.name as role_name, stu.last_name, stu.first_name, stu.other_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN students stu ON u.user_id = stu.student_no
                ORDER BY u.user_id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
//echo json_encode(['success' => true, 'message' => 'djdkjflskdjlkf']);exit;
        return array_map(fn($user) => UserDTO::fromArray($user), $results);
    }

    public function updateUser(int $id, array $data): bool
    {
        $allowedFields = ['username', 'email', 'status', 'role_id'];
        $updateFields = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            // Clear user caches
            $this->cache->forget("user:id:{$id}");
            // Also clear email cache if email was updated
            if (isset($data['email'])) {
                $user = $this->findById($id);
                if ($user) {
                    $oldEmailCacheKey = "user:email:" . md5($data['email']);
                    $this->cache->forget($oldEmailCacheKey);
                }
            }
        }
        
        return $success;
    }

    public function deleteUser(int $id): bool
    {
        $sql = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updatePassword(string $id, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['password' => $hashedPassword, 'user_id' => $id]);
    }

    /**
     * Update failed login attempts counter.
     *
     * @param int $id User ID
     * @param int $attempts Number of failed attempts
     * @return bool
     */
    public function updateFailedAttempts(string $id, int $attempts): bool
    {
        $sql = "UPDATE users SET failed_login_attempts = :attempts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['attempts' => $attempts, 'id' => $id]);
    }

    /**
     * Reset failed login attempts to zero.
     *
     * @param int $id User ID
     * @return bool
     */
    public function resetFailedAttempts(int $id): bool
    {
        $sql = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lock an account until a specific datetime.
     *
     * @param int $id User ID
     * @param string $lockedUntil Datetime string
     * @return bool
     */
    public function lockAccount(int $id, string $lockedUntil): bool
    {
        $sql = "UPDATE users SET locked_until = :locked_until, failed_login_attempts = 5 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['locked_until' => $lockedUntil, 'id' => $id]);
    }

    /**
     * Unlock an account (admin function).
     *
     * @param int $id User ID
     * @return bool
     */
    public function unlockAccount(int $id): bool
    {
        $sql = "UPDATE users SET locked_until = NULL, failed_login_attempts = 0, locked_by_admin = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
