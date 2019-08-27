<?php

namespace App\Console\Commands;

use App\BotInstance;
use App\Helpers\CommonHelper;
use App\Services\Aws;
use App\User;
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
        $this->now  = Carbon::now();

        User::chunkById(100, function ($users) {
            foreach ($users as $user) {

                $region = $user->region ? $user->region->code : config('aws.region', 'us-east-2');

                $aws = new Aws;
                $aws->ec2Connection($region);

                $instances = $user->instances()->findRunningInstance();

                if ($instances->isNotEmpty()) {

                    foreach ($instances as $instance) {

                        try {

//                            $describeInstance = $aws->describeInstances([$instance->aws_instance_id]);
//
//                            if ($describeInstance->hasKey('Reservations')) {
//
//                                $instanceResponse = $describeInstance->get('Reservations')[0]['Instances'][0];
//
//                                $cronUpTime = CommonHelper::diffTimeInMinutes($instanceResponse['LaunchTime']->format('Y-m-d H:i:s'), $this->now->toDateTimeString());
//
//                                $instance->cron_up_time = $cronUpTime;
//
//                                $instance->up_time = $cronUpTime + $instance->temp_up_time ?? 0;
//
//                                $instance->used_credit = CommonHelper::calculateUsedCredit($cronUpTime + $instance->temp_up_time ?? 0);
//
//                                Log::debug('instance id ' . $instance->aws_instance_id . ' Cron Up Time is ' . $cronUpTime);
//
//                            } else {
//                                Log::debug('instance id ' . $instance->aws_instance_id . ' already terminated');
//                                $instance->status = 'terminated';
//                            }

                        } catch (Throwable $throwable) {
                            Log::error($throwable->getMessage());
                        }

                    }
                }
            }
        });
    }
}
