<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class StudentDocument extends ORMModel
{
    protected static string $table = 'student_documents';
    protected array $fillable = [
        'student_no', 'document_name', 'document_type', 'file_path', 'file_size', 
        'mime_type', 'description', 'uploaded_by', 'status'
    ];
}
