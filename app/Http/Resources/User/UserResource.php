<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'id'            => $this->id ?? '',
            'name'          => $this->name ?? '',
            'email'         => $this->email ?? '',
            'role'          => $this->role->name ?? '',
            'credits'       => $this->credits ?? 0,
            'timezone'      => $this->timezone->timezone ?? '',
            'region'        => $this->region->name ?? '',
            'subscription'  => $this->subscription(config('settings.stripe.product')) ?? []
        ];
    }
}
