<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class InstanceSessionsHistoryResource extends JsonResource
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
            'id'           => $this->id ?? '',
            'instance_id'  => $this->schedulingInstance->userInstance->aws_instance_id ?? '',
            'user'         => $this->schedulingInstance->user->email ?? '',
            'type'         => $this->schedule_type ?? '',
            'time'         => $this->selected_time ?? '',
            'cron'         => $this->cron_data ?? '',
            'status'       => $this->status ?? ''
        ];
    }
}
