<?php

namespace App\Models;

use Database\ORM\Model;

class CalendarEvent extends Model
{
    protected static string $table = 'calendar_events';
    
    public int $id;
    public string $act_id;
    public ?string $sub_activity_id = null; // New field
    public string $activity_name;
    public string $status;
    public string $added_on;


}
