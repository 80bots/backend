<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use Illuminate\Console\Command;
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

            $regions = AwsRegion::all();

            Log::info('Count ' . $regions->count());

            if (! empty($regions)) {

                Log::info('Regions isset');

                foreach ($regions as $region) {

                    Log::info("Sync region {$region->code}");

                    $aws    = new Aws;
                    $limit  = 5;
                    $token  = '';

                    do
                    {
                        $instancesByStatus = $aws->sync($region->code ?? '', $limit, $token);
                        $token = $instancesByStatus['nextToken'] ?? '';

                        $instancesByStatus = collect($instancesByStatus['data']);

                        if ($instancesByStatus->isNotEmpty()) {
                            InstanceHelper::syncInstances($instancesByStatus, $region);
                        }

                    } while(! empty($token));

                    unset($aws, $limit, $token);
                }
            }

            Log::info('Completed InstanceSyncScheduling');

        } catch (Throwable $throwable) {
            Log::info('ERROR');
            Log::error($throwable->getMessage());
        }

        Log::info('END');
    }
}
