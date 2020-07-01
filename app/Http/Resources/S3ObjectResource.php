<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class S3ObjectResource extends JsonResource
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
            'id'    => $this->id ?? '',
            'name'  => $this->name,
            'url'   => $this->link ?? '',
            'type'  => $this->entity,
            'path'  => $this->path
        ];
    }
}
