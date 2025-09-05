<?php

namespace App\Http\Requests\Tool;

use Illuminate\Foundation\Http\FormRequest;

class CountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => 'present|string|max:20000',
            'trim' => 'sometimes|boolean',
            'normalize' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'text.present' => '文字列を入力してください。',
            'text.max' => '文字が長すぎます。(最大20,000文字)。',
        ];
    }
}
