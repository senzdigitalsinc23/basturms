<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class StudentSportsTeam extends ORMModel
{
    protected static string $table = 'student_sports_teams';
    protected array $fillable = [
        'student_no', 'team_name', 'sport', 'position', 'join_date', 'status', 'achievements'
    ];
}
