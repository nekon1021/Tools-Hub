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

class PostController extends Controller
{
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
                'og_image_path'      => $v['og_image_path'] ?? null,
            ];

            if ($request->hasFile('eyecatch')) {
                $data['og_image_path'] = $request->file('eyecatch')
                    ->store('posts/eyecatch/' . now()->format('Y/m'), 'public');
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

            if ($request->hasFile('eyecatch')) {
                if ($post->og_image_path) {
                    Storage::disk('public')->delete($post->og_image_path);
                }
                $data['og_image_path'] = $request->file('eyecatch')
                    ->store('posts/eyecatch/' . now()->format('Y/m'), 'public');
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
            Storage::disk('public')->delete($post->og_image_path);
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
