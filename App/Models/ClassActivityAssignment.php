<?php

namespace App\Models;

use Database\ORM\Model;

class ClassActivityAssignment extends Model
{
    protected static string $table = 'class_activity_assignment';
    
    public int $id;
    public string $class_id;
    public string $act_id;
    public string $status;
    public string $academic_year;
    public string $term;
    public string $assigned_by;
    public string $assigned_on;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'act_id' => $this->act_id,
            'status' => $this->status,
            'academic_year' => $this->academic_year,
            'term'  => $this->term,
            'assigned_by' => $this->assigned_by,
            'assigned_on' => $this->assigned_on
        ];
    }
}
