<?php
namespace App\Models;

use Database\ORM\Model;

class Transaction extends Model
{
    protected string $table = 'transactions';

    protected array $fillable = [
        'gateway',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payload'
    ];
}
