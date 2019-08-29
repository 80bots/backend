<?php

namespace App\Jobs;

use App\BotInstance;
use App\BotInstancesDetails;
use App\Events\InstanceLaunched;
use App\Helpers\CommonHelper;
use App\Services\Aws;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class InstanceChangeStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var BotInstance
     */
    protected $instance;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var BotInstancesDetails
     */
    protected $details;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $currentDate;

    /**
     * @var string
     */
    protected $region;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     * @param User $user
     * @param string $status
     */
    public function __construct(BotInstance $instance, User $user, string $status)
    {
        $this->instance     = $instance;
        $this->user         = $user;
        $this->details      = $instance->details()->latest()->first();
        $this->currentDate  = Carbon::now()->toDateTimeString();
        $this->status       = $status;
        $regions            = Aws::getEc2Regions();

        if (! empty($regions) && in_array($instance->region->code, $regions)) {
            $this->region = $instance->region->code;
        } else {
            $this->region = config('aws.region', 'us-east-2');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting ChangeStatusToRunning for ' . $this->instance->id ?? '');

        $aws = new Aws;
        $aws->ec2Connection($this->region);

        $aws->waitUntil([$this->details->aws_instance_id ?? null]);

        dd("AAA");

        switch ($this->status) {

            case BotInstance::STATUS_RUNNING:
//                // TODO: Check result
//                $result = $aws->startInstance([$instanceDetail->aws_instance_id ?? null]);
//
//                if ($result->hasKey('StartingInstances')) {
//
//                    $startingInstances  = $result->get('StartingInstances');
//                    $awsInstanceId      = $startingInstances[0]['InstanceId'];
//
//                    dd($startingInstances);
//                }
                break;
            case BotInstance::STATUS_STOPPED:
//                // TODO: Check result
//                $aws->stopInstance([$instanceDetail->aws_instance_id ?? null]);
//
//                $instance->fill(['aws_status' => BotInstance::STATUS_STOPPED]);
//
//                $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time ?? null, $currentDate);
//
//                $instanceDetail->fill([
//                    'end_time'      => $currentDate,
//                    'total_time'    => $diffTime
//                ]);
//
//                if ($instanceDetail->save()) {
//                    if ($diffTime > ($instance->cron_up_time ?? 0)) {
//                        $upTime = $diffTime + ($instance->temp_up_time ?? 0);
//
//                        $instance->fill([
//                            'cron_up_time'  => 0,
//                            'temp_up_time'  => $upTime,
//                            'up_time'       => $upTime,
//                            'used_credit'   => CommonHelper::calculateUsedCredit($upTime)
//                        ]);
//                    }
//                }
                break;
            default:
//                $instance->fill(['aws_status' => BotInstance::STATUS_TERMINATED]);
//                // TODO: Check result
//                $aws->terminateInstance([$instanceDetail->aws_instance_id ?? null]);
//                $this->cleanUpTerminatedInstanceData($aws, $instanceDetail);

                break;
        }

//        if ($instance->isAwsStatusTerminated()) {
//            $instance->delete();
//            //
//            if ($awsRegion->created_instances > 0) {
//                $awsRegion->decrement('created_instances');
//            }
//        }

        $aws->waitUntil([$this->details->aws_instance_id ?? null]);

        $info = $aws->describeInstances([$this->details->aws_instance_id ?? null]);

        $instanceDetail = $this->instance->details()->latest()->first();

        //$this->instance->fill(['aws_status' => BotInstance::STATUS_RUNNING]);

//        $newInstanceDetail = $instanceDetail->replicate([
//            'end_time', 'total_time'
//        ]);
//        $newInstanceDetail->fill(['start_time' => $this->currentDate]);
//        $newInstanceDetail->save();

        Log::debug(print_r($info, true));

        Log::info('Completed ChangeStatusToRunning for ' . $this->instance->id ?? '');

        broadcast(new InstanceLaunched($this->instance, $this->user));
    }
}
