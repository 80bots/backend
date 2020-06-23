<?php

namespace App\Http\Controllers\Admin;

use App\AwsSetting;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\AwsSettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AwsSettingController extends AppController
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $settings = (new AwsSettingResource(AwsSetting::isDefault()->first()))->response()->getData();
        return $this->success([ 'settings' => $settings->data ?? null ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $settings = AwsSetting::find($id);

        if (! empty($settings)) {
            $updateData = $request->validate([
                'update.type'               => 'string|required',
                'update.storage'            => 'integer|required',
                'update.script'             => 'string|required',
            ]);
            $settings->update($updateData['update']);

            return $this->success((new AwsSettingResource($settings))->toArray($request));
        }

        return $this->notFound(__('admin.not_found'), __('admin.not_found'));
    }
}
