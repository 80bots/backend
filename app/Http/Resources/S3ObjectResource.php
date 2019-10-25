<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class S3ObjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $name = $this->name;

        if (empty($this->parent_id)) {

            $now = Carbon::now();
            $nowDate = $now->toDateString();
            $yesterdayDate = $now->subDay()->toDateString();

            if ($this->name === $nowDate) {
                $name = 'Today';
            } elseif ($this->name === $yesterdayDate) {
                $name = 'Yesterday';
            } else {
                $name = $this->name;
            }
        }

        return [
            'id'    => $this->id ?? '',
            'name'  => $name,
            'thumbnail' => $this->link ?? '',
            'type'  => $this->entity
        ];
    }
}
