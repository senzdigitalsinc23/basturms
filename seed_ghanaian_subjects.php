<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

try {
    // Ghanaian curriculum subjects organized by level
    $subjects = [
        // CRECHE SUBJECTS
        ['subject_id' => 'PSD_CR', 'subject_name' => 'Personal and Social Development'],
        ['subject_id' => 'LL_CR', 'subject_name' => 'Language and Literacy'],
        ['subject_id' => 'MATH_CR', 'subject_name' => 'Mathematics'],
        ['subject_id' => 'CA_CR', 'subject_name' => 'Creative Arts'],
        ['subject_id' => 'PD_CR', 'subject_name' => 'Physical Development'],
        ['subject_id' => 'ES_CR', 'subject_name' => 'Environmental Studies'],

        // KINDERGARTEN SUBJECTS
        ['subject_id' => 'ENG_KG', 'subject_name' => 'English Language'],
        ['subject_id' => 'MATH_KG', 'subject_name' => 'Mathematics'],
        ['subject_id' => 'SCI_KG', 'subject_name' => 'Science'],
        ['subject_id' => 'SST_KG', 'subject_name' => 'Social Studies'],
        ['subject_id' => 'RME_KG', 'subject_name' => 'Religious and Moral Education'],
        ['subject_id' => 'CA_KG', 'subject_name' => 'Creative Arts'],
        ['subject_id' => 'PE_KG', 'subject_name' => 'Physical Education'],
        ['subject_id' => 'GL_KG', 'subject_name' => 'Ghanaian Language'],

        // PRIMARY SCHOOL SUBJECTS
        ['subject_id' => 'ENG_PR', 'subject_name' => 'English Language'],
        ['subject_id' => 'MATH_PR', 'subject_name' => 'Mathematics'],
        ['subject_id' => 'SCI_PR', 'subject_name' => 'Science'],
        ['subject_id' => 'SST_PR', 'subject_name' => 'Social Studies'],
        ['subject_id' => 'RME_PR', 'subject_name' => 'Religious and Moral Education'],
        ['subject_id' => 'ICT_PR', 'subject_name' => 'Information and Communication Technology'],
        ['subject_id' => 'FRE_PR', 'subject_name' => 'French'],
        ['subject_id' => 'GL_PR', 'subject_name' => 'Ghanaian Language'],
        ['subject_id' => 'CA_PR', 'subject_name' => 'Creative Arts'],
        ['subject_id' => 'PE_PR', 'subject_name' => 'Physical Education'],

        // JUNIOR HIGH SCHOOL SUBJECTS
        ['subject_id' => 'ENG_JHS', 'subject_name' => 'English Language'],
        ['subject_id' => 'MATH_JHS', 'subject_name' => 'Mathematics'],
        ['subject_id' => 'SCI_JHS', 'subject_name' => 'Integrated Science'],
        ['subject_id' => 'SST_JHS', 'subject_name' => 'Social Studies'],
        ['subject_id' => 'RME_JHS', 'subject_name' => 'Religious and Moral Education'],
        ['subject_id' => 'ICT_JHS', 'subject_name' => 'Information and Communication Technology'],
        ['subject_id' => 'FRE_JHS', 'subject_name' => 'French'],
        ['subject_id' => 'GL_JHS', 'subject_name' => 'Ghanaian Language'],

        // JHS ELECTIVES
        ['subject_id' => 'BUS_JHS', 'subject_name' => 'Business Studies'],
        ['subject_id' => 'HEC_JHS', 'subject_name' => 'Home Economics'],
        ['subject_id' => 'VA_JHS', 'subject_name' => 'Visual Arts'],
        ['subject_id' => 'AGR_JHS', 'subject_name' => 'General Agriculture'],
        ['subject_id' => 'GKA_JHS', 'subject_name' => 'General Knowledge in Art'],
        ['subject_id' => 'CT_JHS', 'subject_name' => 'Career Technology'],
        ['subject_id' => 'ARA_JHS', 'subject_name' => 'Arabic'],
        ['subject_id' => 'PE_JHS', 'subject_name' => 'Physical Education']
    ];

    echo "Seeding Ghanaian curriculum subjects...\n";

    $inserted = 0;
    $skipped = 0;

    foreach ($subjects as $subject) {
        // Check if subject already exists
        $stmt = $db->prepare("SELECT id FROM subjects WHERE subject_id = ?");
        $stmt->execute([$subject['subject_id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            echo "  Skipping: {$subject['subject_name']} (already exists)\n";
            $skipped++;
            continue;
        }

        // Insert new subject
        $stmt = $db->prepare("
            INSERT INTO subjects (subject_id, subject_name, added_by, added_on)
            VALUES (?, ?, 'system', NOW())
        ");
        $stmt->execute([$subject['subject_id'], $subject['subject_name']]);

        echo "  ✓ Added: {$subject['subject_name']}\n";
        $inserted++;
    }

    echo "\nSeeding completed:\n";
    echo "  - Inserted: {$inserted} subjects\n";
    echo "  - Skipped: {$skipped} subjects (already existed)\n";
    echo "  - Total: " . ($inserted + $skipped) . " Ghanaian curriculum subjects\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
