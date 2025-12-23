<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'owner_id',
        'business_name',
        'address',
        'domain',
        'slug',
        'data'
    ];

     protected $casts = [
        'data' => 'array',
    ];

    public $incrementing = false;
    
    protected $keyType = 'string';

    public function user(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
        ->withPivot('role_in_tenant')
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function paymentGateways(): BelongsToMany
    {
        return $this->belongsToMany(PaymentGateway::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'owner_id',
            'business_name',
            'address',
            'domain',
            'slug',
            'data',
            'created_at',
            'updated_at',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tenant) {
            // Si no tiene ID, generar uno con UUID
            if (!$tenant->id) {
                $tenant->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected static function booted()
    {
        static::created(function ($tenant) {
            if (request()->has('owner_id')) {
                $ownerId = request('owner_id');

                \App\Models\User::where('id', $ownerId)->update([
                    'tenant_id' => $tenant->id,
                ]);
            }
        });

       static::deleting(function ($tenant) {
        // 1. Borrar físicamente los pagos asociados al negocio
       $tenant->payments()->delete();

        // 2. Borrar físicamente las pasarelas de pago asociadas a este negocio
        // Usamos cada modelo para que se borre el registro real, no solo la relación
        $tenant->paymentGateways->each->delete();

        // 3. Borrar a los empleados exclusivos
        $tenant->user()->role('Employee')->get()->each->delete();

        // 4. Limpiar la tabla pivote de usuarios por seguridad
        $tenant->users()->detach();
    });
    }

}