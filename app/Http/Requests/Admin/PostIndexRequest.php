<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'q'        => ['nullable','string','max:200'],               // キーワード
            'status'   => ['nullable','in:all,published,scheduled,draft,trashed'],        // 絞り込み
            'from'     => ['nullable','date'],                           // 公開日From
            'to'       => ['nullable','date','after_or_equal:from'],                           // 公開日To
            'sort'     => ['nullable','in:created_at,published_at,title'], // 並び替え列
            'dir'      => ['nullable','in:asc,desc'],                    // 並び方向
            'per_page' => ['nullable','integer','min:5','max:100'],      // 1ページ件数
        ];
    }

    protected function prepareForValidation(): void
    {
        // ついでにトリムしておくと検索の体感がよくなります
        $this->merge([
            'q' => trim((string) $this->input('q', '')),
        ]);
    }
}
