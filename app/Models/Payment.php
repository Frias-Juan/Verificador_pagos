<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Payment extends Model
{
    use BelongsToTenant;
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'payment_gateway_id',
        'amount',
        'payment_date',
        'remitter',
        'phone_number', 
        'reference',
        'bank',
        'verified',
        'verified_on',
        'status',
        'notification_data',
        'notification_source',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reference' => 'string',
        'payment_date' => 'date',
        'verified_on' => 'date',
        'verified' => 'boolean',
        'notification_data' => 'array',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }



    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function payment_gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
