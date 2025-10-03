<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(Request $request, Category $category): View
    {
        $qRaw = (string) $request->query('q', '');
        $q    = $this->normalizeJa(trim($qRaw));

        $query = $category->posts()
            ->published()
            ->with(['user:id,name', 'category:id,name,slug']);

        if ($q !== '') {
            // LIKE用に % _ \ をエスケープ
            $escaped = $this->escapeLike($q);

            // タイトル部分一致（先頭一致を少し優先：任意）
            $query->where('title', 'LIKE', "%{$escaped}%")
                  ->orderByRaw(
                      'CASE WHEN title LIKE ? THEN 0 ELSE 1 END, published_at DESC',
                      ["{$escaped}%"] // 先頭一致優先
                  );
        } else {
            $query->orderByDesc('published_at');
        }

        $posts = $query->paginate(12)->withQueryString();

        return view('public.categories.posts.index', compact('category', 'posts'));
    }

    /**
     * 日本語の軽い正規化（全角英数/スペース→半角、半角ｶﾅ→全角カナ 等）
     */
    private function normalizeJa(string $s): string
    {
        // a:英数, s:空白, K:カタカナ, V:濁点結合を分離
        return trim(mb_convert_kana($s, 'asKV'));
    }

    /**
     * LIKEエスケープ（% _ \ をリテラルに）
     */
    private function escapeLike(string $s, string $escape = '\\'): string
    {
        return str_replace(
            [$escape,   '%',  '_'],
            [$escape.$escape, $escape.'%', $escape.'_'],
            $s
        );
    }
}
