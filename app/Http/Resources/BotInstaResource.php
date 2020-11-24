<?php

namespace App\Http\Resources;

use App\Helpers\S3BucketHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BotInstaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
       

        $params = json_decode($this->parameters ?? '');
        //Log::debug("BotInstaResource&&&&&&&&&&&&&&& {$params}"); 
        $formattedParameters = [];
        $tags = collect([]);
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
            'parameters'                => $formattedParameters ?? [],
            'aws_custom_script'         => $scripts['custom_script'] ?? '',
            'aws_custom_package_json'   => $scripts['custom_package_json'] ?? '',
            'tags'                      => $tags
        ];
    }
}
