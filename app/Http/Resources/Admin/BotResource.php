<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class BotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $params = json_decode($this->parameters ?? '');
        $formattedParameters = [];

        if (! empty($params)) {
            foreach ($params as $key => $param) {
                $formattedParameters[] = array_merge([
                    'name' => $key
                ], (array) $param);
            }
        }

        return [
            'id'                => $this->id ?? '',
            'platform'          => $this->platform->name ?? '',
            'name'              => $this->name ?? '',
            'description'       => $this->description ?? '',
            'parameters'        => $formattedParameters,
            'ami_id'            => $this->aws_image_id ?? '',
            'aws_startup_script'=> $this->aws_startup_script ?? '',
            'aws_custom_script' => $this->aws_custom_script ?? '',
            'status'            => $this->status ?? '',
            'type'              => $this->type ?? '',
            'tags'              => array_map(function($item) { return $item->name; }, $this->tags->all()),
            'users'             => $this->users->all()
        ];
    }
}
