<?php

namespace App\Services\Tool;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ImageCompressor
{
    public function __construct(
        private string $disk = 'public',
        private string $baseDir = 'tmp/compress'
    ) {}

    /**
     * 1枚処理（バッチID指定で同一フォルダにまとめられる）
     * @return array{name:string,url:string,bytes:int,ratio:int}
     */
    public function compressOne(
        UploadedFile $file,
        int $quality = 85,
        ?int $maxSize = null,
        bool $stripExif = true,
        ?string $batchId = null
    ): array {
        $srcPath = $file->getRealPath();
        if (!$srcPath || !is_file($srcPath)) {
            throw new RuntimeException('Temporary file missing.');
        }

        $info = @getimagesize($srcPath);
        if ($info === false || empty($info['mime'])) {
            throw new RuntimeException('Invalid image data.');
        }
        $mime = $info['mime'];
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            throw new RuntimeException('Unsupported image type: '.$mime);
        }

        $origBytes = $file->getSize() ?: filesize($srcPath) ?: 0;
        $ext       = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $safeExt   = in_array($ext, ['jpg','jpeg','png','webp'], true) ? $ext : $this->extFromMime($mime);

        $baseNameRaw = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fnameBase   = Str::slug($baseNameRaw) ?: ('img-'.Str::random(6));

        $im = $this->createImageResource($srcPath, $mime);
        [$w, $h] = [imagesx($im), imagesy($im)];

        // リサイズ（長辺）
        if ($maxSize && max($w, $h) > $maxSize) {
            [$nw, $nh] = $this->fitToLongSide($w, $h, $maxSize);
            $im = $this->resize($im, $nw, $nh, $this->preserveAlpha($mime));
        }

        // 出力先
        $batchId = $batchId ?: Str::uuid()->toString();
        $outDir  = trim($this->baseDir, '/').'/'.$batchId;
        $this->ensureDir($outDir);

        // JPEGはプログレッシブ
        if ($mime === 'image/jpeg') imageinterlace($im, true);

        // ファイル名
        $fname  = sprintf('%s-q%s%s', $fnameBase, $quality, $maxSize ? '-'.$maxSize.'w' : '');
        $outRel = $outDir.'/'.Str::limit($fname, 80, '').'.'.$safeExt;

        // 保存
        $this->saveImage($im, $mime, $outRel, $quality);

        $bytes = Storage::disk($this->disk)->size($outRel) ?: 0;
        $ratio = $origBytes > 0 ? max(0, (int)round(100 - ($bytes / $origBytes * 100))) : 0;

        return [
            'name'  => basename($outRel),
            'url'   => $this->publicUrl($outRel), // 表示用
            'path'  => $outRel,                   // ★ 追加：Storage上の相対パス（ダウンロード用）
            'bytes' => $bytes,
            'ratio' => $ratio,
        ];
    }

    private function extFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'img',
        };
    }

    private function createImageResource(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => throw new RuntimeException('Unsupported type: '.$mime),
        };
    }

    private function preserveAlpha(string $mime): bool
    {
        return in_array($mime, ['image/png','image/webp'], true);
    }

    private function fitToLongSide(int $w, int $h, int $max): array
    {
        if ($w >= $h) {
            $scale = $max / $w;
            return [ (int)round($w * $scale), (int)round($h * $scale) ];
        }
        $scale = $max / $h;
        return [ (int)round($w * $scale), (int)round($h * $scale) ];
    }

    private function resize($src, int $nw, int $nh, bool $keepAlpha)
    {
        $dst = imagecreatetruecolor($nw, $nh);
        if ($keepAlpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));
        imagedestroy($src);
        return $dst;
    }

    private function saveImage($im, string $mime, string $outRel, int $quality): void
    {
        $pngLevel = $this->mapQualityToPngLevel($quality);

        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($im, null, $quality),
            'image/png'  => imagepng($im, null, $pngLevel),
            'image/webp' => imagewebp($im, null, $quality),
            default      => throw new RuntimeException('Unsupported type: '.$mime),
        };
        $bin = ob_get_clean();

        if ($bin === false) {
            throw new RuntimeException('Failed to encode image.');
        }

        Storage::disk($this->disk)->put($outRel, $bin, ['visibility' => 'public']);
        imagedestroy($im);
    }

    private function mapQualityToPngLevel(int $q): int
    {
        $q = max(1, min(100, $q));
        $rev = 100 - $q; // 0..99
        return (int)round($rev / 11); // 0..9
    }

    private function ensureDir(string $dirRel): void
    {
        if (!Storage::disk($this->disk)->exists($dirRel)) {
            Storage::disk($this->disk)->makeDirectory($dirRel);
        }
    }

    private function publicUrl(string $path): string
    {
        /** @var FilesystemAdapter $disk */
        $disk   = Storage::disk($this->disk);
        $driver = config("filesystems.disks.{$this->disk}.driver"); // 'local' | 's3' など
        $base   = rtrim((string) config("filesystems.disks.{$this->disk}.url"), '/');

        // 1) filesystems.php で URL が設定されていれば最優先（publicローカル想定）
        if ($base !== '') {
            return $base . '/' . ltrim($path, '/');
        }

        // 2) ローカル: storage:link 済みなら asset('storage/...') で公開URLに
        if ($driver === 'local') {
            return asset('storage/' . ltrim($path, '/'));
            // ※ もし「公開URLではなく絶対パス」が欲しい場合は下を使用:
            // return $disk->path($path);
        }

        // 3) クラウド: まず期限付きURL（ACLがprivateでもOK）
        try {
            return $disk->temporaryUrl($path, now()->addMinutes(60));
        } catch (\Throwable $e) {
            // 4) それがダメなら通常の公開URL（バケットがpublicなら有効）
            try {
                return $disk->url($path);
            } catch (\Throwable $e2) {
                // 5) 最後のフォールバック（絶対に何か返す）
                return $path;
            }
        }
    }
}
