<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'username', 'password', 'phone', 'is_active', 'user_type', 'default_lorry_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use \App\Traits\LogsActivity;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Define roles relationship.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function lorryAssignments()
    {
        return $this->hasMany(LorryUserAssignment::class);
    }

    /**
     * Check if user has a role.
     */
    public function hasRole(string $roleName): bool
    {
        if ($this->roles->contains('name', 'Super Admin') || $this->roles->contains('name', 'Admin') || $this->roles->contains('name', 'Administrator')) {
            return true;
        }
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Check if user has a permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super Admin and Admin have all permissions
        if ($this->roles->contains('name', 'Super Admin') || $this->roles->contains('name', 'Admin') || $this->roles->contains('name', 'Administrator')) {
            return true;
        }

        // Check user's roles' permissions
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        return false;
    }
}
