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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /** 一覧 */
    public function index(PostIndexRequest $request)
    {
        $filters = $request->validated();
        $per = (int)($filters['per_page'] ?? 15);

        // タブ用件数（未削除/ゴミ箱を分けて集計）
        $base = Post::query();
        $counts = [
            'all'       => (clone $base)->whereNull('deleted_at')->count(),
            'published' => (clone $base)->whereNull('deleted_at')->published()->count(),
            'scheduled' => (clone $base)->whereNull('deleted_at')->scheduled()->count(), // 予約
            'draft'     => (clone $base)->whereNull('deleted_at')->where('is_published', false)->count(),
            'trashed'   => (clone $base)->onlyTrashed()->count(),
        ];

        // 一覧本体（statusに応じたスコープで取得）
        $posts = Post::forAdminIndex($filters)
            ->with(['user:id,name','category:id,name'])
            ->paginate($per)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'counts'));
    }

    /** 作成画面 */
    public function create()
    {
        $categories = Category::activeSorted()->get(['id','name']);
        return view('admin.posts.create', compact('categories'));
    }

    /** 保存 */
    public function store(PostStoreRequest $request): RedirectResponse
    {
        $v = $request->validated();

        // 本文を正規化（目次生成など）
        $processor = app(PostHtmlProcessor::class);
        $processed = $processor->process($v['body']);

        // action / published_at から公開状態を決定
        [$isPublished, $publishedAt] = $this->resolvePublication(
            (string)$request->input('action', 'save_draft'),
            $v['published_at'] ?? null
        );

        // 公開するのに slug が空なら弾く（運用ポリシーが「公開時のみ必須」の場合）
        if ($isPublished && empty($v['slug'])) {
            return back()->withErrors('公開するにはスラッグが必要です。')->withInput();
        }

        $post = DB::transaction(function () use ($request, $v, $processed, $isPublished, $publishedAt) {
            $data = [
                'title'           => $v['title'],
                'slug'            => $v['slug'] ?? null,  // 自動生成しない方針
                'body'            => $processed['body'],
                'lead'            => $v['lead'] ?? null,
                'toc_json'        => $processed['toc'],
                'is_published'    => $isPublished,
                'published_at'    => $publishedAt,
                'user_id'         => $request->user()->id,
                'category_id'     => $v['category_id'] ?? null,
                'show_ad_under_lead' => (bool)($v['show_ad_under_lead'] ?? true),
                'show_ad_in_body'    => (bool)($v['show_ad_in_body'] ?? true),
                'ad_in_body_max'     => (int)($v['ad_in_body_max'] ?? 2),
                'show_ad_below'      => (bool)($v['show_ad_below'] ?? true),
                'meta_title'         => $v['meta_title'] ?? null,
                'meta_description'   => $v['meta_description'] ?? null,
                'og_image_path'      => $v['og_image_path'] ?? null,
            ];

            // アイキャッチ保存（任意）
            if ($request->hasFile('eyecatch')) {
                $data['og_image_path'] = $request->file('eyecatch')
                    ->store('posts/eyecatch/' . now()->format('Y/m'), 'public');
            }

            return Post::create($data);
        });

        // モデル側に computed_status があればそれで分岐
        $msg = match ($post->computed_status ?? ($isPublished ? 'published' : 'draft')) {
            'draft'     => '下書きを保存しました。',
            'scheduled' => '公開予約を設定しました。',
            default     => '記事を公開しました。',
        };

        // ★ ここで分岐：下書きは create へ、公開は edit へ
    if (!$isPublished) {
        return redirect()
            ->route('admin.posts.create')
            ->with('success', $msg);
        // ->with('draft_id', $post->id) などを付けると、メッセージ内で「編集へ」リンクも出せます
    }

    return redirect()
        ->route('admin.posts.edit', $post)
        ->with('success', $msg);
    }

    /** 詳細（管理プレビュー） */
    public function show(Post $post)
    {
        // 下書き・予約も表示対象（公開判定はフロントで）
        $post->load(['category:id,name,slug', 'user:id,name']);

        $isDraft     = !$post->is_published;
        $isScheduled = $post->is_published && $post->published_at && $post->published_at->gt(now());

        return view('admin.posts.show', compact('post','isDraft','isScheduled'));
    }

    /** 編集画面 */
    public function edit(Post $post)
    {
        $categories = Category::activeSorted()->get(['id','name']);
        return view('admin.posts.edit', compact('post','categories'));
    }

    /** 更新 */
    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $v = $request->validated();

        $processor = app(PostHtmlProcessor::class);
        $processed = $processor->process($v['body']);

        [$isPublished, $publishedAt] = $this->resolvePublication(
            (string)$request->input('action', 'save_draft'),
            $v['published_at'] ?? null
        );

        // 公開なのに slug 空は拒否（ポリシーに合わせて）
        if ($isPublished && empty($v['slug'] ?? $post->slug)) {
            return back()->withErrors('公開するにはスラッグが必要です。')->withInput();
        }

        $post->refresh(); // 競合更新に弱いケースの最小ケア

        DB::transaction(function () use ($request, $v, $post, $processed, $isPublished, $publishedAt) {
            $data = [
                'title'           => $v['title'],
                'slug'            => $v['slug'] ?? $post->slug,
                'body'            => $processed['body'],
                'lead'            => $v['lead'] ?? null,
                'toc_json'        => $processed['toc'],
                'is_published'    => $isPublished,
                'published_at'    => $publishedAt,
                'category_id'     => $v['category_id'] ?? null,
                'show_ad_under_lead' => (bool)($v['show_ad_under_lead'] ?? true),
                'show_ad_in_body'    => (bool)($v['show_ad_in_body'] ?? true),
                'ad_in_body_max'     => (int)($v['ad_in_body_max'] ?? 2),
                'show_ad_below'      => (bool)($v['show_ad_below'] ?? true),
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

    /** 削除（＝ゴミ箱へ移動） */
    public function destroy(Post $post): RedirectResponse
    {
        $post->delete(); // ソフトデリート
        return back()->with('success', '記事をゴミ箱に移動しました。');
    }

    /** ゴミ箱も対象のプレビュー（ID指定で開く想定） */
    public function previewWithTrashed(int $id)
    {
        $post = Post::withTrashed()->with(['category:id,name,slug','user:id,name'])->findOrFail($id);

        $isDraft     = !$post->is_published;
        $isScheduled = $post->is_published && $post->published_at && $post->published_at->gt(now());
        $isTrashed   = !is_null($post->deleted_at);

        return view('admin.posts.show', compact('post','isDraft','isScheduled','isTrashed'));
    }

    /** 単体：復元（ゴミ箱→元に戻す） */
    public function restore(int $id): RedirectResponse
    {
        $post = Post::withTrashed()->findOrFail($id);
        if (is_null($post->deleted_at)) {
            return back()->withErrors('ゴミ箱ではありません。');
        }
        $post->restore();
        return back()->with('success','復元しました。');
    }

    /** 一括操作：削除/復元/完全削除 */
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
            case 'delete': // ソフトデリート
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

    /** 付随ファイルの削除（完全削除時に呼ぶ） */
    private function deleteAttachedFiles(Post $post): void
    {
        if ($post->og_image_path) {
            Storage::disk('public')->delete($post->og_image_path);
        }
        // 派生サムネイル等があればここで追加削除
    }

    /**
     * action と published_at 入力から公開状態を決定
     * - 下書き: is_published=false / published_at=null
     * - 即時公開: is_published=true / published_at=now()
     * - 予約公開: is_published=true / published_at=指定値（未来）
     */
    private function resolvePublication(string $action, $publishedAtInput): array
    {
        $willPublish = ($action === 'publish');

        if (!$willPublish) {
            return [false, null];
        }

        // $publishedAtInput は null or string or \DateTimeInterface
        if (empty($publishedAtInput)) {
            return [true, now()];
        }

        // フォームリクエストで date バリデート済み前提だが、保険で parse
        try {
            $dt = $publishedAtInput instanceof \DateTimeInterface ? $publishedAtInput : now()->parse($publishedAtInput);
        } catch (\Throwable $e) {
            $dt = now(); // パース失敗時は即時公開に倒す
        }

        return [true, $dt];
    }
}
