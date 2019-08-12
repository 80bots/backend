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
        $instance = $this->userInstance ?? null;

        if (! empty($instance)) {
            $instance = collect($instance->toArray())
                ->only([
                    'id', 'tag_name', 'aws_instance_id'
                ])
                ->toArray();
        }

        return [
            'id'            => $this->id ?? '',
            'bot_name'      => $instance['tag_name'] ?? '',
            'instance_id'   => $instance['aws_instance_id'] ?? '',
            'status'        => $this->status ?? null,
            'details'       => InstanceHelper::getSchedulingDetails($this->details ?? null),
        ];
    }
}
