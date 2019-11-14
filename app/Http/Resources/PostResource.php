<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'id'            => $this->id ?? '',
            'title'         => $this->title ?? '',
            'slug'          => $this->slug ?? '',
            'type'          => $this->type ?? '',
            'content'       => $this->content ?? '',
            'style'         => $this->style ?? '',
            'javascript'    => $this->javascript ?? '',
            'status'        => $this->status ?? ''
        ];
    }
}
