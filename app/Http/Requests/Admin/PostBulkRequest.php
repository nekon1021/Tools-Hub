<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool)$this->user()?->is_admin;
    }

    public function rules(): array
    {
        $action = (string) $this->input('action');

        return [
            'action' => ['required','in:delete,restore,force-delete'],
            'ids'    => ['required','array','min:1'],
            'ids.*'  => [
                'integer',
                'distinct',
                // アクションに応じて対象を制限
                Rule::exists('posts', 'id')->where(function ($q) use ($action) {
                    if ($action === 'delete') {
                        $q->whereNull('deleted_at');           // 未削除のみ
                    } elseif (in_array($action, ['restore', 'force-delete'], true)) {
                        $q->whereNotNull('deleted_at');        // ゴミ箱内のみ
                    }
                }),
            ],
            // scheduled も許容
            'status' => ['nullable','in:all,published,scheduled,draft,trashed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // 単一値でも配列化しておく保険
        $ids = $this->input('ids');
        if (!is_array($ids) && $ids !== null) {
            $ids = [$ids];
        }

        $this->merge([
            'ids' => $ids,
            'status' => ($s = trim((string)$this->input('status',''))) === '' ? null : $s,
        ]);
    }

     public function messages(): array
    {
        return [
            'action.required' => '一括操作の種別を選択してください。',
            'ids.required'    => '対象を1件以上選択してください。',
            'ids.array'       => '対象IDの形式が不正です。',
            'ids.min'         => '対象を1件以上選択してください。',
            'ids.*.distinct'  => '同じ記事が重複して選択されています。',
            'ids.*.exists'    => '選択された記事の一部は現在の操作対象になりません。',
            'status.in'       => 'ステータスの指定が不正です。',
        ];
    }
}
