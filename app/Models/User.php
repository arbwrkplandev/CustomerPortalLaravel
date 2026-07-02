<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 * Session Payload Contract (standardized for .NET compatibility):
 * - user_id, tenant_id, role, display_name, email, session_token, expires_at, permissions
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'username', 'email', 'phone', 'role',
        'password', 'plain_password', 'is_active',
        'avatar', 'preferred_theme', 'preferred_color',
    ];

    protected $hidden = ['password', 'plain_password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function isAdmin(): bool { return in_array($this->role, ['admin', 'superadmin']); }
    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isCustomer(): bool { return $this->role === 'customer'; }

    public function toSessionPayload(?string $sessionToken = null, ?string $expiresAt = null): array
    {
        return [
            'user_id'       => $this->id,
            'tenant_id'     => $this->tenant_id,
            'role'          => $this->role,
            'display_name'  => $this->name,
            'email'         => $this->email,
            'session_token' => $sessionToken,
            'expires_at'    => $expiresAt,
            'permissions'   => $this->resolvePermissions(),
        ];
    }

    protected function resolvePermissions(): array
    {
        return match ($this->role) {
            'superadmin' => ['*'],
            'admin'      => ['tenants.*', 'subscriptions.*', 'contracts.*', 'invoices.*', 'tickets.*', 'announcements.*'],
            'customer'   => ['portal.view', 'tickets.create', 'contracts.sign', 'invoices.view'],
            default      => [],
        };
    }
}
