<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\InstanceSessionsHistory;
use App\SchedulingInstance;
use App\Services\Aws;
use App\UserInstances;
use App\UserInstancesDetails;
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
     * @var string
     */
    private $currentDate;

    /**
     * @var int
     */
    private $currentTime;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $now = Carbon::now();

        $this->currentDate = $now->toDateTimeString();
        $this->currentTime = Carbon::parse($now->format('D h:i A'))->getTimestamp();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('cron call stop scheduling');

        try {

            $instancesIds = [];
            $startSchedule = SchedulingInstance::scheduling('stop')->get();

            foreach ($startSchedule as $scheduler) {

                $userInstance = $scheduler->userInstances ?? null;

                if (! empty($scheduler->details) && $userInstance) {

                    foreach ($scheduler->details as $detail) {

                        if (InstanceHelper::isScheduleInstance($detail, $this->currentTime)) {

                            //Save the session history
                            InstanceSessionsHistory::create([
                                'scheduling_instances_id'   => $scheduler->id,
                                'user_id'                   => $scheduler->user_id,
                                'schedule_type'             => $scheduler->schedule_type,
                                'selected_time'             => $scheduler->selected_time,
                            ]);

                            array_push($instancesIds, $userInstance->aws_instance_id);
                        }
                    }
                }
            }

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

                        $userInstance = UserInstances::findByInstanceId($instanceId)->first();
                        $userInstance->status = 'stop';

                        $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $userInstance->id, 'end_time' => null])->latest()->first();

                        if (!empty($instanceDetail)) {

                            $instanceDetail->end_time = $this->currentDate;

                            $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $instanceDetail->end_date);
                            $instanceDetail->total_time = $diffTime;

                            if ($instanceDetail->save()) {
                                if ($diffTime > $userInstance->cron_up_time) {
                                    $userInstance->cron_up_time = 0;
                                    $tempUpTime = !empty($userInstance->temp_up_time) ? $userInstance->temp_up_time : 0;
                                    $upTime = $diffTime + $tempUpTime;
                                    $userInstance->temp_up_time = $upTime;
                                    $userInstance->up_time = $upTime;
                                    $userInstance->used_credit = CommonHelper::calculateUsedCredit($upTime);
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
