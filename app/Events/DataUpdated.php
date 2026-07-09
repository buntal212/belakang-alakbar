<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Channel yang akan dibroadcast.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('saldo.' . $this->data['jabatan']),
        ];
    }

    /**
     * Nama event yang diterima frontend.
     */
    public function broadcastAs(): string
    {
       return 'saldo.updated';
    }

    /**
     * Data yang dikirim ke frontend.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
