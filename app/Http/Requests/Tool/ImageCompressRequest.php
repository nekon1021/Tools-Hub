<?php

namespace App\Http\Requests\Tool;

use Illuminate\Foundation\Http\FormRequest;

class ImageCompressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'      => ['required','array','max:20'],
            'images.*'    => ['file','image','mimes:jpeg,jpg,png,webp','max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => '画像を選択してください。',
            'images.array'    => '画像の送信形式が不正です。',
            'images.max'      => '一度にアップロードできるのは最大20枚です。',
            'images.*.image'  => '画像ファイルを選択してください。',
            'images.*.mimes'  => '対応形式は JPEG / PNG / WebP です。',
            'images.*.max'    => '各ファイルは最大10MBまでです。',
        ];
    }
}
