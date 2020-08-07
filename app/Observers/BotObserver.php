<?php

namespace App\Observers;

use App\Bot;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @method error(array|Application|Translator|string|null $__, string $getMessage)
 */
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
        try {
            if($bot->s3_folder_name !== null) {
                Storage::disk('s3')->put('custom-bot/' . $bot->s3_folder_name .'/' . $bot->path, $bot->aws_custom_script);
                Storage::disk('s3')->put('custom-bot/' . $bot->s3_folder_name . '/package.json', $bot->aws_custom_package_json);
            }
        } catch (Throwable $throwable) {
            return $this->error(__('custom_bot.server_error'), $throwable->getMessage());
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
        try {
            if($bot->s3_folder_name !== null) {
                Storage::disk('s3')->deleteDirectory('custom-bot/' . $bot->s3_folder_name);
                Storage::disk('s3')->put('custom-bot/' . $bot->s3_folder_name .'/' . $bot->path, $bot->aws_custom_script);
                Storage::disk('s3')->put('custom-bot/' . $bot->s3_folder_name . '/package.json', $bot->aws_custom_package_json);
            }
        } catch (Throwable $throwable) {
            return $this->error(__('custom_bot.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Handle the bot "deleting" event.
     *
     * @param Bot $bot
     * @return void
     */
    public function deleting(Bot $bot)
    {
        try {
            Storage::disk('s3')->deleteDirectory('custom-bot/' . $bot->s3_folder_name);
        } catch (Throwable $throwable) {
            return $this->error(__('custom_bot.server_error'), $throwable->getMessage());
        }
    }
}
