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
        $content = '';

        if (! empty($this->content)) {
            $content = mb_strimwidth(strip_tags($this->content), 0, 40, "...");
        }

        return [
            'id'        => $this->id ?? '',
            'title'     => $this->title ?? '',
            'slug'      => $this->slug ?? '',
            'type'      => $this->type ?? '',
            'bot'       => $this->bot ?? null,
            'content'   => $content,
            'status'    => $this->status ?? '',
            'messages'  => $this->messages ?? [],
        ];
    }
}
