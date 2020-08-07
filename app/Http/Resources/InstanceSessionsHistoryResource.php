<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceSessionsHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'           => $this->id ?? '',
            'instance_id'  => $this->schedulingInstance->instance->aws_instance_id ?? '',
            'user'         => $this->user->email ?? '',
            'status'       => $this->status ?? '',
            'type'         => $this->schedule_type ?? '',
            'date'         => $this->cron_data ?? '',
            'time_zone'    => $this->current_time_zone ?? '',
        ];
    }
}
