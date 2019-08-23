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

            $instancesIds = InstanceHelper::getScheduleInstancesIds(
                SchedulingInstance::scheduling('start')->get(),
                $this->now
            );

            $this->startInstances($instancesIds);

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

                    if ($currentState['Name'] == 'pending' || $currentState['Name'] == 'running') {

                        $userInstance = BotInstance::findByInstanceId($instanceId)->first();
                        $userInstance->status = 'running';

                        if ($userInstance->save()) {

                            $instanceDetail = BotInstancesDetails::where(
                                [
                                    'user_instance_id'  => $userInstance->id,
                                    'end_time'          => null
                                ])
                                ->latest()
                                ->first();

                            if (empty($instanceDetail)) {
                                BotInstancesDetails::create([
                                    'user_instance_id'  => $userInstance->id,
                                    'start_time'        => $this->now->toDateTimeString()
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
