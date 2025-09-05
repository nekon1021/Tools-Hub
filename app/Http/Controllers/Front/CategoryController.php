<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index(Category $category): View
    {
        $posts = $category->posts()
            ->published()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.categories.posts.index', compact('category', 'posts'));
    }
}
