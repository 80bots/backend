<?php

namespace App\Http\Resources\Admin;

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
        $content = '';

        if (! empty($this->content)) {
            $content = mb_strimwidth($this->content, 0, 40, "...");
        }

        return [
            'id'        => $this->id ?? '',
            'title'     => $this->title ?? '',
            'slug'      => $this->slug ?? '',
            'content'   => $content,
            'status'    => $this->status ?? '',
        ];
    }
}
