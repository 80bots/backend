<?php

namespace App\Http\Resources\User;

use App\Helpers\InstanceHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'name'      => $this->name ?? '',
            'details'   => InstanceHelper::getSchedulingDetails($this->details ?? null),
            'status'    => $this->status ?? null,
        ];
    }
}
