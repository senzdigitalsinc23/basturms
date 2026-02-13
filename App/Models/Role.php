<?php
namespace App\Models;

use App\Core\Database;
use Database\ORM\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    public int $role_id;
    public string $name;

    public function toArray(): array
    {
        return [
            'role_id'    => $this->role_id,
            'name'  => $this->name,
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'id');
    }

    public static function permissions($roleId): array
    {
        $cache = new \App\Core\Cache();
        $cacheKey = "role:{$roleId}:permissions";
        
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT p.name FROM permissions p
            JOIN permission_role rp ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$roleId]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Cache permissions for 1 hour
        $cache->set($cacheKey, $permissions, 3600);
        
        return $permissions;
    }
}