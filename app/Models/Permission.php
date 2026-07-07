<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['name', 'guard_name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
