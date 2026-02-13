<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();

echo "Testing query...\n";

// Base query without suspicious joins
$sql = "
    SELECT 
        ssr.id, ssr.student_no, ssr.subject_id, ssr.class_id, ssr.academic_year, ssr.term, ssr.assignment_activity_id, ssr.total_score
        -- , s.first_name
        -- , subj.subject_name
        -- , c.class_name
        -- , aa.act_name
    FROM student_summary_report ssr
    -- JOIN students s ON ssr.student_no = s.student_no
    -- JOIN subjects subj ON ssr.subject_id = subj.id
    -- JOIN classes c ON ssr.class_id = c.id
    -- JOIN assignment_activities aa ON ssr.assignment_activity_id = aa.id
    WHERE 1=1
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Base query success. Rows: " . count($res) . "\n";
} catch (\PDOException $e) {
    echo "Base query failed: " . $e->getMessage() . "\n";
    exit;
}

// Add Students Join
$sql = "
    SELECT ssr.id
    FROM student_summary_report ssr
    JOIN students s ON ssr.student_no = s.student_no
";
try {
    $db->query($sql);
    echo "Students JOIN success.\n";
} catch (\PDOException $e) { echo "Students JOIN failed: " . $e->getMessage() . "\n"; }

// Add Subjects Join
$sql = "
    SELECT ssr.id
    FROM student_summary_report ssr
    JOIN subjects subj ON ssr.subject_id = subj.id
";
try {
    $db->query($sql);
    echo "Subjects JOIN success.\n";
} catch (\PDOException $e) { echo "Subjects JOIN failed: " . $e->getMessage() . "\n"; }

// Add Classes Join
$sql = "
    SELECT ssr.id
    FROM student_summary_report ssr
    JOIN classes c ON ssr.class_id = c.id
";
try {
    $db->query($sql);
    echo "Classes JOIN success.\n";
} catch (\PDOException $e) { echo "Classes JOIN failed: " . $e->getMessage() . "\n"; }

// Add Activities Join
$sql = "
    SELECT ssr.id
    FROM student_summary_report ssr
    JOIN assignment_activities aa ON ssr.assignment_activity_id = aa.id
";
try {
    $db->query($sql);
    echo "Activities JOIN success.\n";
} catch (\PDOException $e) { echo "Activities JOIN failed: " . $e->getMessage() . "\n"; }
