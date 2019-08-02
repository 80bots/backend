<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Jobs\StoreUserInstance;
use App\Services\Aws;
use App\User;
use App\UserInstances;
use App\UserInstancesDetails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class AppController extends Controller
{
    protected $credit;

    public function __construct()
    {
        $this->credit = CommonHelper::calculateCredit();
    }

    /**
     * store bot_id in session
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function storeBotIdInSession(Request $request)
    {
        $userInstance = new UserInstances;
        $userInstance->fill([
            'user_id'   => $request->input('user_id'),
            'bot_id'    => $request->input('bot_id'),
        ]);
        if($userInstance->save()){
            Log::debug('IN-queued Instance : '.json_encode($userInstance));
            Session::put('instance_id', $userInstance->id ?? null);
            return response()->json(['type' => 'success', 'data' => $userInstance->id ?? null],200);
        }

        return response()->json(['type' => 'error','data' => ''],200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function checkBotIdInQueue(Request $request)
    {
        $instanceIds = UserInstances::select('id')
            ->where('user_id', Auth::id())
            ->where('is_in_queue', '=', 1)
            ->pluck('id')->toArray();

        $instanceIds = array_unique($instanceIds);

        foreach($instanceIds as $instanceId) {
            dispatch(new StoreUserInstance($instanceId, Auth::user()));
        }

        return response()->json(['type' => 'success', 'data' => $instanceIds], 200);
    }

    /**
     * Change status ec2 instance
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function changeStatus(Request $request)
    {
        try{

            $instanceObj = UserInstances::find($request->input('id'));

            if (empty($instanceObj)) {
                session()->flash('error', 'This user instance does not exist');
                return response()->json([
                    'error'     => true,
                    'message'   => 'This user instance does not exist'
                ]);
            }

            $aws = new Aws;

            $currentDate = Carbon::now()->toDateTimeString();

            $describeInstancesResponse = $aws->describeInstances([$instanceObj->aws_instance_id]);

            if (! $describeInstancesResponse->hasKey('Reservations')) {
                return response()->json([
                    'error'     => true,
                    'message'   => ''
                ]);
            }

            $reservationObj = $describeInstancesResponse->get('Reservations');

            if(empty($reservationObj)){
                $instanceObj->fill(['status' => 'terminated']);
                $instanceObj->save();
                session()->flash('error', 'This instance does not exist');
                return response()->json([
                    'error'     => true,
                    'message'   => 'This instance does not exist'
                ]);
            }

            $instanceStatus = $reservationObj[0]['Instances'][0]['State']['Name'];

            if ($instanceStatus === 'terminated') {
                $instanceObj->fill(['status' => 'terminated']);
                $instanceObj->save();
                session()->flash('error', 'This instance is already terminated');
                return response()->json([
                    'error'     => true,
                    'message'   => 'This instance is already terminated'
                ]);
            }

            switch ($request->input('status')) {

                case 'start':

                    $instanceObj->fill(['status' => 'running']);

                    $aws->startInstance([$instanceObj->aws_instance_id]);

                    UserInstancesDetails::create([
                        'user_instance_id' => $instanceObj->id,
                        'start_time' => $currentDate
                    ]);

                    break;
                case 'stop':

                    $instanceObj->fill(['status' => 'stop']);

                    $aws->stopInstance([$instanceObj->aws_instance_id]);

                    $instanceDetail = UserInstancesDetails::where('user_instance_id', '=', $request->input('id'))
                        ->latest()
                        ->first();

                    $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $currentDate);

                    $instanceDetail->fill([
                        'end_time' => $currentDate,
                        'total_time' => $diffTime
                    ]);

                    if ($instanceDetail->save()) {
                        if($diffTime > $instanceObj->cron_up_time){
                            $instanceObj->cron_up_time = 0;
                            $tempUpTime = !empty($instanceObj->temp_up_time) ? $instanceObj->temp_up_time: 0;
                            $upTime = $diffTime + $tempUpTime;
                            $instanceObj->temp_up_time = $upTime;
                            $instanceObj->up_time = $upTime;
                            $instanceObj->used_credit = CommonHelper::calculateUsedCredit($upTime);
                        }
                    }

                    break;
                default:
                    $instanceObj->fill(['status' => 'terminated']);
                    $aws->terminateInstance([$instanceObj->aws_instance_id]);
                    break;
            }

            if($instanceObj->save()){
                session()->flash('success', "Instance {$request->input('status')} successfully!");
                return response()->json([
                    'error'     => false,
                    'message'   => "Instance {$request->input('status')} successfully!"
                ]);
            }

            session()->flash('error', "Instance {$request->input('status')} not successfully!");
            return response()->json([
                'error'     => true,
                'message'   => "Instance {$request->input('status')} not successfully!"
            ]);

        } catch (Throwable $throwable){
            session()->flash('error', $throwable->getMessage());
            return response()->json([
                'error'     => true,
                'message'   => $throwable->getMessage()
            ]);
        }
    }

    public function UserActivation($id)
    {
        $checkActivationToken = User::where('verification_token', $id)->first();

        if (isset($checkActivationToken) && !empty($checkActivationToken)) {
            $checkActivationToken->verification_token = '';
            $checkActivationToken->status = 'active';
            if ($checkActivationToken->save()) {
                return redirect(route('login'))->with('success', 'Your Account will be verified successfully!!');
            } else {
                return redirect(route('login'))->with('error', 'Please Try After Some Time');
            }
        } else {
            return redirect(route('login'))->with('error', 'Unauthorized');
        }
    }
}

