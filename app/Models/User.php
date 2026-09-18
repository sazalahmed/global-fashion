<?php

namespace App\Models;

use App\Traits\PermissionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, PermissionsTrait;

    /**
     * The protected Super Admin role name. Users holding this role and the
     * role itself are hidden from management screens and cannot be edited.
     */
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'image',
        'branch_id',
        'status',
        'last_login_at',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'last_login_at'           => 'datetime',
            'password'                => 'hashed',
            'two_factor_secret'       => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Scope: active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: exclude Super Admin users from management listings.
     */
    public function scopeExcludingSuperAdmin($query)
    {
        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', self::SUPER_ADMIN_ROLE);
        });
    }

    /**
     * Determine if this user holds the protected Super Admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    /**
     * Whether the user has completed Google Authenticator (TOTP) enrollment.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }
}
