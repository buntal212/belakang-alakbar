<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaldoUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $saldo)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('saldo.' . $this->saldo['pemilik']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'saldo.updated';
    }

    public function broadcastWith(): array
    {
        return $this->saldo;
    }
}
