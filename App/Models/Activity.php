<?php

namespace App\Models;

use Database\ORM\Model;

class Activity extends Model
{
    protected static string $table = 'activities';
    
    public int $id;
    public string $act_id;
    public ?string $sub_activity_id = null; // New field
    public string $activity_name;
    public string $status;
    public string $added_on;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'act_id' => $this->act_id,
            'sub_activity_id' => $this->sub_activity_id,
            'activity_name' => $this->activity_name,
            'status' => $this->status,
            'added_on' => $this->added_on
        ];
    }
}
