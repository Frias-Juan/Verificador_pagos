<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    // Canales privados por tenant
    public function broadcastOn()
    {
        return new PrivateChannel('tenant.' . $this->payment->tenant_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->payment->id,
            'remitter' => $this->payment->remitter,
            'amount' => $this->payment->amount,
            'reference' => $this->payment->reference,
            'bank' => $this->payment->bank,
            'status' => $this->payment->status,
        ];
    }
}
