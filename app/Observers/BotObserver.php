<?php

namespace App\Observers;

use App\Bot;
use App\Platform;
use Illuminate\Support\Facades\Log;

class BotObserver
{
    /**
     * Handle the bot "creating" event.
     *
     * @param Bot $bot
     * @return void
     */
    public function creating(Bot $bot)
    {
        Log::info(print_r($bot, true));

        if($bot->platform_id){
            $bot->platform_id = $this->getPlatformId($bot->platform_id);
        }
    }

    /**
     * Handle the bot "updating" event.
     *
     * @param Bot $bot
     * @return void
     */
    public function updating(Bot $bot)
    {
        //
    }

    /**
     * Handle the bot "deleting" event.
     *
     * @param Bot $bot
     * @return void
     */
    public function deleting(Bot $bot)
    {
        //
    }

    /**
     * @param string|null $name
     * @return int|null
     */
    private function getPlatformId(?string $name): ?int
    {
        $platform = Platform::findByName($name)->first();

        if (empty($platform)) {
            $platform = Platform::create([
                'name' => $name
            ]);
        }

        return $platform->id ?? null;
    }
}
