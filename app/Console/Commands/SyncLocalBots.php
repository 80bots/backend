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

            File::deleteDirectory(base_path('resources/puppeteer'));

            shell_exec('git clone -b master https://14b12de18e2199b2d584d3f6cf9492f3353f9b3e@github.com/80bots/puppeteer.git ./resources/puppeteer');

            $ignore = [
                'config.js',
                '.gitignore',
                'package.json',
                'package-lock.json'
            ];

            $files = File::allFiles(base_path('resources/puppeteer'));

            if (! empty($files)) {
                foreach ($files as $file) {

                    if (! in_array($file->getFilename(), $ignore)) {

                        $res = explode('-', $file->getFilename());
                        $filePlatform = $res[0] ?? 'unknown';

                        $content = $file->getContents();

                        $result = BotParser::getBotInfo($content);

                        if (! empty($result['about']) && ! empty($result['params'])) {

                            $platformName = $result['about']->platform ?? $filePlatform;

                            if (! empty($result['about']->platform)) {
                                $platform = Platform::where('name', '=', $platformName)->first();
                            } else {
                                $platform = Platform::whereRaw('lower(name) like (?)',["%{$platformName}%"])->first();
                            }

                            if (empty($platform)) {
                                $platform = Platform::create([
                                    'name' => ucfirst($platformName),
                                ]);
                            }

                            $bot = Bot::where('name', '=', $result['about']->name)->first();

                            if (! empty($bot)) {
                                $bot->update([
                                    'description'   => $result['about']->description,
                                    'parameters'    => json_encode($result['params'])
                                ]);
                            } else {
                                Bot::create([
                                    'platform_id'   => $platform->id ?? null,
                                    'name'          => $result['about']->name,
                                    'description'   => $result['about']->description,
                                    'parameters'    => json_encode($result['params']),
                                    'path'          => $file->getFilename()
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
