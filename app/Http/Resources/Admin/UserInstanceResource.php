<?php

namespace App\Http\Resources\Admin;

use App\UserInstance;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $details = $this->userInstanceDetail()
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'id'                => $this->id ?? '',
            'name'              => $this->tag_name ?? '',
            'launched_by'       => $details->start_time ?? '',
            'instance_id'       => $this->aws_instance_id ?? '',
            'tag_user_email'    => $this->tag_user_email ?? '',
            'used_credit'       => $this->used_credit ?? 0,
            'uptime'            => $this->up_time ?? 0,
            'temp_up_time'      => $this->temp_up_time ?? 0,
            'cron_up_time'      => $this->cron_up_time ?? 0,
            'status'            => $this->status ?? UserInstance::STATUS_TERMINATED,
            'ip'                => $this->aws_public_ip ?? '',
            'pem'               => $this->aws_pem_file_path ?? ''
        ];
    }
}
