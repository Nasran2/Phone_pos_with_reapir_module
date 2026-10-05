<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @property Collection<int, Permission> $permissions */
class Role extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'guard_name', 'description', 'is_system_role', 'is_fixed', 'is_deletable', 'is_renamable'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
