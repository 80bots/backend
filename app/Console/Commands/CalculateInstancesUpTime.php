<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use App\User;
use Aws\Exception\AwsException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculateInstancesUpTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:calculate-up-time';

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

        User::chunkById(100, function ($users) {
            foreach ($users as $user) {

                $user->instances()
                    ->findRunningInstance()
                    ->chunkById(100, function ($instances) use ($user) {
                        foreach ($instances as $instance) {

                            try {

                                $aws = new Aws;
                                $aws->ec2Connection($instance->region->code);

                                $instanceDetail = $instance->details()->latest()->first();

                                $describeInstance = $aws->describeInstances([$instance->aws_instance_id], $instance->region->code);

                                if ($describeInstance->hasKey('Reservations')) {
                                    $reservations = collect($describeInstance->get('Reservations'));

                                    if ($reservations->isNotEmpty()) {

                                        $awsInstancesInfo = $reservations->first();
                                        $awsInstance = $awsInstancesInfo['Instances'][0];

                                        $cronUpTime = CommonHelper::diffTimeInMinutes($awsInstance['LaunchTime']->format('Y-m-d H:i:s'), $this->now->toDateTimeString());

                                        $instance->update([
                                            'cron_up_time'  => $cronUpTime,
                                            'up_time'       => $cronUpTime + $instance->total_up_time ?? 0,
                                        ]);

                                        Log::debug('instance id ' . $instance->aws_instance_id . ' Cron Up Time is ' . $cronUpTime);
                                    }

                                    unset($reservations, $awsInstancesInfo, $awsInstance);

                                } else {

                                    Log::debug('instance id ' . $instance->aws_instance_id . ' already terminated');
                                    $instance->setAwsStatusTerminated();

                                    InstanceHelper::cleanUpTerminatedInstanceData($aws, $instanceDetail);

                                    if ($instance->region->created_instances > 0) {
                                        $instance->region->decrement('created_instances');
                                    }
                                }

                            } catch (AwsException $exception) {
                                Log::error($exception->getMessage());
                            } catch (Throwable $throwable) {
                                Log::error($throwable->getMessage());
                            }

                            unset($aws, $instanceDetail, $describeInstance);
                        }
                });
            }
        });
    }
}
