<?php

namespace App\Http\Controllers\admin;

use App\SchedulingInstance;
use App\SchedulingInstancesDetails;
use App\UserInstances;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SchedulingInstancesController
{
    public function index()
    {
        try {
            $results = SchedulingInstance::findByUserId(Auth::id())->get();
            return view('admin.scheduling.index',compact('results'));
        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return redirect(route('admin.scheduling.index'));
        }
    }

    public function create()
    {
        try {
            $instances = UserInstances::where(['status' => 'stop','user_id'=> Auth::id()])->get();
            return view('admin.scheduling.create',compact('instances'));
        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return redirect(route('admin.scheduling.index'));
        }
    }

    public function convertTimeToUserTime($day, $str, $userTimezone, $format = 'D h:i A')
    {
        $new_str = new DateTime("{$day} {$str}", new DateTimeZone($userTimezone));
        return $new_str->format($format);
    }

    public function convertTimeToUSERzone($str, $userTimezone, $format = 'h:i A')
    {
        if(is_null($str) || empty($str) || $str === "null"){
            return '';
        }
        $new_str = new DateTime($str, new DateTimeZone('UTC'));
        $new_str->setTimeZone(new DateTimeZone($userTimezone));
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
            } catch(Throwable $throwable) {
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

    public function CheckScheduled($id)
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
        } catch (Throwable $throwable) {
            $return['status'] = 'false';
            return $return;
        }
    }

    public function store(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $userInstanceId = isset($request->instance_id) ? $request->instance_id : '';
            $userTimeZone = isset($request->userTimeZone) ? $request->userTimeZone : '';
            $days = isset($request->day) ? $request->day : '';
            $requestData = [];
            foreach ($days as $key => $day){
                if(!empty($day)){
                    $data = [];
                    $data['day'] = $day;
                    $ids = isset($request->ids) ? explode(',',$request->ids[$key]) : '';
                    $scheduled_time = isset($request->scheduled_time) ? $request->scheduled_time : '';
                    $type = isset($request->type) ? $request->type : '';
                    $endTime = isset($request->end_time) ? $request->end_time : '';
                    if(!empty($scheduled_time)) {
                        $data['schedule_type'] = $type[$key];
                        if(!empty($scheduled_time[$key])){
                            $selected_time = $this->convertTimeToUserTime($day, $scheduled_time[$key], $userTimeZone);
                            $data['selected_time'] = date('h:i A', strtotime($selected_time));
                            $data['cron_data'] = $selected_time.' '.$userTimeZone;
                        } else {
                            $data['selected_time'] = '';
                            $data['cron_data'] = '';
                        }
                        if(!empty($ids) && $ids[0] != "0"){
                            $data['id'] = $ids[0];
                        }
                        array_push($requestData, $data);
                    }
                }
            }

            $schedulingInstance = SchedulingInstance::findByUserInstanceId($userInstanceId, $user_id)->first();
            if(empty($schedulingInstance)){
                $schedulingInstance = new SchedulingInstance();
            }
            $schedulingInstance->user_id = $user_id;
            $schedulingInstance->user_instances_id = $userInstanceId;
            if($schedulingInstance->save()){
                foreach ($requestData as $scheduleDetail){
                    if(isset($scheduleDetail['id']) && !empty($scheduleDetail['id'])){
                        $schedulingInstanceDetail = SchedulingInstancesDetails::findById($scheduleDetail['id'])->first();
                    } else {
                        $schedulingInstanceDetail = new SchedulingInstancesDetails();
                    }
                    $schedulingInstanceDetail->scheduling_instances_id = $schedulingInstance->id;
                    $schedulingInstanceDetail->schedule_type = $scheduleDetail['schedule_type'];
                    $schedulingInstanceDetail->day = $scheduleDetail['day'];
                    $schedulingInstanceDetail->selected_time = $scheduleDetail['selected_time'];
                    $schedulingInstanceDetail->cron_data = $scheduleDetail['cron_data'];
                    $schedulingInstanceDetail->save();
                }
                session()->flash('success', 'Scheduling Create successfully');


                return redirect(route('admin.my-bots'));
            } else {
                session()->flash('error', 'Please Try again later');
                return redirect(route('admin.my-bots'));
            }

        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return redirect(route('admin.my-bots'));
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
            $instances = UserInstances::where(['status' => 'stop','user_id'=> Auth::id()])->get();
            $scheduling = SchedulingInstance::with('userInstances')->find($id);
            return view('admin.scheduling.edit',compact('scheduling','instances' ,'id'));
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
                $schedulingInstance->user_instances_id = $request->user_instances_id;
                $schedulingInstance->start_time = $request->start_time;
                $schedulingInstance->end_time = $request->end_time;
                $schedulingInstance->utc_start_time = $request->utc_start_time ;
                $schedulingInstance->utc_end_time = $request->utc_end_time;
                $schedulingInstance->status = $request->status;
                $schedulingInstance->current_time_zone =  $request->current_time_zone;

                if ($schedulingInstance->save()) {
                    return redirect(route('admin.scheduling.index'))->with('success', 'Scheduling Update Successfully');
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
            if($schedulingInstance->delete()){
                return redirect(route('admin.scheduling.index'))->with('success', 'Scheduling Delete Successfully');
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
            if($Scheduling->save()){
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
