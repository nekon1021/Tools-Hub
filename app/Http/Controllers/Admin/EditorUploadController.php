<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditorUploadController extends Controller
{
    public function store(Request $request)
    {
        // 1) バリデーション
        $data = $request->validate([
            'file' => ['required','image','mimes:jpeg,jpg,png,webp','max:4096'], // 4MB
        ]);

        // 2) 保存先（public ディスク）
        $dir  = 'posts/editor/' . now()->format('Y/m');
        $path = $request->file('file')->store($dir, 'public'); // ← 相対キーが返る（例: posts/editor/2025/09/xxx.webp）

        // 3) 公開URLを作る（IDE赤波線回避のため asset() を使用）
        $publicUrl = asset('storage/' . ltrim($path, '/'));

        // 4) エディタ側が期待する JSON 形式で返す（location が必須）
        return response()->json(['location' => $publicUrl]);
    }
}
