<?php

namespace App\Console\Commands;

use App\CreditPercentage;
use App\Helpers\CommonHelper;
use App\Helpers\CreditUsageHelper;
use App\Helpers\MailHelper;
use App\Order;
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
        // Get Low creditPercentage
        $lowPercentage  = CreditPercentage::pluck('percentage')->toArray();

        User::whereHas('instances')->chunkById(100, function ($users) use ($lowPercentage) {
            foreach ($users as $user) {

                foreach ($user->instances as $instance) {

                    $order = $instance->order ?? null;

                    if (empty($order)) {
                        $order = Order::create([
                            'user_id' => $user->id,
                            'instance_id' => $instance->id
                        ]);
                    }

                    $used = $instance->used_credit - $order->credits;

                    if ($user->isUser() && $used >= $user->credits) {
                        $instancesId = $instance->aws_instance_id ?? null;
                        $this->stopUserAllInstances([$instancesId], $instance->region->code);
                    }

                    if ($used > 0) {
                        CreditUsageHelper::usingTheBot($user, $instance->bot, $instance->id, $used);
                    }

                    $order->increment('credits', $used);
                    $user->decrement('credits', $used);
                }

                // TODO: Need to add check for remaining credits and send message to the user about credits lack
                // User relation to get packages amount what package buy.
//                if($user->UserSubscriptionPlan->count()){
//
//                    $packageAmount = $user->UserSubscriptionPlan->first()->credit ?? 0;
//
//                    // Find  Percentage by current credit and user package amount
//                    // Percentage get on round and then match
//                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);
//
//                    // Check low percentge and if match then sent mail user credit is low please add credit.
//                    if (in_array($creditScorePercentage, $lowPercentage)) {
//                        // Check last mail Percentage sent and current creditScorePercentage if not match then send mail
//                        if ($user->sent_email_status != $creditScorePercentage) {
//                            // if send mail then save on users table on
//                            $user->sent_email_status = $creditScorePercentage;
//
//                            MailHelper::userCreditSendEmail($user);
//                        }
//                    }
//                }

                Log::info('Credits of email: ' . $user->email . ' is ' . $user->credits);

            }
        });
    }

    private function stopUserAllInstances(array $instancesIds, string $region)
    {
        $aws = new Aws;

        $describeInstance = $aws->describeInstances($instancesIds, $region);

        if ($describeInstance->hasKey('Reservations')) {

            $instancesIds = collect($describeInstance->get('Reservations'))->map(function ($item, $key) {
                return $item['Instances'][0]['InstanceId'];
            })->toArray();

            $result = $aws->stopInstance($instancesIds);

            if ($result->hasKey('StoppingInstances')) {

                $stopInstances = $result->get('StoppingInstances');

                // Update instance  on user instance table
                foreach ($stopInstances as $stopInstance) {

                    $CurrentState   = $stopInstance['CurrentState'];
                    $instanceId     = $stopInstance['InstanceId'];

                    if ($CurrentState['Name'] == 'stopped' || $CurrentState['Name'] == 'stopping') {

                        $instance = BotInstance::findByInstanceId($instanceId)->first();
                        $instance->aws_status = BotInstance::STATUS_STOPPED;

                        $stopInstance = BotInstancesDetails::where(['instance_id' => $instance->id, 'end_time' => null])->latest()->first();

                        if (! empty($stopInstance)) {

                            $stopInstance->end_time = $this->now->toDateTimeString();
                            $diffTime = CommonHelper::diffTimeInMinutes($stopInstance->start_time, $stopInstance->end_date);
                            $stopInstance->total_time = $diffTime;

                            if ($stopInstance->save() && $diffTime > $instance->cron_up_time) {
                                $tempUpTime = $instance->total_up_time ?? 0;
                                $upTime = $diffTime + $tempUpTime;
                                $instance->total_up_time = $upTime;
                                $instance->up_time = $upTime;
                                $instance->cron_up_time = 0;
                            }
                        }

                        if ($instance->save()) {
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
