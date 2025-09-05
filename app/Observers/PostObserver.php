<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\SlugRedirect;
use Symfony\Component\CssSelector\Node\FunctionNode;
use Illuminate\Support\Str;


class PostObserver
{

    public function creating(Post $post): void
    {
        // 念のため小文字化（ルータ制約も [a-z0-9-]+）
        if (!blank($post->slug)) {
            $post->slug = Str::lower($post->slug);
        }
    }

    public function updating(Post $post): void
    {
        if ($post->isDirty('slug')) {
            $old = $post->getOriginal('slug');
            if ($old && $old !== $post->slug) {
                SlugRedirect::firstOrCreate(
                    ['old_slug' => $old],
                    ['post_id' => $post->id],
                );
            }
        }
    }

    public function saved(Post $post): void
    {
        // サイトマップ系キャッシュを破棄
        cache()->forget('sitemap:index');
        cache()->forget('sitemap:posts');
        cache()->forget('html_sitemap'); // HTMLサイトマップを使っている場合
    }

    public function deleted(Post $post): void
    {
        $this->saved($post); // 410 になる想定なのでキャッシュもクリア
    }

    public function restored(Post $post): void
    {
        $this->saved($post);
    }
}