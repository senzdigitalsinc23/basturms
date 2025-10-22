<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class AuditLog extends ORMModel
{
    protected static string $table = 'audit_logs';
    protected array $fillable = [
        'user_id', 'action', 'details', 'client_info', 'ip_address', 'user_agent'
    ];
}
