<?php

namespace App\Observers;

use App\BotInstance;
use App\BotInstancesDetails;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Jobs\SyncS3Objects;
use App\SchedulingInstancesDetails;
use App\SchedulingInstance;
use App\Services\Aws;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SchedulingDetailsObserver
{
    /**
     * Handle the scheduling instances details "created" event.
     *
     * @param SchedulingInstancesDetails $query
     * @return void
     */
    public function created(SchedulingInstancesDetails $query)
    {
        Log::info('created');
        $schedulingDetail = $query;
        $scheduling = $query->schedulingInstance;

        if ($scheduling->status === 'active') {
            switch ($schedulingDetail->schedule_type) {
                case SchedulingInstancesDetails::TYPE_START:
                    break;
                case SchedulingInstancesDetails::TYPE_STOP:
                    break;
                default:
                    break;
            }
        } else {
            Log::info(print_r($scheduling->status, true));
        }

//        $this->now = Carbon::now();
//
//        Log::info("InstanceStopScheduling => cron call stop scheduling => {$this->now->toDateTimeString()}");
//
//        try {
//
//            SchedulingInstance::has('details')
//                ->scheduling('stop')
//                ->chunkById(100, function ($schedulers) {
//
//                    $instancesIds = InstanceHelper::getScheduleInstancesIds(
//                        $schedulers,
//                        $this->now
//                    );
//
//                    $this->stopInstances($instancesIds);
//                });
//
//        } catch (Throwable $throwable) {
//            Log::info('Catch Error Message ' . $throwable->getMessage());
//            Log::info('Catch Error File ' . $throwable->getFile());
//        }
    }

    /**
     * @param array $instancesIds
     * @return void
     * @throws Exception
     */
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
                                'aws_status'    => BotInstance::STATUS_STOPPED,
                            ]);
                        } else {
                            $instance->update([
                                'aws_status' => BotInstance::STATUS_STOPPED
                            ]);
                        }

                        // Update directory tree on instance status change
                        dispatch(new SyncS3Objects($instance));

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
