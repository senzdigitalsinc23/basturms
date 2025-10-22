<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class StudentClub extends ORMModel
{
    protected static string $table = 'student_clubs';
    protected array $fillable = [
        'student_no', 'club_name', 'club_description', 'position', 'join_date', 'status'
    ];
}
