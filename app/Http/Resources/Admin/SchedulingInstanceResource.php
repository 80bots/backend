<?php

namespace App\Http\Resources\Admin;

use App\Helpers\InstanceHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class SchedulingInstanceResource extends JsonResource
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
            'user'      => $this->user->name ?? '',
            'status'    => $this->status ?? '',
            'details'   => InstanceHelper::getSchedulingDetails($this->details ?? null),
        ];
    }
}
