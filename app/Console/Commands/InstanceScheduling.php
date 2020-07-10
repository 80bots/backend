<?php

namespace App\Console\Commands;

use App\Events\InstanceStatusUpdated;
use App\Helpers\InstanceHelper;
use App\SchedulingInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:scheduling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var Carbon
     */
    private $now;

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
        $this->now = Carbon::now();

        Log::info("InstanceScheduling => cron call scheduling => {$this->now->toDateTimeString()}");

        try {
            $statuses = ['running', 'stopped'];
            foreach ($statuses as $status) {
                SchedulingInstance::has('details')
                    ->scheduling($status)
                    ->chunkById(100, function ($schedulers) {
                        $instances = InstanceHelper::getScheduleInstancesIds(
                            $schedulers,
                            $this->now
                        );
                        $this->startInstances($instances);
                    });
            }
        } catch (Throwable $throwable) {
            Log::error('InstanceScheduling Catch Error Message ' . $throwable->getMessage());
        }
    }

    /**
     * @param array $instances
     * @return void
     */
    private function startInstances(array $instances)
    {

        Log::info(print_r($instances, true));

        if (count($instances) > 0) {
            foreach ($instances as $instance) {

                $status     = $instance['status'];
                $instanceId = $instance['instances_id'];
                $userId     = $instance['user_id'];

                if (InstanceHelper::changeInstanceStatus($status, $instanceId, $userId)) {
                  broadcast(new InstanceStatusUpdated($userId));
                } else {
                    Log::info('Instances Are error to Scheduling');
                }
            }
        } else {
            Log::info('No Instances Are there to Scheduling');
        }
    }
}
