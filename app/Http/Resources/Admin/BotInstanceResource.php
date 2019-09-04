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
        $details    = $this->details()->latest()->first();
        $region     = $this->region ?? null;

        return [
            'id'                => $this->id ?? '',
            'region'            => $region->name ?? '',
            'name'              => $details->tag_name ?? '',
            'bot_name'          => $this->bot->name ?? '',
            'parameters'        => $this->bot->parameters ?? '',
            'launched_by'       => $details->tag_user_email ?? '',
            'launched_at'       => $details->start_time ?? '',
            'instance_id'       => $details->aws_instance_id ?? '',
            'tag_user_email'    => $details->tag_user_email ?? '',
            'credits_used'      => $this->credits_used ?? 0,
            'uptime'            => $this->uptime ?? 0,
            'total_uptime'      => $this->total_uptime ?? 0,
            'cron_uptime'       => $this->cron_uptime ?? 0,
            'status'            => $this->aws_status ?? BotInstance::STATUS_TERMINATED,
            'ip'                => $details->aws_public_ip ?? '',
            'pem'               => $details->aws_pem_file_path ?? ''
        ];
    }
}
