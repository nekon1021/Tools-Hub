<?php

namespace App\Http\Controllers\Tools\ImageCompressor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\ImageCompressRequest;
use App\Services\Tool\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\StreamedResponse;




class CompressorController extends Controller
{
    public function index()
    {
        return view('tools.image-compressor.index');
    }

    public function compress(ImageCompressRequest $request, ImageCompressor $service)
    {
        $files     = $request->file('images', []);

        // 固定デフォルト
        $quality   = 85;
        $maxSize   = null;
        $strip     = true;

        $batchId   = \Illuminate\Support\Str::uuid()->toString();
        $results   = [];
        $errorList = [];

        foreach ($files as $file) {
            try {
                $one = $service->compressOne($file, $quality, $maxSize, $strip, $batchId);
                $results[] = $one;
            } catch (\Throwable $e) {
                $errorList[] = sprintf('%s の処理に失敗: %s', $file->getClientOriginalName(), $e->getMessage());
            }
        }

        return view('tools.image-compressor.index', compact('results', 'errorList'));
    }

    // ★ Request を受け取り、署名＆暗号化トークン p を検証してからダウンロード
    public function download(Request $request)
    {
        $disk = 'public';
        $enc  = $request->query('p');
        $tok  = $request->query('t'); // 完了検知トークン

        abort_unless(is_string($enc) && $enc !== '', 404);
        abort_unless(is_string($tok) && $tok !== '', 404);

        try {
            $path = decrypt($enc);
        } catch (\Throwable) {
            abort(403);
        }

        $fs = Storage::disk($disk);

        if (!$fs->exists($path)) {
            abort(404);
        }

        $name = basename($path);
        $cookieName = 'dl_' . $tok;

        // ---- ① ローカル(public)の場合は config の root から絶対パスを自力生成（path() 不使用）----
        $driver = (string) config("filesystems.disks.$disk.driver");
        $root   = (string) config("filesystems.disks.$disk.root");
        if ($driver === 'local' && $root !== '') {
            $absolute = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
            if (!is_file($absolute)) {
                abort(404);
            }
            // 完了クッキーはキューで付与（BinaryFileResponse に ->cookie は無い）
            Cookie::queue(cookie($cookieName, '1', 2));
            return response()->download($absolute, $name);
        }

        // ---- ② それ以外（S3 等）は streamDownload + readStream（download()/mimeType()を使わない）----
        $stream = $fs->readStream($path);
        abort_unless(is_resource($stream), 500, 'Failed to open file stream.');

        Cookie::queue(cookie($cookieName, '1', 2));

        // MIME は汎用でOK（正確さが必要なら拡張子から判定を足せます）
        $headers = [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ];

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, $name, $headers);
    }

    public function delete(Request $request)
    {
        $disk = 'public';
        $enc  = $request->input('p');

        // 必須チェック
        abort_unless(is_string($enc) && $enc !== '', 422);

        try {
            $path = decrypt($enc);
        } catch (\Throwable) {
            abort(403);
        }

        $fs = Storage::disk($disk);
        if ($fs->exists($path)) {
            $fs->delete($path);
        }

        // JSON(AJAX)ならJSON、通常POSTならリダイレクト
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('status', '削除しました');
    }

}
