@extends('layouts.app')

@section('title', 'サイトマップ')

@section('content')
<main class="container mx-auto max-w-5xl px-4 py-10">
  <h1 class="text-2xl font-bold mb-8">サイトマップ</h1>

  {{-- ツール一覧 --}}
  <section aria-labelledby="sm-static" class="mb-10">
    <h2 id="sm-static" class="text-xl font-semibold mb-3">ツール一覧</h2>
    <ul class="grid sm:grid-cols-2 gap-2">
      @foreach($staticLinks as $link)
        <li>
          <a href="{{ $link['url'] }}" class="underline hover:no-underline focus:outline-none focus:ring rounded">
            {{ $link['label'] }}
          </a>
        </li>
      @endforeach
    </ul>
  </section>

  {{-- カテゴリ --}}
  <section aria-labelledby="sm-cats" class="mb-10">
    <h2 id="sm-cats" class="text-xl font-semibold mb-3">記事カテゴリ</h2>
    <ul class="grid sm:grid-cols-2 gap-2">
      @forelse($categories as $cat)
        <li>
          <a href="{{ route('public.categories.posts.index', ['category' => $cat->slug]) }}"
             class="underline hover:no-underline focus:outline-none focus:ring rounded">
            {{ $cat->name }}
          </a>
        </li>
      @empty
        <li class="text-gray-500">カテゴリはありません</li>
      @endforelse
    </ul>
  </section>

  {{-- 最新記事 --}}
  <section aria-labelledby="sm-posts">
    <h2 id="sm-posts" class="text-xl font-semibold mb-3">最新記事</h2>
    <ul class="space-y-2">
      @forelse($recentPosts as $post)
        <li>
          <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}"
             class="underline hover:no-underline focus:outline-none focus:ring rounded">
            {{ $post->title }}
          </a>
        </li>
      @empty
        <li class="text-gray-500">公開記事はありません</li>
      @endforelse
    </ul>
  </section>
</main>
@endsection
