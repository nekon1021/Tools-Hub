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
            ->with([
                'user:id,name',
                'category:id,name,slug',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        // --- サイドバー：カテゴリ（公開記事数つき）
        $sidebarCategories = Cache::remember(
            'sidebar:categories_with_counts',
            now()->addMinutes(10),
            function () {
                return Category::query()
                    ->withCount([
                        'posts as posts_count' => function ($q) {
                            $q->published();
                        }
                    ])
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']);
            }
        );

        // ★ 最新記事は渡さない
        return view('public.posts.show', compact('post', 'sidebarCategories'));
    }
}
