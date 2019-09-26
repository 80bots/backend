<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\QueryHelper;
use App\Http\Resources\User\ScheduleCollection;
use App\Http\Resources\User\ScheduleResource;
use App\Http\Resources\User\BotInstanceCollection;
use App\SchedulingInstance;
use App\SchedulingInstancesDetails;
use App\BotInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ScheduleController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = SchedulingInstance::findByUserId(Auth::id());

            // TODO: Add Filters

            if (! empty($search)) {
                $resource->whereHas('instance', function (Builder $query) use ($search) {
                    $query->where('tag_name', 'like', "%{$search}%");
                });
            }

            //
            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(SchedulingInstance::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBotScheduling($query, SchedulingInstance::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('created_at', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('created_at', 'desc');
            });

            $schedules  = (new ScheduleCollection($resource->paginate($limit)))->response()->getData();
            $meta       = $schedules->meta ?? null;

            $response = [
                'data'  => $schedules->data ?? [],
                'total' => $meta->total ?? 0
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
            $resource = BotInstance::where(['status' => 'stop','user_id'=> Auth::id()]);
            return new BotInstanceCollection($resource->paginate(self::PAGINATE));
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
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {

            $data = $request->validate([
               'instance_id' => 'required|string'
            ]);

            $instance = BotInstance::findByInstanceId($data['instance_id'])->first();

            if(empty($instance)) return $this->error(__('user.server_error'), 'Such bot does not exists');

            $schedule = SchedulingInstance::findByUserInstanceId($instance->id, Auth::id())
                ->first();

            if (empty($schedule)) {
                $schedule = SchedulingInstance::create([
                    'user_id'           => Auth::id(),
                    'user_instance_id'  => $instance->id,
                ]);

                if($schedule) return $this->success();
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
                $instances = BotInstance::where(['status' => 'stop', 'user_id' => Auth::id()])->get();

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

            $active     = SchedulingInstance::STATUS_ACTIVE;
            $inactive   = SchedulingInstance::STATUS_INACTIVE;

            if (! empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status'     => "in:{$active},{$inactive}",
                    'update.details'    => 'array',
                ]);
                return $this->updateSimpleInfo($request, $updateData, $instance);
            } else {
                return $this->updateFullInfo($request, $instance);
            }

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    private function updateSimpleInfo(Request $request, array $updateData, SchedulingInstance $instance)
    {
        foreach ($updateData['update'] as $key => $value) {
            switch ($key) {
                case 'status':
                    $instance->fill(['status' => $value]);
                    if ($instance->save()) {
                        return $this->success((new ScheduleResource($instance))->toArray($request));
                    } else {
                        return $this->error(__('user.server_error'), __('user.scheduling.not_updated'));
                    }
                case 'details':
                    $this->updateOrCreateSchedulingInstancesDetails($instance, $value,
                        $request->user()->timezone->value ?? '+00:00');
                    return $this->success((new ScheduleResource($instance))->toArray($request));
                default:
                    return $this->error(__('user.server_error'), __('user.scheduling.not_updated'));
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));
    }

    private function updateFullInfo(Request $request, SchedulingInstance $instance)
    {
        return $this->error(__('user.error'), __('user.parameters_incorrect'));
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
     * @param SchedulingInstance $instance
     * @param array $details
     * @param string $timezone
     * @return void
     */
    private function updateOrCreateSchedulingInstancesDetails(SchedulingInstance $instance, array $details, $timezone): void
    {
        // Delete all
        SchedulingInstancesDetails::whereHas('schedulingInstance', function(Builder $query) {
            $query->where('user_id', '=', Auth::id());
        })->where('scheduling_instance_id', '=', $instance->id ?? null)->delete();

        /**
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

            $selectedTime = Carbon::parse("{$detail['day']} {$detail['time']}");

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
