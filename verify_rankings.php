<?php

/**
 * verify_rankings.php
 *
 * Quick CLI sanity-check for the ComputeRankingsJob output.
 *
 * Usage:
 *   php verify_rankings.php [academic_year] [term]
 *
 * Defaults to the latest (academic_year, term) combinations found in
 * student_term_rankings if no arguments are supplied.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// ── Resolve year/term from CLI args or fall back to latest in DB ──────────
$academicYear = $argv[1] ?? null;
$term         = $argv[2] ?? null;

if (!$academicYear || !$term) {
    $latest = $db->query("
        SELECT academic_year, term
        FROM   student_term_rankings
        ORDER  BY updated_at DESC
        LIMIT  1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$latest) {
        // Try student_report instead (rankings may not yet be computed)
        $latest = $db->query("
            SELECT academic_year, term
            FROM   student_report
            ORDER  BY id DESC
            LIMIT  1
        ")->fetch(PDO::FETCH_ASSOC);
    }

    if (!$latest) {
        echo "No data found in student_term_rankings or student_report. Run the jobs first.\n";
        exit(1);
    }

    $academicYear = $academicYear ?? $latest['academic_year'];
    $term         = $term         ?? $latest['term'];
}

echo "=== Ranking Verification: {$academicYear} – {$term} ===\n\n";

// ── 1. student_term_rankings ──────────────────────────────────────────────
echo "── Class Rankings (student_term_rankings) ──\n";

$stmt = $db->prepare("
    SELECT  str.class_position,
            str.student_no,
            CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.last_name) AS student_name,
            c.class_name,
            str.total_score_sum,
            str.average_score,
            str.subjects_count
    FROM    student_term_rankings str
    JOIN    students s ON str.student_no = s.student_no
    JOIN    classes  c ON str.class_id   = c.id
    WHERE   str.academic_year = :ay
      AND   str.term          = :term
    ORDER   BY c.class_name ASC, str.class_position ASC
    LIMIT   20
");
$stmt->execute([':ay' => $academicYear, ':term' => $term]);
$classRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($classRows)) {
    echo "  (no rows — run ComputeRankingsJob first)\n";
} else {
    printf("  %-5s %-12s %-30s %-15s %8s %8s %8s\n",
        'Pos', 'Student No', 'Name', 'Class', 'Sum', 'Avg', 'Subjs');
    echo "  " . str_repeat('-', 90) . "\n";
    foreach ($classRows as $r) {
        printf("  %-5d %-12s %-30s %-15s %8.2f %8.2f %8d\n",
            $r['class_position'],
            $r['student_no'],
            trim($r['student_name']),
            $r['class_name'],
            $r['total_score_sum'],
            $r['average_score'],
            $r['subjects_count']
        );
    }
}

echo "\n";

// 2. School Rankings
echo "\n── School Rankings (Top 5) ──\n";
$schoolSql = "
    SELECT str.school_position as `rank`, str.student_no, s.first_name, s.last_name, c.class_name, str.average_score
    FROM   student_term_rankings str
    JOIN   students s ON str.student_no = s.student_no
    JOIN   classes c ON str.class_id = c.id
    WHERE  str.academic_year = :ay AND str.term = :term
    ORDER  BY str.school_position ASC
    LIMIT  5
";
$stmt = $db->prepare($schoolSql);
$stmt->execute([':ay' => $academicYear, ':term' => $term]);
printf("  %-4s %-12s %-30s %-20s %-8s\n", "Rank", "ID", "Name", "Class", "Avg");
echo "  " . str_repeat("-", 80) . "\n";
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("  %-4d %-12s %-30s %-20s %-8.2f\n",
        $r['rank'],
        substr($r['student_no'], 0, 12),
        $r['first_name'] . ' ' . $r['last_name'],
        $r['class_name'],
        $r['average_score']
    );
}

// 3. Level Rankings (LP sample)
echo "\n── Level Rankings: LP (Lower Primary) ──\n";
$levelSql = "
    SELECT str.level_position as `rank`, str.student_no, s.first_name, s.last_name, c.class_name, str.average_score
    FROM   student_term_rankings str
    JOIN   students s ON str.student_no = s.student_no
    JOIN   classes c ON str.class_id = c.id
    WHERE  str.academic_year = :ay AND str.term = :term AND c.level_id = 'LP'
    ORDER  BY str.level_position ASC
";
$stmt = $db->prepare($levelSql);
$stmt->execute([':ay' => $academicYear, ':term' => $term]);
printf("  %-4s %-12s %-30s %-20s %-8s\n", "Rank", "ID", "Name", "Class", "Avg");
echo "  " . str_repeat("-", 80) . "\n";
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("  %-4d %-12s %-30s %-20s %-8.2f\n",
        $r['rank'],
        substr($r['student_no'], 0, 12),
        $r['first_name'] . ' ' . $r['last_name'],
        $r['class_name'],
        $r['average_score']
    );
}

// 4. Subject Rankings sample (existing)
echo "\n── Subject Rankings sample (student_report.subject_position) ──\n";

$stmt = $db->prepare("
    SELECT  sr.subject_position as `rank`,
            sr.student_no,
            CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.last_name) AS student_name,
            subj.subject_name,
            c.class_name,
            sr.`total_score_100%` AS score,
            sr.grade
    FROM    student_report sr
    JOIN    students s    ON sr.student_no  = s.student_no
    JOIN    subjects subj ON sr.subject_id  = subj.id
    JOIN    classes  c    ON sr.class_id    = c.id
    WHERE   sr.academic_year = :ay
      AND   sr.term          = :term
    ORDER   BY c.class_name ASC, subj.subject_name ASC, sr.subject_position ASC
    LIMIT   20
");
$stmt->execute([':ay' => $academicYear, ':term' => $term]);
$subjectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($subjectRows)) {
    echo "  (no rows — run GenerateStudentReportJob first)\n";
} else {
    printf("  %-5s %-12s %-28s %-20s %-15s %8s %5s\n",
        'Pos', 'Student No', 'Name', 'Subject', 'Class', 'Score', 'Grade');
    echo "  " . str_repeat('-', 100) . "\n";
    foreach ($subjectRows as $r) {
        printf("  %-5s %-12s %-28s %-20s %-15s %8.2f %5s\n",
            $r['subject_position'] ?? '–',
            $r['student_no'],
            trim($r['student_name']),
            $r['subject_name'],
            $r['class_name'],
            $r['score'],
            $r['grade']
        );
    }
}

echo "\n=== Done ===\n";
