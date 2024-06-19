<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinishSlide implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $slide_id;
    public $show_id;

    /**
     * Create a new event instance.
     */
    public function __construct($id, $show_id)
    {
        $this->slide_id = $id;
        $this->show_id = $show_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('show.' . $this->show_id)
        ];
    }
}
