<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\ScheduleCollection;
use App\SchedulingInstance;
use App\SchedulingInstancesDetails;
use App\UserInstance;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class SchedulesController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {

            $resource = SchedulingInstance::findByUserId(Auth::id());

            // TODO: Add Filters

            return new ScheduleCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }

    public function create()
    {
        try {
            $instances = UserInstance::where(['status' => 'stop','user_id'=> Auth::id()])->get();
            return view('user.scheduling.create', compact('instances'));
        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return redirect(route('scheduling.index'));
        }
    }

    public function convertTimeToUserTime($day, $str, $userTimezone, $format = 'D h:i A')
    {
        try {
            $new_str = new DateTime("{$day} {$str}", new DateTimeZone($userTimezone));
            return $new_str->format($format);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return null;
        }
    }

    public function convertTimeToUserZone(Request $request)
    {
        $format = 'h:i A';
        if(is_null($request->str) || empty($str) || $request->str === "null"){
            return '';
        }
        $new_str = new DateTime($request->str, new DateTimeZone('UTC'));
        $new_str->setTimeZone(new DateTimeZone($request->timeZone));
        return $new_str->format($format);
    }

    public function deleteSchedulerDetails(Request $request)
    {
        if (! empty($request->input('ids'))) {
            try {
                SchedulingInstancesDetails::destroy($request->input('ids'));
                $return['status'] = 'true';
                $return['message'] = 'Delete Successfully';
                return json_encode($return);
            }
            catch(\Exception $e) {
                $return['status'] = 'false';
                $return['message'] = 'Please try again';
                return json_encode($return);
            }
        } else {
            $return['status'] = 'false';
            $return['message'] = 'No Ids Found';
            return json_encode($return);
        }
    }

    public function checkScheduled($id)
    {
        try {
            $scheduleInstanceObj = SchedulingInstance::findByUserInstanceId($id, Auth::id())->first();
            if (! empty($scheduleInstanceObj)) {
                $scheduleInstanceObj = $scheduleInstanceObj->toArray();
                $return['status'] = 'true';
                $return['data'] = $scheduleInstanceObj;
            } else {
                $return['status'] = 'false';
                $return['data'] = $scheduleInstanceObj;
            }
            return json_encode($return);
        } catch (\Exception $e) {
            $return['status'] = 'false';
            return $return;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $userInstanceId = $request->instance_id ?? null;
            $userTimeZone = $request->userTimeZone ?? '';
            $days = $request->day ?? '';

            $requestData = [];

            foreach ($days as $key => $day){

                if (! empty($day)) {

                    $data = [];
                    $data['day'] = $day;
                    $ids = isset($request->ids) ? explode(',',$request->ids[$key]) : '';
                    $scheduled_time = $request->scheduled_time ?? '';
                    $type = $request->type ?? '';

                    if (! empty($scheduled_time)) {

                        $data['schedule_type'] = $type[$key];

                        if(!empty($scheduled_time[$key])){
                            $selected_time = $this->convertTimeToUserTime($day, $scheduled_time[$key], $userTimeZone);
                            $data['selected_time'] = date('h:i A', strtotime($selected_time));
                            $data['time_zone'] = $userTimeZone;
                            $data['cron_data'] = $selected_time.' '.$userTimeZone;
                        } else {
                            $data['selected_time'] = '';
                            $data['time_zone'] = '';
                            $data['cron_data'] = '';
                        }
                        if(!empty($ids) && $ids[0] != "0"){
                            $data['id'] = $ids[0];
                        }

                        array_push($requestData, $data);
                    }
                }
            }

            $schedulingInstance = SchedulingInstance::findByUserInstanceId($userInstanceId, Auth::id())->first();

            if(empty($schedulingInstance)){
                $schedulingInstance = new SchedulingInstance;
            }

            $schedulingInstance->fill([
                'user_id' => Auth::id(),
                'user_instances_id' => $userInstanceId,
            ]);

            if($schedulingInstance->save()){
                foreach ($requestData as $scheduleDetail){

                    if(isset($scheduleDetail['id']) && !empty($scheduleDetail['id'])){
                        $schedulingInstanceDetail = SchedulingInstancesDetails::findById($scheduleDetail['id'])->first();
                    } else {
                        $schedulingInstanceDetail = new SchedulingInstancesDetails;
                    }

                    $schedulingInstanceDetail->fill([
                        'scheduling_instances_id'   => $schedulingInstance->id,
                        'schedule_type'             => $scheduleDetail['schedule_type'],
                        'day'                       => $scheduleDetail['day'],
                        'selected_time'             => $scheduleDetail['selected_time'],
                        'time_zone'                 => $scheduleDetail['time_zone'],
                        'cron_data'                 => $scheduleDetail['cron_data'],
                    ]);

                    $schedulingInstanceDetail->save();
                }
                session()->flash('success', 'Scheduling Create successfully');
                return redirect(route('scheduling.index'));
            } else {
                session()->flash('error', 'Please Try again later');
                return redirect(route('scheduling.index'));
            }
        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return redirect(route('scheduling.index'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try{
            $instances = UserInstance::where(['status' => 'stop','user_id'=> Auth::id()])->get();
            $scheduling = SchedulingInstance::with('userInstances')->find($id);
            return view('user.scheduling.edit', compact('scheduling','instances' ,'id'));
        } catch (Throwable $throwable){
            session()->flash('error', $throwable->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $schedulingInstance = SchedulingInstance::find($id);

            if (! empty($schedulingInstance)) {

                $schedulingInstance->fill([
                    'user_instances_id' => $request->input('user_instances_id'),
                    'start_time'        => $request->input('start_time'),
                    'end_time'          => $request->input('end_time'),
                    'utc_start_time'    => $request->input('utc_start_time'),
                    'utc_end_time'      => $request->input('utc_end_time'),
                    'status'            => $request->input('status'),
                    'current_time_zone' => $request->input('current_time_zone'),
                ]);

                if ($schedulingInstance->save()) {
                    return redirect(route('scheduling.index'))->with('success', 'Scheduling Update Successfully');
                } else {
                    session()->flash('error', 'Bot Can not Updated Successfully');
                    return redirect()->back();
                }
            }

            session()->flash('error', 'Bot not found');
            return redirect()->back();

        } catch (Throwable $throwable){
            session()->flash('error', $throwable->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $schedulingInstance = SchedulingInstance::find($id);
            if ($schedulingInstance->delete()) {
                return redirect(route('scheduling.index'))->with('success', 'Scheduling Delete Successfully');
            }
            session()->flash('error', 'Scheduling Can not Deleted Successfully');
            return redirect()->back();
        } catch (Throwable $throwable){
            session()->flash('error', $throwable->getMessage());
            return redirect()->back();
        }
    }

    public function changeStatus(Request $request)
    {
        try{
            $Scheduling = SchedulingInstance::find($request->id);
            $Scheduling->status = $request->status;
            if ($Scheduling->save()) {
                session()->flash('success', 'Schedule '.$request->status.' successfully!');
                return 'true';
            } else {
                session()->flash('error', 'Schedule '.$request->status.' Not successfully!');
                return 'false';
            }

        } catch (Throwable $throwable){
            session()->flash('error', 'Schedule '.$request->status.' Not successfully!');
            return 'false';
        }
    }
}
