<?php

namespace App\Http\Resources\User;

use App\BotInstance;
use Illuminate\Http\Resources\Json\JsonResource;

class BotInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $details = $this->details()
            ->orderBy('created_at', 'desc')
            ->first();

        $region = $this->region ?? null;

        return [
            'id'                => $this->id ?? '',
            'region'            => $region->name ?? '',
            'instance_id'       => $details->aws_instance_id ?? '',
            'name'              => $details->tag_name ?? '',
            'parameters'        => $this->bot->parameters ?? '',
            'launched_at'       => $details->start_time ?? '',
            'tag_user_email'    => $this->tag_user_email ?? '',
            'credits_used'      => $this->used_credit ?? 0,
            'up_time'           => $this->up_time ?? 0,
            'total_up_time'     => $this->total_up_time ?? 0,
            'cron_up_time'      => $this->cron_up_time ?? 0,
            'status'            => $this->aws_status ?? BotInstance::STATUS_TERMINATED,
            'ip'                => $details->aws_public_ip ?? '',
            'is_in_queue'       => $this->is_in_queue ?? 0,
        ];
    }
}
