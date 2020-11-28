<?php

namespace App\Http\Requests;

use App\BotInstance;
use Illuminate\Foundation\Http\FormRequest;

class BotInstanceUpdateRequest extends FormRequest
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

        return [
            'update.aws_custom_script'          => 'nullable|string',
            'update.aws_custom_package_json'    => 'nullable|json'
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'update.aws_custom_package_json.unique' => 'The package.json must be a valid JSON string.',
        ];
    }
}
