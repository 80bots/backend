<?php

namespace App\Jobs;

use App\AwsRegion;
use App\BotInstance;
use App\BotInstancesDetails;
use App\Events\InstanceLaunched;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
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
     * @var AwsRegion
     */
    protected $region;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     * @param User $user
     * @param AwsRegion $region
     * @param string $status
     */
    public function __construct(BotInstance $instance, User $user, AwsRegion $region, string $status)
    {
        $this->instance     = $instance;
        $this->user         = $user;
        $this->region       = $region;
        $this->details      = $instance->details()->latest()->first();
        $this->currentDate  = Carbon::now()->toDateTimeString();
        $this->status       = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('Starting InstanceChangeStatus for ' . $this->instance->id ?? '');

        $aws = new Aws;
        $aws->ec2Connection($this->region->code);

        switch ($this->status) {
            case BotInstance::STATUS_RUNNING:
                $this->setStatusRunning($aws);
                break;
            case BotInstance::STATUS_STOPPED:
                $this->setStatusStopped($aws);
                break;
            default:
                $this->setStatusTerminated($aws);
                break;
        }

        Log::info('Completed InstanceChangeStatus for ' . $this->instance->id ?? '');
    }

    /**
     * @param Aws $aws
     * @return string|null
     */
    private function getCurrentInstanceStatus(Aws $aws): ?string
    {
        $result = $aws->describeInstances([$this->instance->aws_instance_id], $this->region->code);

        if ($result->hasKey('Reservations')) {
            $reservations = collect($result->get('Reservations'));
            if ($reservations->isNotEmpty()) {
                $instance = $reservations->first()['Instances'][0];
                return $instance['State']['Name'];
            }
        }

        return null;
    }

    private function setStatusRunning(Aws $aws)
    {
        if ($this->user->credits < 1) {
            // TODO: Add a message notifying the user about credits lack for changing the status
            broadcast(new InstanceLaunched($this->instance, $this->user));
        }

        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_STOPPED) {

            $result = $aws->startInstance([$this->instance->aws_instance_id]);

            if ($result->hasKey('StartingInstances')) {

                $aws->waitUntil([$this->instance->aws_instance_id]);

                $info = $this->getPublicIpAddressAndDns($aws);

                if ($info->isNotEmpty()) {

                    $this->instance->setAwsStatusRunning();

                    $newInstanceDetail = $this->details->replicate([
                        'end_time', 'total_time'
                    ]);

                    $newInstanceDetail->fill([
                        'start_time'        => $this->currentDate,
                        'aws_public_dns'    => $info['dns']
                    ]);

                    $newInstanceDetail->save();

                    $this->instance->update([
                        'aws_public_ip' => $info['ip'],
                        'start_time'    => $this->currentDate,
                    ]);
                }

                broadcast(new InstanceLaunched($this->instance, $this->user));
            }

        } else {
            dispatch(new InstanceChangeStatus(
                $this->instance,
                $this->user,
                $this->region,
                $this->status)
            )->delay(30);
        }
    }

    /**
     * @param Aws $aws
     */
    private function setStatusStopped(Aws $aws)
    {
        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_RUNNING) {

            $result = $aws->stopInstance([$this->instance->aws_instance_id]);

            if ($result->hasKey('StoppingInstances')) {

                $this->instance->setAwsStatusStopped();

                $this->updateUpTime();

                broadcast(new InstanceLaunched($this->instance, $this->user));
            }

        } else {
            dispatch(new InstanceChangeStatus(
                    $this->instance,
                    $this->user,
                    $this->region,
                    $this->status)
            )->delay(30);
        }
    }

    /**
     * @param Aws $aws
     * @throws \Exception
     */
    private function setStatusTerminated(Aws $aws)
    {
        $terminateInstance = $aws->terminateInstance([$this->instance->aws_instance_id]);

        if ($terminateInstance->hasKey('TerminatingInstances')) {

            $result = collect($terminateInstance->get('TerminatingInstances'));

            $previousState = $result->map(function ($item, $key) {
                return $item['PreviousState']['Name'] ?? null;
            })->first();

            // Check whether old status was 'running'
            if ($previousState === BotInstance::STATUS_RUNNING) {
                $this->updateUpTime();
            }

            $this->instance->setAwsStatusTerminated();
            $this->instance->delete();

            InstanceHelper::cleanUpTerminatedInstanceData($aws, $this->details);

            if ($this->region->created_instances > 0) {
                $this->region->decrement('created_instances');
            }

            broadcast(new InstanceLaunched($this->instance, $this->user));
        }
    }

    /**
     * @param Aws $aws
     * @return Collection
     */
    private function getPublicIpAddressAndDns(Aws $aws): Collection
    {
        $result = $aws->describeInstances([$this->instance->aws_instance_id], $this->region->code);

        if ($result->hasKey('Reservations')) {
            $reservations = collect($result->get('Reservations'));
            if ($reservations->isNotEmpty()) {
                $instance = $reservations->first()['Instances'][0];

                return collect([
                    'ip'    => $instance['PublicIpAddress'],
                    'dns'   => $instance['PublicDnsName']
                ]);
            }
        }

        return collect([]);
    }

    private function updateUpTime(): void
    {
        $diffTime = CommonHelper::diffTimeInMinutes($this->details->start_time, $this->currentDate);

        $this->details->update([
            'end_time'      => $this->currentDate,
            'total_time'    => $diffTime
        ]);

        $upTime = $diffTime + $this->instance->total_up_time;

        $this->instance->update([
            'cron_up_time'  => 0,
            'total_up_time' => $upTime,
            'up_time'       => $upTime,
            'used_credit'   => CommonHelper::calculateUsedCredit($upTime)
        ]);
    }
}
