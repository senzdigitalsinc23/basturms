<?php

namespace App\Models;

use App\Database\ORM\Model;

class Document extends Model
{
    protected string $table = 'documents';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'document_id',
        'entity_type',
        'entity_id',
        'category_id',
        'document_name',
        'original_filename',
        'file_path',
        'file_size',
        'file_type',
        'file_extension',
        'description',
        'uploaded_by',
        'upload_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'status',
        'metadata'
    ];
}
