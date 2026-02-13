<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$migrationsPath = __DIR__ . '/Database/migrations';
$files = scandir($migrationsPath);

// Create migrations table if not exists
$db->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        batch INT NOT NULL,
        migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=INNODB;
");

$stmt = $db->query("SELECT migration FROM migrations");
$applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

$batch = 1;

foreach ($files as $file) {
    if (str_ends_with($file, '.php') && !in_array($file, $applied)) {
        if ($file === '20251221000000_fix_database_integrity.php') {
            echo "Skipping new migration: $file\n";
            continue;
        }
        
        echo "Marking as applied: $file\n";
        $stmtInsert = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
        $stmtInsert->execute(['migration' => $file, 'batch' => $batch]);
    }
}

echo "Sync completed.\n";
