<?php

namespace App\Models;

use Database\ORM\Model;

class Classes extends Model
{
    protected static string $table = 'classes';
    protected static string $primaryKey = 'id';

    public int $id;
    public string $class_id;
    public string $class_name;
    public string $status;
    //public string $level_id;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'act_id' => $this->class_name,
            //'level_id' => $this->level_id ?? '',
            'status' => $this->status,
        ];
    }
}
