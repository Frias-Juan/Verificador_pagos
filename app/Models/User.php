<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
     use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'type_ident',
        'cedula',
        'phone',
        'password',
        'status',
        'tenant_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->BelongsTo(Tenant::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user', 'user_id', 'tenant_id')
        ->withPivot('role_in_tenant')
            ->withTimestamps();
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole('Superadmin', 'Admin', 'Employee');
    }

    public function paymentsgateways(): HasMany
    {
        return $this->hasMany(PaymentGateway::class);
    }


     public function currentTenant()
    {
        if ($this->tenant_id) {
            return Tenant::find($this->tenant_id);
        }
        
        return $this->tenants()->first();
    }

     public function isSuperAdmin(): bool
    {
        return $this->hasRole('Superadmin') && is_null($this->tenant_id);
    }
    
    // Determinar si es admin de tenant
    public function isTenantAdmin(): bool
    {
        return $this->hasRole('Admin');
    }
    
    // Determinar si es empleado
    public function isEmployee(): bool
    {
        return $this->hasRole('Employee');
    }

}
