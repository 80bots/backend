<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Http\Resources\User\ScheduleCollection;
use App\Http\Resources\User\ScheduleResource;
use App\Http\Resources\User\UserInstanceCollection;
use App\SchedulingInstance;
use App\SchedulingInstancesDetails;
use App\UserInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SchedulesController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {

            $resource = SchedulingInstance::findByUserId(Auth::id());

            // TODO: Add Filters

            $schedules = (new ScheduleCollection($resource->paginate(self::PAGINATE)))->response()->getData();

            $response = [
                'schedules' => $schedules->data ?? [],
                'paginate'  => CommonHelper::getPaginateInfo($schedules->meta ?? null)
            ];

            return $this->success($response);

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

            $instance = SchedulingInstance::findByUserInstanceId($userInstanceId, Auth::id())->first();

            if (empty($instance)) {
                $instance = SchedulingInstance::create([
                    'user_id'           => Auth::id(),
                    'user_instances_id' => $userInstanceId,
                ]);
            }

            if (! empty($details) && is_array($details)) {
                $this->updateOrCreateSchedulingInstancesDetails($instance, $details, $userTimeZone);
                return $this->success();
            }

            return $this->error(__('user.error'), __('user.parameters_incorrect'));

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

                $instance = SchedulingInstance::with('userInstance')->where('user_id', '=', Auth::id())
                    ->where('id', '=', $id)->first();

                if (! empty($instance)) {
                    $resource = new ScheduleResource($instance);

                    return $this->success([
                        'instance' => $resource->response()->getData(),
                    ]);
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

                $instance = SchedulingInstance::with('userInstance')->where('user_id', '=', Auth::id())
                    ->where('id', '=', $id)->first();

                if (! empty($instance)) {
                    $resource = new ScheduleResource($instance);
                    return $this->success([
                        'instances' => $instances,
                        'scheduling' => $resource->response()->getData(),
                    ]);
                }

                return $this->notFound(__('user.not_found'), __('user.not_found'));

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

            $instance = SchedulingInstance::where('user_id', '=', Auth::id())
                ->where('id', '=', $id)->first();

            if (empty($instance)) {
                return $this->notFound(__('user.not_found'), __('user.scheduling.not_found'));
            }

            $userTimeZone   = $request->input('timezone');
            $details        = $request->input('details');

            if (! empty($details) && is_array($details)) {
                $this->updateOrCreateSchedulingInstancesDetails($instance, $details, $userTimeZone);
                return $this->success();
            }

            return $this->error(__('user.error'), __('user.parameters_incorrect'));

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
            $instance = SchedulingInstance::where('user_id', '=', Auth::id())
                ->where('id', '=', $id)->first();

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

            $update = SchedulingInstance::whereHas('userInstance', function(Builder $query) {
                $query->where('user_id', '=', Auth::id());
            })->where('id', '=', $request->input('id'))->update(['status' => $status]);

            if ($update) {
                return $this->success();
            }

            return $this->error(__('user.error'), __('user.scheduling.status_error'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param SchedulingInstance $instance
     * @param array $details
     * @param string $timezone
     * @return void
     */
    private function updateOrCreateSchedulingInstancesDetails(SchedulingInstance $instance, array $details, string $timezone): void
    {
        /**
         * details[0][id] = 1 | empty
         * details[0][type] = stop | start
         * details[0][time] = 6:00 PM
         * details[0][day] = Friday
         */
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

            $selectedTime = Carbon::parse("{$detail['day']} {$detail['time']}")
                ->setTimezone($timezone);

            $schedulingDetails = SchedulingInstancesDetails::whereHas('schedulingInstance', function(Builder $query) {
                $query->where('user_id', '=', Auth::id());
            })->where('id', '=', $detail['id'] ?? null)->first();

            if (! empty($schedulingDetails)) {
                $schedulingDetails->fill([
                    'scheduling_instance_id'    => $instance->id ?? null,
                    'day'                       => $detail['day'] ?? '',
                    'selected_time'             => $selectedTime->format('h:i A'),
                    'time_zone'                 => $timezone,
                    'cron_data'                 => "{$selectedTime->format('D h:i A')} {$timezone}",
                    'schedule_type'             => $type,
                    'status'                    => SchedulingInstancesDetails::STATUS_ACTIVE
                ]);
                $schedulingDetails->save();
            } else {
                SchedulingInstancesDetails::create([
                    'scheduling_instance_id'    => $instance->id ?? null,
                    'day'                       => $detail['day'] ?? '',
                    'selected_time'             => $selectedTime->format('h:i A'),
                    'time_zone'                 => $timezone,
                    'cron_data'                 => "{$selectedTime->format('D h:i A')} {$timezone}",
                    'schedule_type'             => $type,
                    'status'                    => SchedulingInstancesDetails::STATUS_ACTIVE
                ]);
            }
        }
    }

}
