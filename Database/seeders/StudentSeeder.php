<?php

use App\Core\Database;

class StudentSeeder
{
    public static function run()
    {
        $db = Database::getInstance()->getConnection();

        $students = [
            [
                'student_no' => 'STU001',
                'admission_no' => 'ADM001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'other_name' => '',
                'email' => 'johndoe@example.com',
                'phone' => '0244000001',
                'gender' => 'Male',
                'dob' => '2005-03-15',
                'class_id' => 'F1A',
                'country_id' => '001',
                'city' => 'Tarkwa',
                'hometown' => 'Dompim Pepesa',
                'residence' => 'Tarkwa Kwabedu',
                'house_no' => 'TKB24',
                'gps_no' => 'WT-2018-0919',
                'parent_no' => 'PAR001',
                'enrollment_date' => '2010-01-01',
                'created_at' => '',
                'updated_at' => '',
                'created_by' => '',
                'status'    => 'Active'
            ],
            [
                'student_no' => 'STU002',
                'admission_no' => 'ADM002',
                'first_name' => 'Mary',
                'last_name' => 'Smith',
                'other_name' => 'Ansah',
                'email' => 'smithmary@example.com',
                'phone' => '0244000002',
                'gender' => 'Female',
                'dob' => '2005-03-15',
                'class_id' => 'F1A',
                'country_id' => '001',
                'city' => 'Tarkwa',
                'hometown' => 'Asokore Mampong',
                'residence' => 'Tarkwa New site',
                'house_no' => 'TKB24',
                'gps_no' => 'WT-2018-0919',
                'parent_no' => 'PAR001',
                'enrollment_date' => '2010-01-01',
                'created_at' => '',
                'updated_at' => '',
                'created_by' => '',
                'status'    => 'Active'
            ],
            [
                'student_no' => 'STU003',
                'admission_no' => 'ADM003',
                'first_name' => 'Kwame',
                'last_name' => 'Mensah',
                'other_name' => '',
                'email' => 'kwamemensah@example.com',
                'phone' => '0244000003',
                'gender' => 'Male',
                'dob' => '2005-03-15',
                'class_id' => 'F1A',
                'country_id' => '001',
                'city' => 'Tarkwa',
                'hometown' => 'Dompim Pepesa',
                'residence' => 'Tarkwa Kwabedu',
                'house_no' => 'TKB24',
                'gps_no' => 'WT-2018-0919',
                'parent_no' => 'PAR001',
                'enrollment_date' => '2010-01-01',
                'created_at' => '',
                'updated_at' => '',
                'created_by' => '',
                'status'    => 'Active'
            ]
        ];

        

        foreach ($students as $student) {
            $keys = ':'. implode(',:', array_keys($student));
            $columns = implode(',', array_keys($student));
            
            $stmt = $db->prepare("
                INSERT INTO students ($columns) VALUES ($keys)
            ");
            $stmt->execute([
                 'student_no' => $student['student_no'],
                'admission_no' => $student['admission_no'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'other_name' => $student['other_name'],
                'email' => $student['email'],
                'phone' => $student['phone'],
                'gender' => $student['gender'],
                'dob' => $student['dob'],
                'class_id' => $student['class_id'],
                'country_id' => $student['country_id'],
                'parent_no' => $student['parent_no'],
                'enrollment_date' => $student['enrollment_date'],
                'city' => $student['city'],
                'hometown' => $student['hometown'],
                'residence' => $student['residence'],
                'house_no' => $student['house_no'],
                'gps_no' => $student['gps_no'],
                'created_at' => $student['country_id'],
                'updated_at' => $student['updated_at'],
                'created_by' => $student['created_by'],
                'status'    => $student['status']
                
            ]);
        }

        echo "✅ StudentSeeder: Students table populated successfully.\n";
    }
}
