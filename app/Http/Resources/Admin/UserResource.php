<?php

namespace App\Http\Resources\Admin;

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
        $array = [
            'id'    => $this->id ?? '',
            'name'  => $this->name ?? '',
            'email' => $this->email ?? '',
            'role'  => $this->role->name ?? '',
        ];

        if ($request->user()->isAdmin()) {
            $array['credits']       = $this->credits ?? 0;
            $array['created_at']    = $this->created_at ?? '';
            $array['status']        = $this->status ?? '';
        }

        return $array;
    }
}
