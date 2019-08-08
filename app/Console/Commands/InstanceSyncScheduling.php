<?php

namespace App\Console\Commands;

use App\Bot;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use App\User;
use App\UserInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceSyncScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:sync';

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

            Log::info('Sync started at ' . date('Y-m-d h:i:s'));

            $aws    = new Aws;
            $limit  = 5;
            $token  = '';

            do
            {
                $instancesByStatus = $aws->sync($limit, $token);
                $token = $instancesByStatus['nextToken'] ?? '';

                InstanceHelper::syncInstances($instancesByStatus['data']);

            } while(! empty($instancesByStatus['nextToken']));

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
