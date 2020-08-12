<?php

namespace App\Console\Commands;

use App\Bot;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
            $directories = $disk->directories('custom-bot/');
            foreach ($directories as $directory) {
                $bot = Bot::where('s3_path', '=', $directory)->first();
                if(!$bot) {
                    $files = $disk->files($directory);
                    foreach ($files as $file) {
                        if (Str::contains($file,'/_metadata.json')) {
                            $data = json_decode($disk->get($file));
                            Bot::updateOrCreate([
                               'platform_id'        => $data->platform_id,
                               'name'               => $data->name,
                               'description'        => $data->description,
                               'parameters'         => $data->parameters,
                               'path'               => $data->path,
                               's3_path'            => $data->s3_path,
                               'type'               => $data->type,
                            ]);
                            Log::info(print_r($data, true));
                        }
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
