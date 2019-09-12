<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
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
            'id'                => $this->id ?? '',
            'name'              => $this->name ?? '',
            'code'              => $this->code ?? '',
            'limit'             => $this->limit ?? 0,
            'created_instances' => $this->created_instances ?? 0,
        ];

        return $array;
    }
}
