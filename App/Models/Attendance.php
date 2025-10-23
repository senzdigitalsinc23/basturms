<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class Attendance extends ORMModel
{
    protected static string $table = 'attendance';
    protected array $fillable = [
        'student_no', 'date', 'status', 'notes', 'recorded_by'
    ];
}
