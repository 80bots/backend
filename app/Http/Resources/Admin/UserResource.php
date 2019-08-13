<?php

namespace App\Http\Resources\Admin;

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
        $array = [
            'id'    => $this->id ?? '',
            'name'  => $this->name ?? '',
            'email' => $this->email ?? '',
            'role'  => $this->role->name ?? '',
        ];

        if ( $request->user()->role->name === 'Admin' ) {
            $array['remaining_credits'] = $this->remaining_credits;
            $array['created_at'] = $this->created_at;
            $array['status'] = $this->status;
        }

        return $array;
    }
}
