<?php

namespace App\Http\Resources;

use App\AwsAmi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (! empty($this->default_image_id)) {
            $ami = AwsAmi::where('image_id', '=', $this->default_image_id)->first();
            $default = ! empty($ami) ? "{$ami->name} | {$ami->image_id}" : "";
        } else {
            $default = $this->default_image_id ?? '';
        }

        $array = [
            'id'                => $this->id ?? '',
            'name'              => $this->name ?? '',
            'code'              => $this->code ?? '',
            'limit'             => $this->limit ?? 0,
            'created_instances' => $this->created_instances ?? 0,
            'amis'              => $this->amis ?? [],
            'show_default_ami'  => $default,
            'default_ami'       => $this->default_image_id,
        ];

        return $array;
    }
}
