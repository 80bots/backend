<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use function Zend\Diactoros\normalizeUploadedFiles;

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
            'bot'       => $this->bot->name ?? null,
            'content'   => $content,
            'status'    => $this->status ?? '',
        ];
    }
}
