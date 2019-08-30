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
     */
    public function handle()
    {
        Log::info('Starting ChangeStatusToRunning for ' . $this->instance->id ?? '');

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

        Log::info('Completed ChangeStatusToRunning for ' . $this->instance->id ?? '');

        broadcast(new InstanceLaunched($this->instance, $this->user));
    }

    /**
     * @param Aws $aws
     * @return string|null
     */
    private function getCurrentInstanceStatus(Aws $aws): ?string
    {
        $result = $aws->describeInstances([$this->details->aws_instance_id]);

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
        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_STOPPED) {

            $result = $aws->startInstance([$this->details->aws_instance_id]);

            if ($result->hasKey('StartingInstances')) {

                $aws->waitUntil([$this->details->aws_instance_id]);

                $info = $this->getPublicIpAddressAndDns($aws);

                if ($info->isNotEmpty()) {

                    $this->instance->setAwsStatusRunning();

                    $newInstanceDetail = $this->details->replicate([
                        'end_time', 'total_time'
                    ]);
                    $newInstanceDetail->fill([
                        'start_time'        => $this->currentDate,
                        'aws_public_ip'     => $info['ip'],
                        'aws_public_dns'    => $info['dns']
                    ]);
                    $newInstanceDetail->save();
                }
            }

        } else {
            //dd($current);
        }
    }

    private function setStatusStopped(Aws $aws)
    {
        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_RUNNING) {

            $result = $aws->stopInstance([$this->details->aws_instance_id]);

            if ($result->hasKey('StoppingInstances')) {

                $this->instance->setAwsStatusStopped();

                $diffTime = CommonHelper::diffTimeInMinutes($this->details->start_time ?? null, $this->currentDate);

                $this->details->update([
                    'end_time'      => $this->currentDate,
                    'total_time'    => $diffTime
                ]);

                if ($diffTime > ($this->instance->cron_up_time ?? 0)) {

                    $upTime = $diffTime + ($this->instance->temp_up_time ?? 0);

                    $this->instance->update([
                        'cron_up_time'  => 0,
                        'temp_up_time'  => $upTime,
                        'up_time'       => $upTime,
                        'used_credit'   => CommonHelper::calculateUsedCredit($upTime)
                    ]);
                }
            }
        } else {
            //dd($current);
        }
    }

    private function setStatusTerminated(Aws $aws)
    {
        $result = $aws->terminateInstance([$this->details->aws_instance_id]);

        if ($result->hasKey('TerminatingInstances')) {

            $this->instance->setAwsStatusTerminated();
            $this->instance->delete();

            InstanceHelper::cleanUpTerminatedInstanceData($aws, $this->details);

            if ($this->region->created_instances > 0) {
                $this->region->decrement('created_instances');
            }
        }
    }

    private function getPublicIpAddressAndDns(Aws $aws): Collection
    {
        $result = $aws->describeInstances([$this->details->aws_instance_id]);

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
}
