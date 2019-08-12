<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name'              => $this->name ?? '',
            'email'             => $this->email ?? '',
            'role'              => $this->role->name ?? '',
            'remaining_credits' => $this->remaining_credits ?? 0
        ];
    }
}
