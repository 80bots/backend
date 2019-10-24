<?php
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use App\BotInstance;

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('running.{user_id}', function ($user, $user_id) {
    return true;
});

Broadcast::channel('bots.{user_id}', function ($user, $user_id) {
    return true;
});

Broadcast::channel('instance.{instance_id}.show', function ($user, $instance_id) {
    return $user->isAdmin() ? true : $user->id === BotInstance::find($instance_id)->user_id;
});

Broadcast::channel('instance-live', function ($user) {
    return $user->isAdmin();
});
