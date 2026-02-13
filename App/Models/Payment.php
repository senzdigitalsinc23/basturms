<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class Payment extends ORMModel
{
    protected string $table = 'payments';
    protected array $fillable = ['transaction_id', 'phone', 'amount', 'status', 'reference', 'reason'];
}
