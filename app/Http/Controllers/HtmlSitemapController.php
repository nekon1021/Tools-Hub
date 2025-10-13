<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Post;
use App\Models\Category;

class HtmlSitemapController extends Controller
{
    public function show()
    {
        $data = Cache::remember('html_sitemap', now()->addHours(6), function () {
            return [
                // ツール一覧
                'staticLinks' => [
                    ['label' => '文字数カウント', 'url' => route('tools.charcount')],
                    ['label' => '画像圧縮', 'url' => route('tools.image.compressor')],
                ],
                // カテゴリー一覧
                'categories' => Category::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']),
                // 最近公開の投稿（多すぎ防止で条件）
                'recentPosts' => Post::published()
                    ->latest('published_at')
                    ->limit(200)
                    ->get(['title', 'slug']),
            ];
        });

        return view('public.sitemap.html', $data);
    }
}
