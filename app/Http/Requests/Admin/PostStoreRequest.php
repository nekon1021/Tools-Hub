<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],

            'slug'  => [
                'nullable','string','max:200',
                Rule::unique('posts','slug'),
                // 「公開」ボタンのときだけ必須
                Rule::requiredIf(fn() => $this->input('action') === 'publish'),
            ],

            'body' => [
                'required',
                'string',
                function (string $attribute, $value, \Closure $fail) {
                    if (!is_string($value)) {
                        return $fail('本文は必須です。');
                    }
                    // 画像/動画/コード/引用/リスト/見出しがあればOK
                    if (preg_match('#<(img|video|iframe|pre|blockquote|ul|ol|h2|h3)\b#i', $value)) {
                        return;
                    }
                    // テキスト1文字以上
                    $text = strip_tags($value);
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace('/\xC2\xA0|\h/u', ' ', $text);
                    $text = trim($text ?? '');
                    if (mb_strlen($text) < 1) {
                        $fail('本文は必須です。');
                    }
                },
            ],

            'lead'  => ['nullable', 'string', 'max:2000'],

            // アイキャッチ
            'eyecatch' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],

            // 広告
            'show_ad_under_lead' => ['sometimes', 'boolean'],
            'show_ad_in_body'    => ['sometimes', 'boolean'],
            'ad_in_body_max'     => ['nullable', 'integer', 'min:0', 'max:5'],
            'show_ad_below'      => ['sometimes', 'boolean'],

            // SEO
            'meta_title'       => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'og_image_path'    => ['nullable', 'string', 'max:255'],

            // 公開設定/カテゴリ
            'published_at' => ['nullable', 'date'],
            'category_id'  => ['nullable','integer','exists:categories,id'],

            'action'       => ['nullable','in:save_draft,publish'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullify = fn ($key) => ($v = $this->input($key)) === '' ? null : $v;

        $this->merge([
            'title'             => trim((string) $this->input('title', '')),
            'slug'              => ($s = trim((string) $this->input('slug', ''))) === '' ? null : $s,
            'lead'              => $nullify('lead'),
            'meta_title'        => $nullify('meta_title'),
            'meta_description'  => $nullify('meta_description'),
            'og_image_path'     => $nullify('og_image_path'),
            'published_at'      => $nullify('published_at'),
            'category_id'       => $nullify('category_id'),

            // ✅ boolean正規化（UI既定に合わせ既定値true）
            'show_ad_under_lead' => $this->boolean('show_ad_under_lead', true),
            'show_ad_in_body'    => $this->boolean('show_ad_in_body', true),
            'show_ad_below'      => $this->boolean('show_ad_below', true),

            // 数値（未入力はnull）
            'ad_in_body_max'     => $this->filled('ad_in_body_max') ? (int) $this->input('ad_in_body_max') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max'      => 'タイトルは200文字以内で入力してください。',

            'slug.unique'      => 'このスラッグは既に使用されています。',
            'slug.max'         => 'スラッグは200文字以内で入力してください。',
            'slug.required'    => '公開するにはスラッグが必要です。', // Rule::requiredIf でもこのキーで出ます

            'body.required' => '本文は必須です。',

            'eyecatch.image' => 'アイキャッチは画像ファイルを指定してください。',
            'eyecatch.mimes' => 'アイキャッチは jpeg/jpg/png/webp のみ対応です。',
            'eyecatch.max'   => 'アイキャッチは4MB以下でアップロードしてください。',

            'ad_in_body_max.min' => '本文中の広告枠は0〜5の範囲で指定してください。',
            'ad_in_body_max.max' => '本文中の広告枠は0〜5の範囲で指定してください。',

            'published_at.date'  => '公開日時は有効な日付で入力してください。',
            'category_id.exists' => '指定されたカテゴリーが存在しません。',
            'action.in'          => '不正な操作です。',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'              => 'タイトル',
            'slug'               => 'スラッグ',
            'body'               => '本文',
            'lead'               => '導入文',
            'eyecatch'           => 'アイキャッチ画像',
            'show_ad_under_lead' => '導入文直下の広告',
            'show_ad_in_body'    => '本文中の広告',
            'ad_in_body_max'     => '本文中の広告最大枠数',
            'show_ad_below'      => '記事末尾の広告',
            'meta_title'         => 'メタタイトル',
            'meta_description'   => 'メタディスクリプション',
            'og_image_path'      => 'OG画像パス',
            'published_at'       => '公開日時',
            'category_id'        => 'カテゴリー',
            'action'             => '操作',
        ];
    }
}
