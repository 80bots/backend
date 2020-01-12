<?php

namespace App\Console\Commands;

use App\BotInstance;
use App\Jobs\SyncS3Objects;
use Illuminate\Console\Command;

class SyncDataFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:folders';

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
//        BotInstance::chunkById(1000, function ($instances) {
//            foreach ($instances as $instance) {
//                dispatch(new SyncS3Objects($instance));
//            }
//        });
    }
}