<?php

namespace App\Http\Requests;

use App\Bot;
use Illuminate\Foundation\Http\FormRequest;

class BotUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $active     = Bot::STATUS_ACTIVE;
        $inactive   = Bot::STATUS_INACTIVE;

        return [
            'update.status'                     => "in:{$active},{$inactive}",
            'update.name'                       => 'string',
            'update.description'                => 'string|nullable',
            'update.platform'                   => 'string|nullable',
            'update.tags'                       => 'array',
            'update.type'                       => 'in:private,public',
            'update.users'                      => 'array',
            'update.aws_custom_script'          => 'nullable|string',
            'update.aws_custom_package_json'    => 'nullable|json'
        ];
    }
}
