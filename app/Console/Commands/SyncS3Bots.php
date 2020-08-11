<?php

namespace App\Console\Commands;

use App\Bot;
use App\Services\BotParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SyncS3Bots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:sync-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var string
     */
    protected $now;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->now = Carbon::now()->toDateTimeString();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $disk = Storage::disk('s3');
            $array = [];
            $files = $disk->directories('custom-bot/');
            Log::info(print_r($files, true));
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
