<?php

namespace App\Models;

use Database\ORM\Model;

class PromotionCriteria extends Model
{
    protected static string $table = 'promotion_criteria';
    protected static string $primaryKey = 'id';

    public function class()
    {
        return $this->belongsTo(Classes::class, 'level_id', 'class_id');
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
