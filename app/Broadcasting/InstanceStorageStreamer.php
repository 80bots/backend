<?php

namespace App\Broadcasting;

use App\User;

class InstanceStorageStreamer
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
    //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param User $user
     * @param $instance_id
     * @return array|bool
     */
    public function join(User $user, $instance_id)
    {
        return $user->hasAccessToInstance($instance_id);
    }
}
