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
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
     use HasFactory, Notifiable, HasApiTokens, HasRoles, Impersonate;

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
        // Si el panel es el de empleados
    if ($panel->getId() === 'employees') {
        return $this->hasRole(['Superadmin', 'Admin', 'Employee']) && $this->status === 'approved';
    }
    
    // Si el panel es el de admin
    if ($panel->getId() === 'admin') {
        return $this->hasRole(['Superadmin', 'Admin']);
    }

    return false;
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

    // Añade esto dentro de la clase User
protected static function booted()
{
    static::deleting(function ($user) {
        // Si el usuario es dueño de negocios (Owner)
        if ($user->hasRole('Admin') || $user->ownedTenants()->exists()) {
            
            // Eliminamos cada negocio del que es dueño
            $user->ownedTenants->each(function ($tenant) {
                // Esto disparará el static::deleting en el modelo Tenant
                // borrando así empleados, pasarelas y pagos.
                $tenant->delete();
            });
        }
        
        // Limpiamos sus vínculos en la tabla pivote por si acaso
        $user->tenants()->detach();
    });
}

public function canImpersonate(): bool
    {
        return $this->hasRole('Superadmin');
    }

public function canBeImpersonated(): bool
    {
        return !$this->hasRole('Superadmin');
    }

}
