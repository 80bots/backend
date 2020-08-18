<?php

namespace App\Http\Resources;

use App\Helpers\S3BucketHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->tags->isNotEmpty()) {
            $tags = $this->tags->map(function ($item, $key) {
                return [
                    'id'    => $item['id'] ?? null,
                    'name'  => $item['name'] ?? '',
                ];
            })->toArray();
        } else {
            $tags = collect([]);
        }

        $params = json_decode($this->parameters ?? '');
        $formattedParameters = [];

        if (! empty($params)) {
            foreach ($params as $key => $param) {
                $formattedParameters[] = array_merge([
                    'name' => $key
                ], (array) $param);
            }
            usort($formattedParameters, function($a, $b) {
                return ($a['order'] > $b['order']) ? 1 : -1;
            });
        }

        if(! empty($this->s3_path)) {
            $scripts = S3BucketHelper::getFilesS3($this->s3_path);
        }

        return [
            'id'                        => $this->id ?? '',
            'name'                      => $this->name ?? '',
            'description'               => $this->description ?? '',
            'parameters'                => $formattedParameters ?? [],
            'platform'                  => $this->platform->name ?? '',
            'status'                    => $this->status ?? '',
            'type'                      => $this->type ?? '',
            'aws_custom_script'         => $scripts['custom_script'] ?? '',
            'aws_custom_package_json'   => $scripts['custom_package_json'] ?? '',
            'tags'                      => $tags
        ];
    }
}
