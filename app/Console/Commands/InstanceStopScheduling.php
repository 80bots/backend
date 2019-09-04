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

            $instancesIds = InstanceHelper::getScheduleInstancesIds(
                SchedulingInstance::scheduling('stop')->get(),
                $this->now
            );

            $this->stopInstances($instancesIds);

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

                    if ($currentState['Name'] == 'stopped' || $currentState['Name'] == 'stopping') {

                        $userInstance = BotInstance::findByInstanceId($instanceId)->first();
                        $userInstance->status = 'stop';

                        $instanceDetail = BotInstancesDetails::where([
                            'user_instance_id' => $userInstance->id,
                            'end_time' => null
                        ])->latest()->first();

                        if (!empty($instanceDetail)) {

                            $instanceDetail->end_time = $this->now->toDateTimeString();

                            $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $instanceDetail->end_date);
                            $instanceDetail->total_time = $diffTime;

                            if ($instanceDetail->save()) {
                                if ($diffTime > $userInstance->cron_uptime) {
                                    $userInstance->cron_uptime = 0;
                                    $tempUpTime = $userInstance->total_uptime ?? 0;
                                    $upTime = $diffTime + $tempUpTime;
                                    $userInstance->total_uptime = $upTime;
                                    $userInstance->uptime = $upTime;
                                    $userInstance->credits_used = CommonHelper::calculateCreditsUsed($upTime);
                                }
                            }
                        }

                        if ($userInstance->save()) {
                            Log::info('Instance Id ' . $instanceId . ' Stopped');
                        }

                    } else {
                        Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                    }
                }

            } else {
                Log::info('Instances are not Stopped [' . $instancesIds . ']');
            }

        } else {
            Log::info('No Instances Are there to stop');
        }
    }
}
