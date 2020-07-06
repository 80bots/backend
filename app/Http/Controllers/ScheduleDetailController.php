<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\SchedulingInstancesDetails;
use App\SchedulingInstance;
use Throwable;

class ScheduleDetailController extends AppController
{

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function store(Request $request, $id)
    {
        try {

            switch ($request['type']) {
                case SchedulingInstancesDetails::TYPE_START:
                case SchedulingInstancesDetails::TYPE_STOP:
                    $type = $request['type'];
                    break;
                default:
                    $type = SchedulingInstancesDetails::TYPE_STOP;
            }

            $detail = SchedulingInstance::findOrFail($id)->details->create([
                'schedule_type' => $type,
            ]);

            if (empty($detail)) {
                return $this->error(__('scheduling.detail.server_error'), __('scheduling.detail.detail.error_create'));
            }

            return $this->success(__('scheduling.detail.success_create'));

        } catch(Throwable $throwable) {
            return $this->error(__('scheduling.server_error'), $throwable->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!empty($request->input('ids'))) {

            try {

                $count = SchedulingInstancesDetails::whereIn('id', $request->input('ids'))->delete();

                if ($count) {
                    return $this->success();
                }

                return $this->error(__('user.error'), __('user.delete_error'));
            } catch(Throwable $throwable) {
                return $this->error(__('user.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));    }
}
