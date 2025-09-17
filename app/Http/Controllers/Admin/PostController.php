<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostIndexRequest;
use App\Http\Requests\Admin\PostStoreRequest;
use App\Http\Requests\Admin\PostUpdateRequest;
use App\Http\Requests\Admin\PostBulkRequest;
use App\Models\Post;
use App\Models\Category;
use App\Services\PostHtmlProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PostController extends Controller
{
    private function normalizePublicKey(?string $v): ?string
    {
        if (!$v) return null;
        $v = str_replace('\\', '/', trim($v));   // Windows対策
        $v = ltrim($v, '/');

        // ありがちな接頭辞を剥がして「publicディスクのキー」に統一
        $v = \Illuminate\Support\Str::after($v, 'storage/app/public/');
        $v = \Illuminate\Support\Str::after($v, 'app/public/');
        $v = \Illuminate\Support\Str::after($v, 'public/');
        $v = \Illuminate\Support\Str::after($v, 'storage/');

        $v = trim($v, '/');
        return $v !== '' ? $v : null;
    }

    public function index(PostIndexRequest $request)
    {
        $filters = $request->validated();
        $per = (int)($filters['per_page'] ?? 15);

        $base = Post::query();
        $counts = [
            'all'       => (clone $base)->whereNull('deleted_at')->count(),
            'published' => (clone $base)->whereNull('deleted_at')->published()->count(),
            'scheduled' => (clone $base)->whereNull('deleted_at')->scheduled()->count(),
            'draft'     => (clone $base)->whereNull('deleted_at')->where('is_published', false)->count(),
            'trashed'   => (clone $base)->onlyTrashed()->count(),
        ];

        $posts = Post::forAdminIndex($filters)
            ->with(['user:id,name','category:id,name'])
            ->paginate($per)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'counts'));
    }

    public function create()
    {
        $categories = Category::activeSorted()->get(['id','name']);
        return view('admin.posts.create', compact('categories'));
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        $v = $request->validated();

        $rawBody   = (string)($v['body'] ?? '');
        $processor = app(PostHtmlProcessor::class);
        $processed = $processor->process($rawBody);

        if (!$this->isMeaningfulContent($processed['html'] ?? '')) {
            if ($this->isMeaningfulContent($rawBody)) {
                $processed['html'] = $rawBody;
            } else {
                return back()->withErrors(['body' => '本文は必須です。'])->withInput();
            }
        }

        [$isPublished, $publishedAt] = $this->resolvePublication(
            (string)$request->input('action', 'save_draft'),
            $v['published_at'] ?? null
        );

        if ($isPublished && empty($v['slug'])) {
            return back()->withErrors(['slug' => '公開するにはスラッグが必要です。'])->withInput();
        }

        $post = DB::transaction(function () use ($request, $v, $processed, $isPublished, $publishedAt) {
            $data = [
                'title'              => $v['title'],
                'slug'               => $v['slug'] ?? null,
                'body'               => $processed['html'] ?? '',
                'lead'               => $v['lead'] ?? null,
                'toc_json'           => is_array($processed['headings'] ?? null)
                                          ? json_encode($processed['headings'], JSON_UNESCAPED_UNICODE)
                                          : ($processed['toc'] ?? null),
                'is_published'       => $isPublished,
                'published_at'       => $publishedAt,
                'user_id'            => $request->user()->id,
                'category_id'        => $v['category_id'] ?? null,
                'show_ad_under_lead' => $v['show_ad_under_lead'] ?? false,
                'show_ad_in_body'    => $v['show_ad_in_body'] ?? true,
                'ad_in_body_max'     => $v['ad_in_body_max'] ?? 2,
                'show_ad_below'      => $v['show_ad_below'] ?? true,
                'meta_title'         => $v['meta_title'] ?? null,
                'meta_description'   => $v['meta_description'] ?? null,
                // 'og_image_path'      => $v['og_image_path'] ?? null,
            ];

            // 直上で [$isPublished, $publishedAt] を出しています → それを使う
            $ym = \Illuminate\Support\Carbon::parse($publishedAt ?? now())->format('Y/m');

            if ($request->hasFile('eyecatch')) {
                // DBには相対パスが入る（Storage::url() で表示）
                $stored = $request->file('eyecatch')->store("posts/eyecatch/{$ym}", 'public');
                // store() は通常「相対キー」を返しますが、表記ゆれ対策で normalize
                $data['og_image_path'] = $this->normalizePublicKey($stored);

            }

            return Post::create($data);
        });

        $msg = match ($post->computed_status ?? ($isPublished ? 'published' : 'draft')) {
            'draft'     => '下書きを保存しました。',
            'scheduled' => '公開予約を設定しました。',
            default     => '記事を公開しました。',
        };

        return !$isPublished
            ? redirect()->route('admin.posts.create')->with('success', $msg)
            : redirect()->route('admin.posts.edit', $post)->with('success', $msg);
    }

    public function show(Post $post)
    {
        $post->load(['category:id,name,slug', 'user:id,name']);

        $isDraft     = !$post->is_published;
        $isScheduled = $post->is_published && $post->published_at && $post->published_at->gt(now());

        return view('admin.posts.show', compact('post','isDraft','isScheduled'));
    }

    public function edit(Post $post)
    {
        $categories = Category::activeSorted()->get(['id','name']);
        return view('admin.posts.edit', compact('post','categories'));
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $v = $request->validated();

        $rawBody   = (string)($v['body'] ?? '');
        $processor = app(PostHtmlProcessor::class);
        $processed = $processor->process($rawBody);

        if (!$this->isMeaningfulContent($processed['html'] ?? '')) {
            if ($this->isMeaningfulContent($rawBody)) {
                $processed['html'] = $rawBody;
            } else {
                return back()->withErrors(['body' => '本文は必須です。'])->withInput();
            }
        }

        [$isPublished, $publishedAt] = $this->resolvePublication(
            (string)$request->input('action', 'save_draft'),
            $v['published_at'] ?? null
        );

        if ($isPublished && empty($v['slug'] ?? $post->slug)) {
            return back()->withErrors(['slug' => '公開するにはスラッグが必要です。'])->withInput();
        }

        $post->refresh();

        DB::transaction(function () use ($request, $v, $post, $processed, $isPublished, $publishedAt) {
            $data = [
                'title'              => $v['title'],
                'slug'               => $v['slug'] ?? $post->slug,
                'body'               => $processed['html'] ?? '',
                'lead'               => $v['lead'] ?? null,
                'toc_json'           => is_array($processed['headings'] ?? null)
                                          ? json_encode($processed['headings'], JSON_UNESCAPED_UNICODE)
                                          : ($processed['toc'] ?? null),
                'is_published'       => $isPublished,
                'published_at'       => $publishedAt,
                'category_id'        => $v['category_id'] ?? null,
                'show_ad_under_lead' => $v['show_ad_under_lead'] ?? false,
                'show_ad_in_body'    => $v['show_ad_in_body'] ?? true,
                'ad_in_body_max'     => $v['ad_in_body_max'] ?? 2,
                'show_ad_below'      => $v['show_ad_below'] ?? true,
                'meta_title'         => $v['meta_title'] ?? null,
                'meta_description'   => $v['meta_description'] ?? null,
            ];

            // 既存 or 入力の公開日時を優先（未定義なら now）
            $ym = \Illuminate\Support\Carbon::parse($publishedAt ?? $post->published_at ?? now())->format('Y/m');

            // （任意）チェックボックス等で「画像を削除」するUIを用意している場合の処理
            if ($request->boolean('remove_eyecatch')) {
                if ($post->og_image_path) {
                    $oldKey = $this->normalizePublicKey($post->og_image_path);
                    if ($oldKey) Storage::disk('public')->delete($oldKey);
                }
                $data['og_image_path'] = null;
            }
            
            // 新しい画像が来たら差し替え（上の削除指定より“後勝ち”にしたいなら順序調整）
            if ($request->hasFile('eyecatch')) {
                if ($post->og_image_path) {
                    $oldKey = $this->normalizePublicKey($post->og_image_path);
                    if ($oldKey) Storage::disk('public')->delete($oldKey);
                }
                $stored = $request->file('eyecatch')->store("posts/eyecatch/{$ym}", 'public');
                $data['og_image_path'] = $this->normalizePublicKey($stored);
            }


            $post->update($data);
        });

        $post->refresh();

        $msg = match ($post->computed_status ?? ($isPublished ? 'published' : 'draft')) {
            'draft'     => '下書きを保存しました。',
            'scheduled' => '公開予約を更新しました。',
            default     => '記事を更新しました。',
        };

        return redirect()->route('admin.posts.edit', $post)->with('success', $msg);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();
        return back()->with('success', '記事をゴミ箱に移動しました。');
    }

    public function previewWithTrashed(int $id)
    {
        $post = Post::withTrashed()->with(['category:id,name,slug','user:id,name'])->findOrFail($id);

        $isDraft     = !$post->is_published;
        $isScheduled = $post->is_published && $post->published_at && $post->published_at->gt(now());
        $isTrashed   = !is_null($post->deleted_at);

        return view('admin.posts.show', compact('post','isDraft','isScheduled','isTrashed'));
    }

    public function restore(int $id): RedirectResponse
    {
        $post = Post::withTrashed()->findOrFail($id);
        if (is_null($post->deleted_at)) {
            return back()->withErrors('ゴミ箱ではありません。');
        }
        $post->restore();
        return back()->with('success','復元しました。');
    }

    public function bulk(PostBulkRequest $request): RedirectResponse
    {
        $action = (string)$request->input('action');
        $ids    = (array)$request->input('ids', []);

        $posts = Post::withTrashed()->whereIn('id', $ids)->get();
        if ($posts->isEmpty()) {
            return back()->withErrors('対象が見つかりません。');
        }

        $affected = 0;

        switch ($action) {
            case 'delete':
                foreach ($posts as $p) {
                    if (is_null($p->deleted_at)) {
                        $p->delete();
                        $affected++;
                    }
                }
                $msg = "{$affected}件をゴミ箱へ移動しました。";
                break;

            case 'restore':
                foreach ($posts as $p) {
                    if (!is_null($p->deleted_at)) {
                        $p->restore();
                        $affected++;
                    }
                }
                $msg = "{$affected}件を復元しました。";
                break;

            case 'force-delete':
                foreach ($posts as $p) {
                    if (!is_null($p->deleted_at)) {
                        $this->deleteAttachedFiles($p);
                        $p->forceDelete();
                        $affected++;
                    }
                }
                $msg = "{$affected}件を完全に削除しました。";
                break;

            default:
                return back()->withErrors('不正な操作です。');
        }

        return back()->with('success', $msg);
    }

    private function deleteAttachedFiles(Post $post): void
    {
        if ($post->og_image_path) {
            $key = $this->normalizePublicKey($post->og_image_path);
            if ($key) {
                Storage::disk('public')->delete($key);
            }
        }
    }

    private function resolvePublication(string $action, $publishedAtInput): array
    {
        $willPublish = ($action === 'publish');
        if (!$willPublish) return [false, null];

        if (empty($publishedAtInput)) return [true, now()];

        try {
            $dt = $publishedAtInput instanceof \DateTimeInterface
                ? $publishedAtInput
                : Carbon::parse($publishedAtInput);
        } catch (\Throwable $e) {
            $dt = now();
        }
        return [true, $dt];
    }

    /** サーバ側の「有内容」判定 */
    private function isMeaningfulContent(?string $html): bool
    {
        if (!is_string($html) || trim($html) === '') {
            return false;
        }

        if (preg_match('#<(img|video|iframe|pre|blockquote|ul|ol|h2|h3)\b#i', $html)) {
            return true;
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\xC2\xA0|\h/u', ' ', $text);
        $text = trim($text);

        return mb_strlen($text) > 0;
    }
}
