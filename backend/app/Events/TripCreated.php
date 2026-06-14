<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // ДОЛЖЕН БЫТЬ ТОЛЬКО ОДИН public параметр
    public function __construct(public Trip $trip)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('drivers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TripCreated';
    }

    // ДОБАВЬТЕ ЭТОТ МЕТОД ДЛЯ ПРАВИЛЬНОЙ СЕРИАЛИЗАЦИИ
    public function broadcastWith(): array
    {
        return [
            'trip' => $this->trip->load('user')
        ];
    }
}
