<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use Database\ORM\Model;

class User extends Model
{
    protected static string $table = 'users';

    public int $id;
    public string $user_id;
    public string $username;
    public string $email;
    public string $password;
    public ?string $created_at;
    public ?string $updated_at;
    public string $status;
    public ?string $is_super_admin;
    public ?int $role_id;

    /**
     * Hide password when converting to array.
     */
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'user_id' => $this->user_id,
            'name'  => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_super_admin' => $this->is_super_admin,
            'role_id' => $this->role_id,
            'status'   => $this->status
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles', 'id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id', 'id');
    }

     public static function hasPermission(string $permission): bool
    {
        $db = Database::getInstance()->getConnection();
        $role = Session::get('user_role');

        if (!$role) {
            return false;
        }

        $stmt = $db->prepare("
            SELECT p.name 
            FROM permissions p
            JOIN permission_role rp ON rp.permission_id = p.id
            JOIN roles r ON r.id = rp.role_id
            WHERE r.name = ? AND p.name = ?
        ");
        $stmt->execute([$role, $permission]);
        return (bool) $stmt->fetch();
    }

    /* public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    } */
}
