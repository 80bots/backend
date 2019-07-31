<?php

namespace App\Console\Commands;

use App\CreditPercentage;
use App\Helpers\CommonHelper;
use App\Services\Aws;
use App\User;
use App\UserInstances;
use App\UserInstancesDetails;
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
        $aws    = new Aws;
        $users  = User::findUserInstances();

        $currentDate = date('Y-m-d H:i:s');

        // Get Low CreditPercentage
        $CreditPercentage = CreditPercentage::get();
        $lowPercentage  = array();
        foreach ($CreditPercentage  as $row) {
            $lowPercentage[] = $row->percentage;
        }
        //End Get low CreditPercentage

        foreach ($users as $UserObj) {

            $UserInstances = $UserObj->UserInstances ?? '';

            if (! empty($UserInstances)) {
                $usedCreditArray = [];
                $instancesIds = [];
                foreach ($UserInstances as $userInstance) {
                    $usedCredit = isset($userInstance->used_credit) ? $userInstance->used_credit : '0';
                    if($userInstance->status == 'running'){
                        array_push($usedCreditArray, $usedCredit);
                    }
                    // add instancesIds
                    array_push($instancesIds, $userInstance->aws_instance_id);
                }
                $totalUsedCredit = array_sum($usedCreditArray);
                $user = User::find($UserObj->id);
                if (empty($UserObj->temp_remaining_credits) || $UserObj->temp_remaining_credits == 0) {
                    $user->temp_remaining_credits = $UserObj->remaining_credits;
                }
                $temp_credit = $UserObj->temp_remaining_credits;
                $creditScore = (float)$temp_credit - (float)$totalUsedCredit;
                $user->remaining_credits = $creditScore;
                //User relation to get packages amount what package buy.

                if(count($UserObj->UserSubscriptionPlan) > 0){
                    $packageAmount = $UserObj->UserSubscriptionPlan[0]->credit;

                    // Find  Percentage by current credit and user package amount
                    // Percentage get on round and then match
                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);

                    // Check low percentge and if match then sent mail user credit is low please add credit.
                    if(in_array($creditScorePercentage, $lowPercentage))
                    {
                        // Check last mail Percentage sent and current creditScorePercentage if not match then send mail
                        if($UserObj->sent_email_status != $creditScorePercentage)
                        {
                            // if send mail then save on users table on
                            $UserObj->sent_email_status = $creditScorePercentage;
                            $dataResult = User::UserCreditSendEmail($UserObj);
                        }
                    }
                }
                $user->save();

                // user credits score is 0 then we will they user all instance will stop
                if($creditScore <= 0)
                {
                    // below if in we check admin role 1-is admin Role so we have checked.
                    if($UserObj->role_id != 1)
                    {
                        // Stop Instance for the user
                        $result = $aws->stopInstance($instancesIds);
                        $startInstance = $result->get('StoppingInstances');
                        // Update instance  on user instance table
                        foreach ($startInstance as $instanceDetail) {
                            $CurrentState = $instanceDetail['CurrentState'];
                            $instanceId = $instanceDetail['InstanceId'];
                            if ($CurrentState['Name'] == 'stopped' || $CurrentState['Name'] == 'stopping') {
                                $UserInstance = UserInstances::findByInstanceId($instanceId)->first();
                                $UserInstance->status = 'stop';
                                $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();
                                if (!empty($instanceDetail)) {
                                    $instanceDetail->end_time = $currentDate;
                                    $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $instanceDetail->end_date);
                                    $instanceDetail->total_time = $diffTime;
                                    if ($instanceDetail->save()) {
                                        if ($diffTime > $UserInstance->cron_up_time) {
                                            $UserInstance->cron_up_time = 0;
                                            $tempUpTime = !empty($UserInstance->temp_up_time) ? $UserInstance->temp_up_time : 0;
                                            $upTime = $diffTime + $tempUpTime;
                                            $UserInstance->temp_up_time = $upTime;
                                            $UserInstance->up_time = $upTime;
                                        }
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

                Log::info('Credits of email: ' . $UserObj->email . ' is ' . $UserObj->remaining_credits);
            }
        }
    }
}
