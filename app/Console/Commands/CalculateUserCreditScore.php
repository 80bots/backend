<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Helpers\MailHelper;
use App\Services\Aws;
use App\User;
use App\BotInstance;
use App\BotInstancesDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateUserCreditScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:calculate-user-credit-score';

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
        $this->now      = Carbon::now();
        $users          = User::findUserInstances();
        // Get Low Credit Percentage
        $lowPercentage = [];

        foreach ($users as $user) {

            $instances = $user->instances ?? '';

            if (! empty($instances)) {

                $usedCreditArray = $instances->map(function ($item, $key) {
                    return $item->credits_used ?? 0;
                })->toArray();

                $creditScore = (float)$user->temp_remaining_credits - (float)array_sum($usedCreditArray);
                $user->remaining_credits = $creditScore;

                if (empty($user->temp_remaining_credits) || $user->temp_remaining_credits == 0) {
                    $user->temp_remaining_credits = $user->remaining_credits;
                }

                // User relation to get packages amount what package buy.
                if($user->UserSubscriptionPlan->count()){

                    $packageAmount = $user->UserSubscriptionPlan->first()->credit ?? 0;

                    // Find  Percentage by current credit and user package amount
                    // Percentage get on round and then match
                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);

                    // Check low percentge and if match then sent mail user credit is low please add credit.
                    if (in_array($creditScorePercentage, $lowPercentage)) {
                        // Check last mail Percentage sent and current creditScorePercentage if not match then send mail
                        if ($user->sent_email_status != $creditScorePercentage) {
                            // if send mail then save on users table on
                            $user->sent_email_status = $creditScorePercentage;

                            MailHelper::userCreditSendEmail($user);
                        }
                    }
                }

                $user->save();

                // user credits score is 0 then we will they user all instance will stop
                if ($creditScore <= 0 && $user->hasRole('User')) {

                    $instancesIds = $instances->map(function ($item, $key) {
                        return $item->aws_instance_id;
                    })->toArray();

                    // Stop Instance for the user
                    $this->stopUserAllInstances($instancesIds);
                }

                Log::info('Credits of email: ' . $user->email . ' is ' . $user->remaining_credits);
            }
        }
    }

    private function stopUserAllInstances(array $instancesIds)
    {
        $aws = new Aws;

        $describeInstance = $aws->describeInstances($instancesIds);

        if ($describeInstance->hasKey('Reservations')) {

            $instancesIds = collect($describeInstance->get('Reservations'))->map(function ($item, $key) {
                return $item['Instances'][0]['InstanceId'];
            })->toArray();

            $result = $aws->stopInstance($instancesIds);

            if ($result->hasKey('StoppingInstances')) {

                $stopInstances = $result->get('StoppingInstances');

                // Update instance  on user instance table
                foreach ($stopInstances as $instanceDetail) {

                    $CurrentState   = $instanceDetail['CurrentState'];
                    $instanceId     = $instanceDetail['InstanceId'];

                    if ($CurrentState['Name'] == 'stopped' || $CurrentState['Name'] == 'stopping') {

                        $UserInstance = BotInstance::findByInstanceId($instanceId)->first();
                        $UserInstance->status = 'stop';

                        $instanceDetail = BotInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();

                        if (! empty($instanceDetail)) {

                            $instanceDetail->end_time = $this->now->toDateTimeString();
                            $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $instanceDetail->end_date);
                            $instanceDetail->total_time = $diffTime;

                            if ($instanceDetail->save() && $diffTime > $UserInstance->cron_uptime) {

                                $tempUpTime = $UserInstance->total_uptime ?? 0;
                                $upTime = $diffTime + $tempUpTime;
                                $UserInstance->total_uptime = $upTime;
                                $UserInstance->uptime = $upTime;
                                $UserInstance->cron_uptime = 0;
                            }
                        }

                        if ($UserInstance->save()) {
                            Log::info('Instance Id ' . $instanceId . ' Stopped');
                        }

                    } else {
                        Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                    }
                }
            }
        }
    }
}
