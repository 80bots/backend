<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\SchedulingInstance;
use App\Services\Aws;
use App\BotInstance;
use App\BotInstancesDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstanceStopScheduling extends Command
{
    const CURRENT_STATE_STOPPING = 'stopping';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:stop';

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

        Log::info("InstanceStopScheduling => cron call stop scheduling => {$this->now->toDateTimeString()}");

        try {

            SchedulingInstance::has('details')
                ->scheduling('stop')
                ->chunkById(100, function ($schedulers) {

                    $instancesIds = InstanceHelper::getScheduleInstancesIds(
                        $schedulers,
                        $this->now
                    );

                    $this->stopInstances($instancesIds);
            });

        } catch (Throwable $throwable) {
            Log::info('Catch Error Message ' . $throwable->getMessage());
            Log::info('Catch Error File ' . $throwable->getFile());
        }
    }

    private function stopInstances(array $instancesIds)
    {
        if (count($instancesIds) > 0) {

            $aws = new Aws;

            $result = $aws->stopInstance($instancesIds);

            if ($result->hasKey('StoppingInstances')) {

                $startInstance = $result->get('StoppingInstances');

                foreach ($startInstance as $instanceDetail) {

                    $currentState   = $instanceDetail['CurrentState'];
                    $instanceId     = $instanceDetail['InstanceId'];

                    if ($currentState['Name'] == BotInstance::STATUS_STOPPED || $currentState['Name'] == self::CURRENT_STATE_STOPPING) {

                        $instance = BotInstance::findByInstanceId($instanceId)->first();

                        $instanceDetail = BotInstancesDetails::where([
                            'instance_id' => $instance->id,
                            'end_time' => null
                        ])->latest()->first();

                        if (! empty($instanceDetail)) {

                            $endTime = $this->now->toDateTimeString();

                            $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $endTime);

                            $instanceDetail->update([
                                'end_time' => $endTime,
                                'total_time' => $diffTime,
                            ]);

                            $upTime = $diffTime + ($instance->total_up_time ?? 0);

                            $instance->update([
                                'cron_up_time'  => 0,
                                'total_up_time' => $upTime,
                                'up_time'       => $upTime,
                                'used_credit'   => CommonHelper::calculateUsedCredit($upTime),
                                'aws_status'    => BotInstance::STATUS_STOPPED,
                            ]);
                        } else {
                            $instance->update([
                                'aws_status' => BotInstance::STATUS_STOPPED
                            ]);
                        }

                        Log::info('Instance Id ' . $instanceId . ' Stopped');

                    } else {
                        Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                    }
                }

            } else {
                Log::info('Instances are not Stopped');
                Log::info(print_r($instancesIds, true));
            }

        } else {
            Log::info('No Instances Are there to stop');
        }
    }
}
