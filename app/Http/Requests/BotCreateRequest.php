<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BotCreateRequest extends FormRequest
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
            'name'                      => 'required|string',
            'description'               => 'nullable|string',
            'tags'                      => 'array',
            'type'                      => 'in:private,public',
            'users'                     => 'array',
            'aws_custom_script'         => 'nullable|string',
            'aws_custom_package_json'   => 'nullable|json',
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
            'aws_custom_package_json.unique' => 'The package.json must be a valid JSON string.',
        ];
    }
}
