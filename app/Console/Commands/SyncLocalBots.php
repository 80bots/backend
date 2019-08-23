<?php

namespace App\Console\Commands;

use App\Bot;
use App\Platform;
use App\Services\BotParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncLocalBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:sync-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $files = File::allFiles(base_path('resources/puppeteer'));

            if (! empty($files)) {
                foreach ($files as $file) {

                    if ($file->getFilename() === 'facebook-find-page-add-post.js') {

                        echo "FILE BOT {$file->getFilename()}\n";

                        $content = $file->getContents();

                        $result = BotParser::getBotInfo($content);

                        if (! empty($result['about']) && ! empty($result['params'])) {

                            $platform = Platform::where('name', '=', $result['about']['platform'])->first();

                            if (empty($platform)) {
                                $platform = Platform::create([
                                    'name',
                                ]);
                            }

                            $bot = Bot::where('name', '=', $result['about']['name'])->first();

                            if (! empty($bot)) {
                                $bot->update([
                                    'description'   => $result['about']['desc'],
                                    'parameters'    => json_encode($result['params'])
                                ]);
                            } else {
                                Bot::create([
                                    'platform_id'   => $platform->id ?? null,
                                    'name'          => $result['about']['name'],
                                    'description'   => $result['about']['desc'],
                                    'parameters'    => json_encode($result['params'])
                                ]);
                            }
                        }
                    }
                }
            }

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
