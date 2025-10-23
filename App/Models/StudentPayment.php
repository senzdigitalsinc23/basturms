<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class StudentPayment extends ORMModel
{
    protected static string $table = 'student_payments';
    protected array $fillable = [
        'student_no', 'payment_type', 'amount', 'payment_method', 
        'reference', 'status', 'payment_date', 'due_date', 'description', 'created_by'
    ];
}
