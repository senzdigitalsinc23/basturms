<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class StudentBillItem extends ORMModel
{
    protected static string $table = 'student_bill_items';
    protected array $fillable = [
        'student_no', 'item_name', 'description', 'amount', 'due_date', 'status'
    ];
}
