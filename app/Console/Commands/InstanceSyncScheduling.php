<?php

namespace App\Console\Commands;

use App\AwsRegion;
use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use Aws\Exception\AwsException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
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
    protected $description = 'Instances synchronization on AWS with the local base';

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

            $this->removeEmptyRecords();

            $this->clearTerminatedInstances();

            $regions = AwsRegion::all();

            if (! empty($regions)) {

                foreach ($regions as $region) {

                    Log::info("Sync region {$region->code}");

                    $aws    = new Aws;
                    $limit  = 50;
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

                    $this->checkNotTerminatedInstances($aws, $region);

                    unset($aws, $limit, $token);
                }
            }

            Log::info('Sync completed at ' . date('Y-m-d h:i:s'));

        } catch (Throwable $throwable) {
            Log::info('ERROR');
            Log::error($throwable->getMessage());
        }
    }

    private function checkNotTerminatedInstances(Aws $aws, AwsRegion $region): void
    {
        Log::info('checkNotTerminatedInstances started at ' . date('Y-m-d h:i:s'));

        $aws->ec2Connection($region->code ?? '');

        $region->instances()->findNotTerminated()->chunk(100, function ($instances) use ($aws, $region){

            $instanceIds = $instances->map(function ($item, $key) {
                return $item['aws_instance_id'];
            })->toArray();

            // Filters['instance-state-code'] => The code for the instance state, as a 16-bit unsigned integer.
            // The valid values are 0 (pending), 16 (running), 32 (shutting-down), 48 (terminated), 64 (stopping), and 80 (stopped).

            foreach ($instanceIds as $instanceId) {

                $parameters = [
                    'IncludeAllInstances' => true,
                    'InstanceIds' => [$instanceId],
                    'Filters' => [
                        [
                            'Name' => 'instance-state-code',
                            'Values' => [48],// 48 (terminated)
                        ],
                    ]
                ];

                try {

                    $instanceStatuses = $aws->describeInstanceStatus($region->code ?? '', $parameters);

                    if ($instanceStatuses->hasKey('InstanceStatuses')) {

                        $instanceStatuses = collect($instanceStatuses->get('InstanceStatuses'));

                        $this->deleteTerminatedInstances($instanceStatuses, $region);
                    }

                } catch (AwsException $exception) {
                    $this->deleteTerminatedInstances(collect([['InstanceId' => $instanceId]]), $region);
                } catch (Throwable $throwable) {
                    Log::error($throwable->getMessage());
                }
            }
        });

        Log::info('checkNotTerminatedInstances completed at ' . date('Y-m-d h:i:s'));
    }

    /**
     * @param Collection $instanceStatuses
     * @param AwsRegion $region
     */
    private function deleteTerminatedInstances(Collection $instanceStatuses, AwsRegion $region): void
    {
        $count = $instanceStatuses->count();

        if ($count > 0) {

            $terminatedIds = $instanceStatuses->map(function ($item, $key) {
                return $item['InstanceId'];
            })->toArray();

            if ($region->created_instances >= $count) {
                $region->decrement('created_instances', $count);
            }

            BotInstance::whereIn('aws_instance_id', $terminatedIds)
                ->update([
                    'aws_public_ip' => null,
                    'aws_status'    => BotInstance::STATUS_TERMINATED
                ]);
        }
    }

    /**
     * Blank records removal during instances synchronization
     */
    private function removeEmptyRecords(): void
    {
        Log::info('Remove Empty Records');
        BotInstance::emptyData()->chunk(100, function ($instances) {
            foreach ($instances as $instance) {
                $instance->forceDelete();
            }
        });
    }

    /**
     *
     */
    private function clearTerminatedInstances(): void
    {
        Log::info('Clear Terminated Records');

        BotInstance::findTerminated()->chunk(100, function ($instances) {
            foreach ($instances as $instance) {
                $instance->update([
                    'aws_public_ip' => null
                ]);
                Log::info("Clear public_ip / instance name: {$instance->tag_name}");
            }
        });
    }
}
