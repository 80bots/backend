<?php

namespace App\Console\Commands;

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

                        $content = $file->getContents();

                        dd($content);
                    }

                }
            }

            dd("SyncLocalBots");

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
