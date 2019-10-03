<?php

namespace App\Console\Commands;

use App\Helpers\InstanceHelper;
use App\SchedulingInstance;
use App\Services\Aws;
use App\BotInstance;
use App\BotInstancesDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceStartScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:start';

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

        Log::info("InstanceStartScheduling => cron call start scheduling => {$this->now->toDateTimeString()}");

        try {

            SchedulingInstance::scheduling('start')->chunk(100, function ($schedulers) {
                $instancesIds = InstanceHelper::getScheduleInstancesIds(
                    $schedulers,
                    $this->now
                );

                $this->startInstances($instancesIds);
            });

        } catch (Throwable $throwable) {
            Log::info('InstanceStartScheduling Catch Error Message ' . $throwable->getMessage());
        }
    }

    private function startInstances(array $instancesIds)
    {
        if (count($instancesIds) > 0) {

            $aws    = new Aws;
            $result = $aws->startInstance($instancesIds);

            if ($result->hasKey('StartingInstances')) {

                $startInstance = $result->get('StartingInstances');

                foreach ($startInstance as $instanceDetail) {

                    $currentState   = $instanceDetail['CurrentState'];
                    $instanceId     = $instanceDetail['InstanceId'];

                    if ($currentState['Name'] == BotInstance::STATUS_PENDING || $currentState['Name'] == BotInstance::STATUS_RUNNING) {

                        $instance = BotInstance::findByInstanceId($instanceId)->first();

                        $instanceDetail = BotInstancesDetails::where(
                            [
                                'instance_id'   => $instance->id,
                                'end_time'      => null
                            ])
                            ->latest()
                            ->first();

                        if (empty($instanceDetail)) {
                            BotInstancesDetails::create([
                                'instance_id'   => $instance->id,
                                'start_time'    => $this->now->toDateTimeString()
                            ]);
                            $instance->update([
                                'aws_status'    => BotInstance::STATUS_RUNNING,
                                'start_time'    => $this->now->toDateTimeString()
                            ]);
                        } else {
                            $instance->update([
                                'aws_status' => BotInstance::STATUS_RUNNING
                            ]);
                        }

                        Log::info('Instance Id ' . $instanceId . ' Started');

                    } else {
                        Log::info('Instance Id ' . $instanceId . ' Not Started Successfully');
                    }
                }
            } else {
                Log::info('Instances are not Started');
                Log::info(print_r($instancesIds, true));
            }
        } else {
            Log::info('No Instances Are there to Start');
        }
    }
}
