<?php

namespace App\Models;

use Database\ORM\Model;

class GradingScheme extends Model
{
    protected static string $table = 'grading_scheme';
    
    public int $id;
    public string $grade;
    public int $grade_from;
    public int $grade_to;
    public string $remarks;
    public string $added_by;
    public string $added_on;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'grade' => $this->grade,
            'grade_from' => $this->grade_from,
            'grade_to' => $this->grade_to,
            'remarks' => $this->remarks,
            'added_by' => $this->added_by,
            'added_on' => $this->added_on
        ];
    }
}
