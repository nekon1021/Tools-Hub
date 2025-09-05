<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class PostIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 公開一覧は誰でも閲覧可
    }

    public function rules(): array
    {
        return [
            'q'        => ['nullable','string','max:200'],
            'from'     => ['nullable','date'],
            'to'       => ['nullable','date','after_or_equal:from'],
            'author'   => ['nullable','string','max:100'],
            'tag'      => ['nullable','string','max:100'],
            'sort'     => ['nullable','in:published_at,created_at'],
            'dir'      => ['nullable','in:asc,desc'],
            'per_page' => ['nullable','integer','min:10','max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sort = $this->input('sort', 'published_at');
        $dir  = $this->input('dir',  'desc');
        $per  = (int) $this->input('per_page', 12);

        // サーバ側で堅く正規化
        if (!in_array($sort, ['published_at','created_at'], true)) $sort = 'published_at';
        if (!in_array($dir,  ['asc','desc'], true))               $dir  = 'desc';
        $per = max(10, min($per, 50));

        $this->merge(compact('sort','dir','per')); // per→per_page に合わせて下で rename
        $this->merge(['per_page' => $per]);
    }
}
