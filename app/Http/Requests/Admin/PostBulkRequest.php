<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PostBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool)$this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'action' => ['required','in:delete,restore,force-delete'],
            'ids'    => ['required','array','min:1'],
            'ids.*'  => ['integer','exists:posts,id'],
            'status' => ['nullable','in:all,published,draft,trashed'],
        ];
    }
}
