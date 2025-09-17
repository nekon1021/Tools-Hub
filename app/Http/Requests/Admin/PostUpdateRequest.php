<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        /** @var \App\Models\Post|null $post */
        $post   = $this->route('post');
        $postId = $post?->id;

        return [
            'title' => ['required','string','max:200'],

            'slug'  => [
                'nullable','string','max:200',
                // ✅ ゴミ箱除外 + 自分は除外
                Rule::unique('posts','slug')
                    ->ignore($postId)
                    ->where(fn($q) => $q->whereNull('deleted_at')),
                // 公開ボタン時 & 既存slugが空なら必須
                Rule::requiredIf(fn() => $this->input('action') === 'publish' && blank($post?->slug)),
                // （任意）形式制限
                // 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],

            'body'  => [
                'required','string',
                function (string $attribute, $value, \Closure $fail) {
                    if (!is_string($value)) return $fail('本文は必須です。');
                    $html = trim($value);
                    if (preg_match('#<(img|video|iframe|pre|blockquote|ul|ol|h2|h3)\b#i', $html)) return;
                    $text = strip_tags($html);
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace('/\xC2\xA0|\h/u', ' ', $text);
                    if (mb_strlen(trim($text)) < 1) $fail('本文は必須です。');
                },
            ],

            'lead'        => ['nullable','string','max:2000'],
            'category_id' => ['nullable','integer','exists:categories,id'],

            'eyecatch' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:4096'],
            // （任意）比率を厳密にするなら
            // 'eyecatch' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:4096','dimensions:ratio=16/9'],

            // ✅ controllerで使っているので追加
            'remove_eyecatch'    => ['boolean'],

            // ✅ 既定値をマージしているので boolean だけでOK（sometimesは任意）
            'show_ad_under_lead' => ['boolean'],
            'show_ad_in_body'    => ['boolean'],
            'ad_in_body_max'     => ['nullable','integer','min:0','max:5'],
            'show_ad_below'      => ['boolean'],

            'meta_title'       => ['nullable','string','max:70'],
            'meta_description' => ['nullable','string','max:160'],

            // ✅ クライアントからは受け取らない（サーバで決定）
            'og_image_path'    => ['prohibited'],

            'published_at' => ['nullable','date'],
            'action'       => ['nullable','in:save_draft,publish'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullify = fn ($key) => ($v = $this->input($key)) === '' ? null : $v;

        $this->merge([
            'title'            => trim((string) $this->input('title','')),
            'slug'             => ($s = trim((string) $this->input('slug',''))) === '' ? null : $s,
            'lead'             => $nullify('lead'),
            'meta_title'       => $nullify('meta_title'),
            'meta_description' => $nullify('meta_description'),
            // ❌ og_image_path は禁止にしたのでマージしない
            'published_at'     => $nullify('published_at'),
            'category_id'      => $nullify('category_id'),

            // 既定値のマージ
            'show_ad_under_lead' => $this->boolean('show_ad_under_lead', false),
            'show_ad_in_body'    => $this->boolean('show_ad_in_body', true),
            'show_ad_below'      => $this->boolean('show_ad_below', true),
            'remove_eyecatch'    => $this->boolean('remove_eyecatch', false),

            'ad_in_body_max'     => $this->filled('ad_in_body_max') ? (int) $this->input('ad_in_body_max') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max'      => 'タイトルは200文字以内で入力してください。',

            'slug.unique'   => 'このスラッグは既に使用されています。',
            'slug.max'      => 'スラッグは200文字以内で入力してください。',
            'slug.required' => '公開するにはスラッグが必要です。',

            'body.required' => '本文は必須です。',

            'eyecatch.image' => 'アイキャッチは画像ファイルを指定してください。',
            'eyecatch.mimes' => 'アイキャッチは jpeg/jpg/png/webp のみ対応です。',
            'eyecatch.max'   => 'アイキャッチは4MB以下でアップロードしてください。',

            'ad_in_body_max.min' => '本文中の広告枠は0〜5の範囲で指定してください。',
            'ad_in_body_max.max' => '本文中の広告枠は0〜5の範囲で指定してください。',

            'published_at.date'  => '公開日時は有効な日付で入力してください。',
            'category_id.exists' => '指定されたカテゴリーが存在しません。',
            'action.in'          => '不正な操作です。',

            'og_image_path.prohibited' => 'OG画像はアップロードからのみ設定できます。',
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
            'remove_eyecatch'    => 'アイキャッチの削除',
            'show_ad_under_lead' => '導入文直下の広告',
            'show_ad_in_body'    => '本文中の広告',
            'ad_in_body_max'     => '本文中の広告最大枠数',
            'show_ad_below'      => '記事末尾の広告',
            'meta_title'         => 'メタタイトル',
            'meta_description'   => 'メタディスクリプション',
            'published_at'       => '公開日時',
            'category_id'        => 'カテゴリー',
            'action'             => '操作',
        ];
    }
}
