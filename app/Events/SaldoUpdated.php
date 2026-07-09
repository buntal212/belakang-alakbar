<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
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
            new Channel('saldo.' . $this->saldo['pemilik']),
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
