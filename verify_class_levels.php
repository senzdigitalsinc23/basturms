<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM class_levels ORDER BY `rank` ASC");
$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total levels found: " . count($levels) . "\n\n";

if (count($levels) > 0) {
    printf("%-20s %-20s %-10s %-5s\n", "Name", "Category", "Code", "Rank");
    echo str_repeat("-", 60) . "\n";
    foreach ($levels as $level) {
        printf("%-20s %-20s %-10s %-5d\n",
            substr($level['category'], 0, 20), 
            $level['level_code'], 
            $level['rank']
        );
    }
} else {
    echo "No class levels found! Migration might have failed.\n";
}
