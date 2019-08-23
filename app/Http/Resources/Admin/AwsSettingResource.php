<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AwsSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id ?? '',
            'image_id'  => $this->image_id ?? '',
            'type'      => $this->type ?? '',
            'storage'   => $this->storage ?? '',
            'default'   => $this->default ?? '',
        ];
    }
}
