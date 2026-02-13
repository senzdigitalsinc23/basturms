<?php
require 'vendor/autoload.php';

use App\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();
    
    $apiKey = bin2hex(random_bytes(32)); // Generate secure random key
    $owner = 'system';
    $scopes = json_encode(['students:read', 'students:write', 'students:upload']);
    
    // Insert or update
    $stmt = $pdo->prepare("
        INSERT INTO api_keys (key_value, owner, scopes, active, created_at) 
        VALUES (?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ");
    $stmt->execute([$apiKey, $owner, $scopes]);
    
    echo "✅ API Key created!\n";
    echo "Key: {$apiKey}\n";
    echo "Scopes: {$scopes}\n";
    echo "Use in header: X-API-KEY: {$apiKey}\n";
    echo "Or query: ?api_key={$apiKey}\n";
    echo "For /api/v1/students/upload\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
