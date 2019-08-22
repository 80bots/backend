<?php

namespace App\Http\Resources\User;

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
        $tags = $this->tags->isNotEmpty() ? $this->tags : collect([]);

        $tags = $tags->map(function ($item, $key) {
            return [
                'id'    => $item['id'] ?? null,
                'name'  => $item['name'] ?? '',
            ];
        })->toArray();

        return [
            'id'                => $this->id ?? '',
            'name'              => $this->name ?? '',
            'platform'          => $this->platform->name ?? '',
            'description'       => $this->description ?? '',
            'parameters'        => $this->parameters ? (array) json_decode($this->parameters) : array(),
            'aws_ami_image_id'  => $this->aws_ami_image_id ?? '',
            'aws_ami_name'      => $this->aws_ami_name ?? '',
            'aws_instance_type' => $this->aws_instance_type ?? '',
            'aws_storage_gb'    => $this->aws_storage_gb ?? '',
            'aws_startup_script'=> $this->aws_startup_script ?? '',
            'aws_custom_script' => $this->aws_custom_script ?? '',
            'status'            => $this->status ?? '',
            'type'              => $this->type ?? '',
            'tags'              => $tags
        ];
    }
}
