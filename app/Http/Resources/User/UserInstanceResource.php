<?php

namespace App\Http\Resources\User;

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
        return [
            'id'                => $this->id ?? '',
            'tag_name'          => $this->tag_name ?? '',
            'tag_user_email'    => $this->tag_user_email ?? '',
            'used_credit'       => $this->used_credit ?? 0,
            'up_time'           => $this->up_time ?? 0,
            'temp_up_time'      => $this->temp_up_time ?? 0,
            'cron_up_time'      => $this->cron_up_time ?? 0,
            'status'            => $this->status ?? 0,
            'aws_public_ip'     => $this->aws_public_ip ?? '',
            'aws_public_dns'    => $this->aws_public_dns ?? '',
            'is_in_queue'       => $this->is_in_queue ?? 0,
        ];
    }
}
