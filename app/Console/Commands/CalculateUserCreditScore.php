<?php

namespace App\Console\Commands;

use App\CreditPercentage;
use App\Helpers\CommonHelper;
use App\Services\Aws;
use App\User;
use App\UserInstances;
use App\UserInstancesDetails;
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
        // Get Low creditPercentage
        $lowPercentage  = CreditPercentage::pluck('percentage')->toArray();

        foreach ($users as $userObj) {

            $userInstances = $userObj->UserInstances ?? '';

            if (! empty($userInstances)) {

                $usedCreditArray = $userInstances->map(function ($item, $key) {
                    return $item->used_credit ?? 0;
                })->toArray();

                $creditScore = (float)$userObj->temp_remaining_credits - (float)array_sum($usedCreditArray);
                $userObj->remaining_credits = $creditScore;

                if (empty($userObj->temp_remaining_credits) || $userObj->temp_remaining_credits == 0) {
                    $userObj->temp_remaining_credits = $userObj->remaining_credits;
                }

                // User relation to get packages amount what package buy.
                if($userObj->UserSubscriptionPlan->count()){

                    $packageAmount = $userObj->UserSubscriptionPlan->first()->credit ?? 0;

                    // Find  Percentage by current credit and user package amount
                    // Percentage get on round and then match
                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);

                    // Check low percentge and if match then sent mail user credit is low please add credit.
                    if (in_array($creditScorePercentage, $lowPercentage)) {
                        // Check last mail Percentage sent and current creditScorePercentage if not match then send mail
                        if ($userObj->sent_email_status != $creditScorePercentage) {
                            // if send mail then save on users table on
                            $userObj->sent_email_status = $creditScorePercentage;
                            $dataResult = User::UserCreditSendEmail($userObj);
                        }
                    }
                }

                $userObj->save();

                // user credits score is 0 then we will they user all instance will stop
                if ($creditScore <= 0 && $userObj->hasRole('User')) {

                    $instancesIds = $userInstances->map(function ($item, $key) {
                        return $item->aws_instance_id;
                    })->toArray();

                    // Stop Instance for the user
                    $this->stopUserAllInstances($instancesIds);
                }

                Log::info('Credits of email: ' . $userObj->email . ' is ' . $userObj->remaining_credits);
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

                        $UserInstance = UserInstances::findByInstanceId($instanceId)->first();
                        $UserInstance->status = 'stop';

                        $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();

                        if (! empty($instanceDetail)) {

                            $instanceDetail->end_time = $this->now->toDateTimeString();
                            $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $instanceDetail->end_date);
                            $instanceDetail->total_time = $diffTime;

                            if ($instanceDetail->save() && $diffTime > $UserInstance->cron_up_time) {

                                $tempUpTime = $UserInstance->temp_up_time ?? 0;
                                $upTime = $diffTime + $tempUpTime;
                                $UserInstance->temp_up_time = $upTime;
                                $UserInstance->up_time = $upTime;
                                $UserInstance->cron_up_time = 0;
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
