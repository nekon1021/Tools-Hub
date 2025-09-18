{{-- resources/views/public/categories/posts/index.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
@endphp

@section('title', $category->name . ' の記事一覧｜' . config('app.name'))

@section('meta')
  <meta name="description" content="{{ $category->name }}の公開記事まとめ。基礎知識から実践ノウハウ・最新トレンドまでを分かりやすく整理。目的に合う記事がすぐ見つかります。
">
  <link rel="canonical" href="{{ route('public.categories.posts.index', $category->slug) }}">
@endsection

@section('content')
  {{-- ヘッダ / コントロールバー --}}
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
    <h1 class="text-2xl font-bold">{{ $category->name }} の記事一覧</h1>

    {{-- カテゴリ内検索 --}}
    <form method="GET" class="sm:ml-auto flex w-full max-w-xl items-center gap-2">
      <input
        name="q" value="{{ request('q') }}"
        placeholder="このカテゴリ内を検索"
        class="w-full rounded-xl border px-3 py-2"
      >
      <button class="rounded-xl border px-3 py-2 hover:bg-gray-50">検索</button>
      <a href="{{ route('public.categories.posts.index', $category->slug) }}" class="rounded-xl border px-3 py-2 hover:bg-gray-50">クリア</a>
    </form>
  </div>

  {{-- ★ 一覧トップ広告（任意） --}}
  {{-- <x-ad.slot id="list-top" class="mb-6" /> --}}
  {{-- @includeIf('partials.ads.list-top') --}}

  @php
    // 便利関数：カード用サムネURL
    $thumbUrl = function($p) {
      $base = $p->eyecatch_url
            ?? $p->thumbnail_url
            ?? (isset($p->og_image_path) ? Storage::disk('public')->url($p->og_image_path) : null)
            ?? (isset($p->cover_path) ? Storage::url($p->cover_path) : null);

      return [
        '1x' => $p->thumb_480x270_url ?? ($base ?: 'https://placehold.jp/480x270.png'),
        '2x' => $p->thumb_960x540_url ?? ($base ?: 'https://placehold.jp/960x540.png'),
      ];
    };
  @endphp

  @if($posts->count())
    {{-- レスポンシブカードグリッド：1→2→3列 --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      @foreach($posts as $p)
        @php
          $thumb = $thumbUrl($p);
          // 導入文（カテゴリ/記事一覧共通ロジック）
          $intro = Str::limit(strip_tags($p->excerpt ?? $p->lead ?? $p->body ?? ''), 110);
          // 日付は published_at が無ければ created_at
          $date  = optional($p->published_at ?? $p->created_at)->format('Y-m-d');
        @endphp

        {{-- ★ インフィード広告：3枚目の前＆以後6の倍数ごと --}}
        @if ($loop->iteration === 3 || ($loop->iteration > 3 && (($loop->iteration - 3) % 6) === 0))
          {{-- <x-ad.slot id="list-grid" class="sm:col-span-2 lg:col-span-3" /> --}}
          {{-- @includeIf('partials.ads.list-grid') --}}
        @endif

        <article class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition
                        hover:shadow-md focus-within:shadow-md">
          <a href="{{ route('public.posts.show', $p->slug) }}" class="block relative">
            {{-- 画像：16:9 固定、オーバーレイ＆ズーム演出 --}}
            <div class="relative aspect-[16/9]">
              <img
                src="{{ $thumb['1x'] }}"
                srcset="{{ $thumb['1x'] }} 480w, {{ $thumb['2x'] }} 960w"
                sizes="(min-width:1024px) 360px, (min-width:640px) 50vw, 100vw"
                width="960" height="540"
                alt="{{ $p->title ?: '記事の画像' }}"
                loading="lazy" decoding="async"
                class="h-full w-full object-cover transition-transform duration-300 ease-out
                       group-hover:scale-[1.03] motion-reduce:transition-none"
              />
              {{-- グラデーションオーバーレイ（タイトル可読性UP） --}}
              <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/30 via-black/0 to-transparent"></div>

              {{-- カテゴリページでは現在カテゴリ名を固定バッジで表示（統一感重視） --}}
              <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium shadow backdrop-blur-sm">
                {{ $category->name }}
              </span>
            </div>
          </a>

          <div class="flex flex-col gap-2 p-4">
            <h2 class="text-base font-semibold leading-snug line-clamp-2">
              <a href="{{ route('public.posts.show', $p->slug) }}"
                 class="transition group-hover:text-blue-600 hover:underline">
                {{ $p->title }}
              </a>
            </h2>

            <p class="text-sm text-gray-700 line-clamp-3">{{ $intro }}</p>

            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500">
              @if($date)<time datetime="{{ $date }}">{{ $date }}</time>@endif
              <span class="w-px self-stretch bg-gray-200"></span>
              <span>by {{ $p->user->name ?? '—' }}</span>
            </div>

            <div class="mt-2">
              <a href="{{ route('public.posts.show', $p->slug) }}"
                 class="inline-flex items-center gap-1 rounded-xl border px-3 py-1.5 text-sm hover:bg-gray-50">
                記事を読む
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd"
                        d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
              </a>
            </div>
          </div>
        </article>
      @endforeach
    </div>

    <div class="mt-8">{{ $posts->links() }}</div>
  @else
    {{-- 空状態 --}}
    <div class="rounded-2xl border bg-white p-10 text-center">
      <p class="text-gray-600">このカテゴリに公開記事はありません。</p>
      <div class="mt-4">
        <a href="{{ route('public.posts.index') }}" class="rounded-xl border px-3 py-2 hover:bg-gray-50">すべての記事</a>
      </div>
    </div>
  @endif
@endsection

{{-- Tailwind の line-clamp を未導入でも綺麗に省略表示 --}}
@push('styles')
<style>
  .line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
</style>
@endpush
