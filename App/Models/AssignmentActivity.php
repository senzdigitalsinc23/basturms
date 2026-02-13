<?php

namespace App\Models;

use Database\ORM\Model;

class AssignmentActivity extends Model
{
    protected static string $table = 'assignment_activities';

    public int $id;
    public string $activity_id;
    public string $act_name;
    public int $expected_per_term;
    public int $weight;
    public string $academic_year;
    public string $term;
    public string $added_by;
    public ?string $added_on;
    public int $is_standalone = 0;
    public string $status;

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'act_name' => $this->act_name,
            'expected_per_term' => $this->expected_per_term,
            'weight' => $this->weight,
            'is_standalone' => $this->is_standalone,
            'academic_year' => $this->academic_year,
            'term' => $this->term,
            'added_by' => $this->added_by,
            'added_on' => $this->added_on,
            'status' => $this->status
        ];
    }
}
