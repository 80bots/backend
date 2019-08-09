<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\ScheduleCollection;
use App\Http\Resources\User\ScheduleResource;
use App\Http\Resources\User\UserInstanceCollection;
use App\SchedulingInstance;
use App\SchedulingInstancesDetails;
use App\UserInstance;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    public function create()
    {
        try {
            // TODO: status stop ?????
            $resource = UserInstance::where(['status' => 'stop','user_id'=> Auth::id()]);
            return new UserInstanceCollection($resource->paginate(self::PAGINATE));
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteSchedulerDetails(Request $request): JsonResponse
    {
        if (! empty($request->input('ids'))) {

            try {

                $count = SchedulingInstancesDetails::whereHas('schedulingInstance', function (Builder $query) {
                    $query->where('user_id', '=', Auth::id());
                })->whereIn('id', $request->input('ids'))->delete();

                if ($count) {
                    return $this->success();
                }

                return $this->error(__('user.error'), __('user.delete_error'));
            } catch(Throwable $throwable) {
                return $this->error(__('user.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));
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

            $userInstanceId = $request->input('instance_id');
            $userTimeZone   = $request->input('timezone');
            $details        = $request->input('details');

            $schedulingInstance = SchedulingInstance::findByUserInstanceId($userInstanceId, Auth::id())->first();

            if (empty($schedulingInstance)) {
                $schedulingInstance = SchedulingInstance::create([
                    'user_id'           => Auth::id(),
                    'user_instances_id' => $userInstanceId,
                ]);
            }

            /**
             * details[0][id]
             * details[0][type]
             * details[0][time]
             * details[0][day]
             */
            if (! empty($details) && is_array($details)) {

                foreach ($details as $detail) {

                    switch ($detail['type']) {
                        case SchedulingInstancesDetails::TYPE_START:
                        case SchedulingInstancesDetails::TYPE_STOP:
                            $type = $detail['type'];
                            break;
                        default:
                            $type = SchedulingInstancesDetails::TYPE_STOP;
                            break;
                    }

                    $selectedTime = $this->convertTimeToUserTime($detail['day'], $detail['time'], $userTimeZone);
                    $data['selected_time'] = date('h:i A', strtotime($selectedTime));

                    $schedulingInstanceDetail = SchedulingInstancesDetails::create([
                        'scheduling_instance_id' => $schedulingInstance->id ?? null,
                        'day' => $detail['day'] ?? '',
                        'selected_time' => $detail['time'] ?? '',
                        'time_zone' => $userTimeZone,
                        'cron_data' => "{$selectedTime} {$userTimeZone}",
                        'schedule_type' => $type,
                        'status' => SchedulingInstancesDetails::STATUS_ACTIVE
                    ]);

                    $schedulingInstanceDetail = $this->getSchedulingInstancesDetails($detail['id'] ?? null);

                    dd($detail, $schedulingInstanceDetail);
                }
            }


//            $requestData = [];
//
//            foreach ($days as $key => $day) {
//
//                if (! empty($day)) {
//
//                    $data = [];
//                    $data['day'] = $day;
//                    $ids = isset($request->ids) ? explode(',',$request->ids[$key]) : '';
//                    $scheduled_time = $request->scheduled_time ?? '';
//                    $type = $request->type ?? '';
//
//                    if (! empty($scheduled_time)) {
//
//                        $data['schedule_type'] = $type[$key];
//
//                        if(!empty($scheduled_time[$key])){
//                            $selected_time = $this->convertTimeToUserTime($day, $scheduled_time[$key], $userTimeZone);
//                            $data['selected_time'] = date('h:i A', strtotime($selected_time));
//                            $data['time_zone'] = $userTimeZone;
//                            $data['cron_data'] = $selected_time.' '.$userTimeZone;
//                        } else {
//                            $data['selected_time'] = '';
//                            $data['time_zone'] = '';
//                            $data['cron_data'] = '';
//                        }
//                        if(!empty($ids) && $ids[0] != "0"){
//                            $data['id'] = $ids[0];
//                        }
//
//                        array_push($requestData, $data);
//                    }
//                }
//            }
//
//
//
//            if (! empty($schedulingInstance)) {
//
//                foreach ($requestData as $scheduleDetail){
//
//                    if (! empty($scheduleDetail['id'])) {
//                        $schedulingInstanceDetail = SchedulingInstancesDetails::findById($scheduleDetail['id'])->first();
//                    } else {
//                        $schedulingInstanceDetail = new SchedulingInstancesDetails;
//                    }
//
//                    $schedulingInstanceDetail->fill([
//                        'scheduling_instances_id'   => $schedulingInstance->id,
//                        'schedule_type'             => $scheduleDetail['schedule_type'],
//                        'day'                       => $scheduleDetail['day'],
//                        'selected_time'             => $scheduleDetail['selected_time'],
//                        'time_zone'                 => $scheduleDetail['time_zone'],
//                        'cron_data'                 => $scheduleDetail['cron_data'],
//                    ]);
//
//                    $schedulingInstanceDetail->save();
//                }
//
//                return $this->success();
//            }

            return $this->error(__('user.error'), __('user.scheduling.error_create'));

        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
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
        if (! empty($id)) {
            try {

                $scheduleInstance = SchedulingInstance::findByUserInstanceId($id, Auth::id())->first();

                if (! empty($scheduleInstance)) {
                    return $this->success($scheduleInstance);
                }

                return $this->notFound(__('user.not_found'), __('user.not_found'));

            } catch (Throwable $throwable) {
                return $this->error(__('user.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! empty($id)) {

            try {

                // TODO: ????
                $instances = UserInstance::where(['status' => 'stop', 'user_id' => Auth::id()])->get();

                $resource = new ScheduleResource(SchedulingInstance::with('userInstance')->find($id));

                return $this->success([
                    'instances'     => $instances,
                    'scheduling'    => $resource->response()->getData(),
                ]);

            } catch (Throwable $throwable) {
                return $this->error(__('user.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));
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

            $instance = SchedulingInstance::find($id);

//            if (empty($instance)) {
//                return $this->notFound(__('user.not_found'), __('user.scheduling.not_found'));
//            }
//
//            $instance->fill([
//                'start_time'        => $request->input('start_time'),
//                'end_time'          => $request->input('end_time'),
//                'utc_start_time'    => $request->input('utc_start_time'),
//                'utc_end_time'      => $request->input('utc_end_time'),
//                'status'            => $request->input('status'),
//                'current_time_zone' => $request->input('current_time_zone'),
//            ]);
//
//            if ($instance->save()) {
//                return $this->success();
//            }

            return $this->error(__('user.error'), __('user.scheduling.not_update'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try{
            $instance = SchedulingInstance::find($id);

            if (empty($instance)) {
                return $this->notFound(__('user.not_found'), __('user.scheduling.not_found'));
            }

            if ($instance->delete()) {
                return $this->success();
            }

            return $this->error(__('user.error'), __('user.scheduling.not_deleted'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeSchedulingStatus(Request $request): JsonResponse
    {
        try{

            switch ($request->input('status')) {
                case SchedulingInstance::STATUS_ACTIVE:
                case SchedulingInstance::STATUS_INACTIVE:
                    $status = $request->input('status');
                    break;
                default:
                    $status = SchedulingInstance::STATUS_INACTIVE;
                    break;
            }

            $update = SchedulingInstance::where('id', $request->input('id'))
                ->update(['status' => $status]);

            if ($update) {
                return $this->success();
            }

            return $this->error(__('user.error'), __('user.scheduling.status_error'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param null $id
     * @return SchedulingInstancesDetails
     */
    private function getSchedulingInstancesDetails($id = null): ?SchedulingInstancesDetails
    {
        if (empty($id)) {
            return new SchedulingInstancesDetails;
        } else {
            return SchedulingInstancesDetails::whereHas('schedulingInstance', function($query) {
                $query->whereHas('user', function (Builder $query) {
                    $query->where('id', '=', Auth::id());
                });
            })->where('id', '=', $id)->first();
        }
    }

}
