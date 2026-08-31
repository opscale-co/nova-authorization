<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Nova\Auth\Impersonatable;
use Opscale\NovaAuthorization\Contracts\HasPrivileges;
use Opscale\Validations\Validatable;
use Spatie\Permission\Traits\HasRoles;
use Workbench\Database\Factories\UserFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements HasPrivileges
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Impersonatable, Notifiable, Validatable;

    /**
     * @var array<string, list<string>>
     */
    public array $validationRules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:254', 'unique:users'],
        'password' => ['required', 'string', 'min:8'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Create a new factory instance for the model.
     */
    final protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    final public function isSuperAdmin(): bool
    {
        return $this->id == 1;
    }

    final public function checkPermissionTo(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * Determine if the user can impersonate another user.
     * Only super admins are allowed to impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Determine if the user can be impersonated.
     * Super admins cannot be impersonated for security reasons.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->isSuperAdmin();
    }
}
