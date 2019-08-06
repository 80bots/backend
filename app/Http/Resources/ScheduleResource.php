<?php

namespace App\Http\Resources;

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
        $details = $this->details->map(function ($object) {
            return [
                'id'            => $object->id ?? null,
                'day'           => $object->day ?? '',
                'selected_time' => $object->selected_time ?? '',
                'cron_data'     => $object->cron_data ?? '',
                'schedule_type' => $object->schedule_type ?? '',
                'status'        => $object->status ?? '',
                'created_at'    => $object->created_at ?? '',
            ];
        })->toArray();

        return [
            'id'        => $this->id ?? '',
            'name'      => $this->name ?? '',
            'details'   => $details,
            'status'    => $this->status ?? null,
        ];
    }
}
