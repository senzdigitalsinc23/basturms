<?php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }
}

/* $user = User::find(1);

// HasOne
$profile = $user->profile();

// HasMany
foreach ($user->posts() as $post) {
    echo $post->title;
}

// BelongsTo
echo $user->role()->name;

// BelongsToMany
foreach ($user->groups() as $group) {
    echo $group->name;
}
 */