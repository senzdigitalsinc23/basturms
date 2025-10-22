<?php
namespace App\Models;

use Database\ORM\Model as ORMModel;

class AuthLog extends ORMModel
{
    protected static string $table = 'auth_logs';
    protected array $fillable = [
        'user_id', 'event', 'event_status', 'details', 'client_info', 'ip_address', 'user_agent'
    ];
}
