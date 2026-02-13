<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$db = Database::getInstance()->getConnection();

$gs = $db->query('SELECT DISTINCT added_by FROM grading_scheme')->fetchAll(PDO::FETCH_COLUMN);
$u = $db->query('SELECT DISTINCT username FROM users')->fetchAll(PDO::FETCH_COLUMN);

echo "Grading Scheme Added By values:\n";
foreach ($gs as $val) {
    echo "- '$val'\n";
}

echo "\nUsers Username values:\n";
foreach ($u as $val) {
    echo "- '$val'\n";
}

$missing = array_diff($gs, $u);
if (!empty($missing)) {
    echo "\nValues in grading_scheme.added_by NOT in users.username:\n";
    foreach ($missing as $m) {
        echo "- '$m'\n";
    }
} else {
    echo "\nNo data mismatch found. The failure might be due to duplicate usernames or something else.\n";
}
