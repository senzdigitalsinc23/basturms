<?php

namespace App\Models;

use Database\ORM\Model;

class CalendarEventCategories extends Model
{
    protected static string $table = 'calendar_event_categories';
    
    public int $event_type_id;
    public string $event_type_name;
    public string $added_on;

    public function toArray(): array
    {
        return [
            'id' => $this->event_type_id,
            'category_name' => $this->event_type_name,
            'added_on' => $this->added_on
        ];
    }
}
