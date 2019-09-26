<?php

namespace App\Http\Resources\Admin;

use App\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
      //$details    = $this->details()->latest()->first();
        $region     = $this->region ?? null;
        $details    = $this->oneDetail ?? null;

        return [
            'id'                => $this->id ?? '',
            'region'            => $region->name ?? '',
            'name'              => $this->tag_name ?? '',
            'bot_name'          => $this->bot->name ?? '',
            'parameters'        => $this->bot->parameters ?? '',
            'launched_by'       => $this->tag_user_email ?? '',
            'launched_at'       => $details->start_time ?? '',
            'instance_id'       => $this->aws_instance_id ?? '',
            'used_credit'       => $this->used_credit ?? 0,
            'uptime'            => $this->total_up_time ?? 0,
            'status'            => $this->aws_status ?? BotInstance::STATUS_TERMINATED,
            'ip'                => $this->aws_public_ip ?? '',
            'pem'               => $details->aws_pem_file_path ?? ''
        ];
    }
}
