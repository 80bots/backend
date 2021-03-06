<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\BotInstance;
use App\Http\Resources\BotInstanceResource;

class InstanceLaunched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $data;
    private $user;

    /**
     * Create a new event instance.
     *
     * @param BotInstance $userInstance
     * @param User $user
     */
    public function __construct($userInstance, $user)
    {
        $this->data = $userInstance;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('running.' . $this->user->id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'instance' => (new BotInstanceResource($this->data))->toArray(null),
        ];
    }
}
