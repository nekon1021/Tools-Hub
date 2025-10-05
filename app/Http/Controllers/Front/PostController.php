<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\PostIndexRequest;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * 公開記事の一覧（検索＋ページング）
     */
    public function index(PostIndexRequest $request): View
    {
        $f   = $request->validated();
        $per = (int)($f['per_page'] ?? 12);

        // 許可ソート（ホワイトリスト）
        $allowedSort = ['published_at', 'created_at', 'title'];
        $sort = in_array($f['sort'] ?? 'published_at', $allowedSort, true) ? $f['sort'] : 'published_at';
        $dir  = ($f['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $q = Post::query()
            ->published()
            ->with(['user:id,name']) // 必要に応じて 'category:id,name,slug' を追加
            ->select(['id','title','slug','lead','og_image_path','published_at','user_id']);

        if (!empty($f['q']))    $q->keyword($f['q']);
        if (!empty($f['from'])) $q->whereDate('published_at', '>=', $f['from']);
        if (!empty($f['to']))   $q->whereDate('published_at', '<=', $f['to']);

        if (!empty($f['author'])) {
            $q->whereHas('user', fn($qq) => $qq->where('name', $f['author']));
        }

        // タグ機能がある場合のみ
        if (!empty($f['tag']) && method_exists(Post::class, 'tags')) {
            $q->whereHas('tags', fn($qq) => $qq->where('slug', $f['tag']));
        }

        $posts = $q->orderBy($sort, $dir)
            ->paginate($per)
            ->withQueryString();

        return view('public.posts.index', compact('posts', 'f', 'sort', 'dir'));
    }

    /**
     * 公開記事の詳細（slug）
     */
    public function show(string $slug): View
    {
        // 記事本体（作成者・カテゴリを事前読込）
        $post = Post::query()
            ->published()
            ->with(['user:id,name','category:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        // --- サイドバー：カテゴリ（公開記事数つき）
        $sidebarCategories = Cache::remember(
            'sidebar:categories_with_counts',
            now()->addMinutes(10),
            function () {
                return Category::query()
                    ->withCount(['posts as posts_count' => fn($q) => $q->published()])
                    ->orderBy('name')
                    ->get(['id','name','slug']);
            }
        );

        /* ▼ 追加：関連記事（同カテゴリの最新6件：自分除外） */
        $relatedPosts = Cache::remember(
            "post:{$post->id}:related",
            now()->addMinutes(30),
            function () use ($post) {
                $q = Post::query()->published()->where('id','!=',$post->id);
                if ($post->category_id) {
                    $q->where('category_id', $post->category_id);
                }
                return $q->latest('published_at')
                        ->limit(6)
                        ->get(['id','slug','title','published_at']);
            }
        );

        /* ▼ 追加：次に読む（同カテゴリで「今より新しい」→無ければ同カテゴリの最新）
        カテゴリが無い記事は全体最新でフォールバック */
        $nextPostQuery = Post::query()->published()->where('id','!=',$post->id);
        if ($post->category_id) {
            $nextPostQuery->where('category_id', $post->category_id);
        }
        $nextPost = (clone $nextPostQuery)
            ->where('published_at','>', $post->published_at ?? now()->subCentury())
            ->orderBy('published_at','asc')
            ->first(['id','slug','title']);

        if (!$nextPost) {
            $nextPost = (clone $nextPostQuery)
                ->latest('published_at')
                ->first(['id','slug','title']);
        }

        /* ▼ 追加：フォールバック候補（カテゴリ別“推し記事” → 全体ピラー → 一覧URL） */
        $categorySlug = optional($post->category)->slug;

        // config/next_read.php に設定（無ければ null でスキップ）
        $fallbackSlug = $categorySlug ? config("next_read.by_category.$categorySlug") : null;
        if (!$fallbackSlug) {
            $fallbackSlug = config('next_read.fallback_slug'); // 全体共通ピラー（任意）
        }

        $fallbackPost = $fallbackSlug
            ? Post::query()->published()->where('slug', $fallbackSlug)->first(['id','slug','title'])
            : null;

        // 最後の保険URL（カテゴリがあればカテゴリ一覧、なければ記事一覧）
        $fallbackUrl = $fallbackPost
            ? null
            : ($categorySlug
                ? route('public.categories.posts.index', $categorySlug)
                : route('public.posts.index'));

        // ここだけで return（下の古い return は削除）
        return view('public.posts.show', [
            'post'              => $post,
            'sidebarCategories' => $sidebarCategories,
            'relatedPosts'      => $relatedPosts,
            'nextPost'          => $nextPost,
            'fallbackPost'      => $fallbackPost,
            'fallbackUrl'       => $fallbackUrl,
        ]);
    }

}
