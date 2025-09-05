<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 認可は運用ポリシーに合わせて。ここでは管理者のみ許可。
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            // 本文
            'title' => ['required', 'string', 'max:200'],
            'slug'  => [
                'nullable',
                'string',
                'max:200',
                'unique:posts,slug',
                // 公開ボタンのときだけ必須（Enter送信等で action が空でもコントローラ側で既定 save_draft に倒す想定なら nullable でOK）
                'required_if:action,publish',
            ],
            'body'  => ['required', 'string'],
            'lead'  => ['nullable', 'string', 'max:2000'],

            // 画像
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

            // 公開設定
            'published_at' => ['nullable', 'date'],
            'category_id'  => ['nullable','integer','exists:categories,id'],

            // 送信ボタン（Enter送信などで空になりうるため nullable|in）
            'action'       => ['nullable','in:save_draft,publish'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // 空文字は null に正規化
        $nullify = fn ($key) => ($v = $this->input($key)) === '' ? null : $v;

        $this->merge([
            'title'        => trim((string) $this->input('title', '')),
            'slug'         => ($s = trim((string) $this->input('slug', ''))) === '' ? null : $s,
            'lead'         => $nullify('lead'),
            'meta_title'   => $nullify('meta_title'),
            'meta_description' => $nullify('meta_description'),
            'og_image_path'    => $nullify('og_image_path'),
            'published_at'     => $nullify('published_at'),
            'category_id'      => $nullify('category_id'),

            // checkbox 正規化（未送信=既定値）
            'show_ad_under_lead' => $this->boolean('show_ad_under_lead', false),
            'show_ad_in_body'    => $this->boolean('show_ad_in_body', true),
            'show_ad_below'      => $this->boolean('show_ad_below', true),

            // 数値は範囲外を丸めるよりはバリデーションに任せ、未入力は null に
            'ad_in_body_max'     => $this->filled('ad_in_body_max') ? (int) $this->input('ad_in_body_max') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max'      => 'タイトルは200文字以内で入力してください。',

            'slug.unique'       => 'このスラッグは既に使用されています。',
            'slug.required_if'  => '公開するにはスラッグが必要です。',
            'slug.max'          => 'スラッグは200文字以内で入力してください。',

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
