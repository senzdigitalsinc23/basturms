<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class ChangePasswordController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userId = $user['user_id'] ?? null;

            if (!$userId) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $data = $request->getPost();
            $currentPassword = $data['current_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'All fields are required'], 400);
            }

            if ($newPassword !== $confirmPassword) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'New passwords do not match'], 400);
            }

            if (strlen($newPassword) < 6) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
            }

            // Fetch current password hash
            $stmt = $this->db->prepare("SELECT password FROM validation_staff WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$staff || !password_verify($currentPassword, $staff['password'])) {
                return $this->jsonResponse($response, ['success' => false, 'message' => 'Current password is incorrect'], 400);
            }

            // Update password and mark as changed
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmt = $this->db->prepare("
                UPDATE validation_staff 
                SET password = :password, password_changed = 1, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['password' => $newHash, 'id' => $userId]);

            return $this->jsonResponse($response, ['success' => true, 'message' => 'Password changed successfully']);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
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
