<?php

use Database\Seeder;

/**
 * Seeder for populating the subjects table with Ghanaian curriculum subjects.
 *
 * Seeds subjects from Creche to Junior High School according to Ghana's curriculum.
 */
class SubjectSeeder extends Seeder
{
    /**
     * Seeds the subjects table with Ghanaian curriculum subjects.
     *
     * @return void
     */
    public function run(): void
    {
        $subjects = $this->getGhanaianSubjects();
        $this->insertSubjects($subjects);
    }

    /**
     * Returns all Ghanaian curriculum subjects organized by level.
     *
     * @return array
     */
    private function getGhanaianSubjects(): array
    {
        return [
            // CRECHE SUBJECTS (Ages 3-5)
            [
                'subject_name' => 'Personal and Social Development',
                'subject_code' => 'PSD',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Basic social skills, hygiene, and personal development'
            ],
            [
                'subject_name' => 'Language and Literacy',
                'subject_code' => 'LL',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Basic language skills, phonics, and early literacy'
            ],
            [
                'subject_name' => 'Mathematics',
                'subject_code' => 'MATH-CR',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Basic counting, shapes, colors, and patterns'
            ],
            [
                'subject_name' => 'Creative Arts',
                'subject_code' => 'CA',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Drawing, painting, music, and creative expression'
            ],
            [
                'subject_name' => 'Physical Development',
                'subject_code' => 'PD',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Motor skills, coordination, and physical activities'
            ],
            [
                'subject_name' => 'Environmental Studies',
                'subject_code' => 'ES',
                'level' => 'Creche',
                'category' => 'Core',
                'description' => 'Basic environmental awareness and nature studies'
            ],

            // KINDERGARTEN SUBJECTS (KG 1-2, Ages 5-6)
            [
                'subject_name' => 'English Language',
                'subject_code' => 'ENG-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Basic English language skills and phonics'
            ],
            [
                'subject_name' => 'Mathematics',
                'subject_code' => 'MATH-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Numbers, counting, basic operations, and problem solving'
            ],
            [
                'subject_name' => 'Science',
                'subject_code' => 'SCI-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Basic science concepts and environmental awareness'
            ],
            [
                'subject_name' => 'Social Studies',
                'subject_code' => 'SST-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Basic social studies and community awareness'
            ],
            [
                'subject_name' => 'Religious and Moral Education',
                'subject_code' => 'RME-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Basic moral values and religious education'
            ],
            [
                'subject_name' => 'Creative Arts',
                'subject_code' => 'CA-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Art, music, and creative expression'
            ],
            [
                'subject_name' => 'Physical Education',
                'subject_code' => 'PE-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Physical activities and motor skill development'
            ],
            [
                'subject_name' => 'Ghanaian Language',
                'subject_code' => 'GL-KG',
                'level' => 'KG',
                'category' => 'Core',
                'description' => 'Introduction to Ghanaian languages'
            ],

            // PRIMARY SCHOOL SUBJECTS (Grades 1-6)
            [
                'subject_name' => 'English Language',
                'subject_code' => 'ENG',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Reading, writing, grammar, and communication skills'
            ],
            [
                'subject_name' => 'Mathematics',
                'subject_code' => 'MATH',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Numbers, operations, geometry, and problem solving'
            ],
            [
                'subject_name' => 'Science',
                'subject_code' => 'SCI',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Basic science, environmental studies, and health education'
            ],
            [
                'subject_name' => 'Social Studies',
                'subject_code' => 'SST',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'History, geography, citizenship, and culture'
            ],
            [
                'subject_name' => 'Religious and Moral Education',
                'subject_code' => 'RME',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Religious studies and moral development'
            ],
            [
                'subject_name' => 'Information and Communication Technology',
                'subject_code' => 'ICT',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Basic computer skills and digital literacy'
            ],
            [
                'subject_name' => 'French',
                'subject_code' => 'FRE',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Basic French language skills'
            ],
            [
                'subject_name' => 'Ghanaian Language',
                'subject_code' => 'GL',
                'level' => 'Primary',
                'category' => 'Core',
                'description' => 'Ghanaian language and cultural studies'
            ],
            [
                'subject_name' => 'Creative Arts',
                'subject_code' => 'CA-PR',
                'level' => 'Primary',
                'category' => 'Optional',
                'description' => 'Art, music, and creative activities'
            ],
            [
                'subject_name' => 'Physical Education',
                'subject_code' => 'PE-PR',
                'level' => 'Primary',
                'category' => 'Optional',
                'description' => 'Sports and physical activities'
            ],

            // JUNIOR HIGH SCHOOL SUBJECTS (JHS 1-3)
            [
                'subject_name' => 'English Language',
                'subject_code' => 'ENG-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Advanced English language and literature'
            ],
            [
                'subject_name' => 'Mathematics',
                'subject_code' => 'MATH-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Advanced mathematics including algebra and geometry'
            ],
            [
                'subject_name' => 'Integrated Science',
                'subject_code' => 'INT-SCI',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Integrated science covering physics, chemistry, and biology'
            ],
            [
                'subject_name' => 'Social Studies',
                'subject_code' => 'SST-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'History, geography, government, and economics'
            ],
            [
                'subject_name' => 'Religious and Moral Education',
                'subject_code' => 'RME-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Advanced religious studies and moral philosophy'
            ],
            [
                'subject_name' => 'Information and Communication Technology',
                'subject_code' => 'ICT-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Advanced computer skills and digital literacy'
            ],
            [
                'subject_name' => 'French',
                'subject_code' => 'FRE-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Intermediate French language skills'
            ],
            [
                'subject_name' => 'Ghanaian Language',
                'subject_code' => 'GL-JHS',
                'level' => 'JHS',
                'category' => 'Core',
                'description' => 'Advanced Ghanaian language and literature'
            ],
            [
                'subject_name' => 'Business Studies',
                'subject_code' => 'BUS',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Business principles and entrepreneurship'
            ],
            [
                'subject_name' => 'Home Economics',
                'subject_code' => 'HEC',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Home management and family life education'
            ],
            [
                'subject_name' => 'Visual Arts',
                'subject_code' => 'VA',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Drawing, painting, and visual arts'
            ],
            [
                'subject_name' => 'General Agriculture',
                'subject_code' => 'AGR',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Agricultural science and practices'
            ],
            [
                'subject_name' => 'General Knowledge in Art',
                'subject_code' => 'GKA',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Art history and appreciation'
            ],
            [
                'subject_name' => 'Career Technology',
                'subject_code' => 'CT',
                'level' => 'JHS',
                'category' => 'Elective',
                'description' => 'Technical and vocational skills'
            ],
            [
                'subject_name' => 'Arabic',
                'subject_code' => 'ARA',
                'level' => 'JHS',
                'category' => 'Optional',
                'description' => 'Arabic language studies'
            ],
            [
                'subject_name' => 'Physical Education',
                'subject_code' => 'PE-JHS',
                'level' => 'JHS',
                'category' => 'Optional',
                'description' => 'Sports and physical fitness'
            ]
        ];
    }

    /**
     * Inserts the subjects into the database.
     *
     * @param array $subjects Array of subject data
     * @return void
     */
    private function insertSubjects(array $subjects): void
    {
        $sql = "
            INSERT INTO subjects (
                subject_name,
                subject_code,
                level,
                category,
                description,
                status
            ) VALUES (
                :subject_name,
                :subject_code,
                :level,
                :category,
                :description,
                'active'
            )
        ";

        foreach ($subjects as $subject) {
            $params = [
                ':subject_name' => $subject['subject_name'],
                ':subject_code' => $subject['subject_code'] ?? null,
                ':level' => $subject['level'],
                ':category' => $subject['category'],
                ':description' => $subject['description'] ?? null,
            ];
            $this->execute($sql, $params);
        }

        echo "Seeded " . count($subjects) . " Ghanaian curriculum subjects\n";
    }
}
