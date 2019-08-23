<?php

namespace App\Http\Controllers\Admin;

use App\AwsSetting;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\AwsSettingResource;
use Illuminate\Http\Request;

class AwsSettingController extends AppController
{
    public function index(Request $request)
    {
        $settings = (new AwsSettingResource(AwsSetting::isDefault()->first()))->response()->getData();
        return $this->success([ 'settings' => $settings->data ?? null ]);
    }

    public function update(Request $request, $id)
    {
        $settings = AwsSetting::find($id);

        if (! empty($settings)) {
            $update = $request->only(['image_id', 'type', 'storage', 'default']);
            $settings->update($update);

            return $this->success((new AwsSettingResource($settings))->toArray($request));
        }

        return $this->notFound(__('admin.not_found'), __('admin.not_found'));
    }
}
