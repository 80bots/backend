<?php

namespace App\Console\Commands;

use App\Helpers\InstanceHelper;
use App\InstanceSessionsHistory;
use App\SchedulingInstance;
use App\Services\Aws;
use App\UserInstances;
use App\UserInstancesDetails;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
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
        Log::info('cron call start scheduling');

        try {

            $instancesIds = [];
            $startSchedule = SchedulingInstance::scheduling('start')->get();

            foreach ($startSchedule as $scheduler) {

                $userInstance = $scheduler->userInstances ?? null;

                if (! empty($scheduler->details) && !empty($userInstance)) {

                    foreach ($scheduler->details as $detail) {

                        if (InstanceHelper::isScheduleInstance($detail, $this->currentTime)) {

                            $tz = CarbonTimeZone::create($detail->time_zone);

                            Log::info($detail->scheduling_instances_id);

                            //Save the session history
                            InstanceSessionsHistory::create([
                                'scheduling_instances_id' => $scheduler->id,
                                'user_id' => $scheduler->user_id,
                                'schedule_type' => $scheduler->schedule_type,
                                'cron_data' => $scheduler->cron_data,
                                'current_time_zone' => $tz->toRegionName(),
                                'selected_time' => $detail->selected_time,
                            ]);

                            array_push($instancesIds, $userInstance->aws_instance_id);
                        }

                    }
                }
            }

            $this->startInstances($instancesIds);

        } catch (Throwable $throwable) {
            Log::info('Catch Error Message ' . $throwable->getMessage());
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

                    if ($currentState['Name'] == 'pending' || $currentState['Name'] == 'running') {

                        $userInstance = UserInstances::findByInstanceId($instanceId)->first();
                        $userInstance->status = 'running';

                        if ($userInstance->save()) {
                            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $userInstance->id, 'end_time' => null])->latest()->first();
                            if (empty($instanceDetail)) {
                                UserInstancesDetails::create([
                                    'user_instance_id' => $userInstance->id,
                                    'start_time' => $this->currentDate
                                ]);
                            }
                            Log::info('Instance Id ' . $instanceId . ' Started');
                        }
                    } else {
                        Log::info('Instance Id ' . $instanceId . ' Not Started Successfully');
                    }
                }
            } else {
                Log::info('Instances are not Started [' . $instancesIds . ']');
            }
        } else {
            Log::info('No Instances Are there to Start');
        }
    }
}
