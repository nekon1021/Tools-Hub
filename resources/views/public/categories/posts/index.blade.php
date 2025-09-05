{{-- resources/views/public/categories/posts/index.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
@endphp

@section('title', $category->name . ' の記事一覧｜' . config('app.name'))

@section('meta')
  <meta name="description" content="{{ $category->name }}の公開記事一覧">
  <link rel="canonical" href="{{ route('public.categories.posts.index', $category->slug) }}">
@endsection

@section('content')
  <div class="mb-6 flex items-center gap-3">
    <h1 class="text-2xl font-bold">{{ $category->name }} の記事一覧</h1>
    <div class="ml-auto">
      <a href="{{ route('public.posts.index') }}" class="px-3 py-1.5 border rounded">すべての記事</a>
    </div>
  </div>

  {{-- 検索フォーム（カテゴリ内検索） --}}
  <form method="GET" class="mb-6 flex flex-wrap gap-2 items-center">
    <input name="q" value="{{ request('q') }}" placeholder="このカテゴリ内を検索"
           class="border rounded px-3 py-2 w-full sm:w-64">
    {{-- 必要なら日付/著者などのフィルタを追加 --}}
    <button class="border rounded px-3 py-2">検索</button>
    <a href="{{ route('public.categories.posts.index', $category->slug) }}" class="border rounded px-3 py-2">クリア</a>
  </form>

  {{-- ★ 一覧トップ広告（必要に応じてどちらか片方だけ使用） --}}
  {{-- <x-ad.slot id="list-top" class="mb-6" /> --}}
  {{-- @includeIf('partials.ads.list-top') --}}

  @forelse ($posts as $p)
    {{-- ★ インフィード広告：3件目の前＆以後6の倍数ごと --}}
    @if ($loop->iteration === 3 || ($loop->iteration > 3 && (($loop->iteration - 3) % 6) === 0))
      {{-- <x-ad.slot id="list-grid" class="my-6" /> --}}
      {{-- @includeIf('partials.ads.list-grid') --}}
    @endif

    <article class="mb-4 rounded border bg-white overflow-hidden">
      <div class="grid sm:grid-cols-[200px_1fr]">
        {{-- 左：アイキャッチ（16:9固定, CLS対策） --}}
        <a href="{{ route('public.posts.show', $p->slug) }}" class="block">
          @php
            // 実装側のサムネ優先順（存在しない場合に備えてフォールバック）
            $base = $p->eyecatch_url
                      ?? $p->thumbnail_url
                      ?? (isset($p->og_image_path) ? Storage::disk('public')->url($p->og_image_path) : null)
                      ?? (isset($p->cover_path) ? Storage::url($p->cover_path) : null);

            $img1x = $p->thumb_200x112_url ?? ($base ?: 'https://placehold.jp/200x112.png');
            $img2x = $p->thumb_400x225_url ?? ($base ?: 'https://placehold.jp/400x225.png');
          @endphp

          <div class="aspect-[16/9]">
            <img
              src="{{ $img1x }}"
              srcset="{{ $img1x }} 200w, {{ $img2x }} 400w"
              sizes="(min-width:640px) 200px, 100vw"
              width="400" height="225"  {{-- CLS対策：縦横比を固定 --}}
              alt="{{ $p->title ?: '記事の画像' }}"
              class="w-full h-full object-cover"
              loading="lazy" decoding="async"
            >
          </div>
        </a>

        {{-- 右：タイトル/メタ/抜粋/ボタン --}}
        <div class="p-4 flex flex-col">
          <h2 class="text-lg font-semibold leading-snug line-clamp-2">
            <a href="{{ route('public.posts.show', $p->slug) }}" class="hover:underline">
              {{ $p->title }}
            </a>
          </h2>

          <div class="mt-1 text-xs text-gray-500 flex flex-wrap gap-x-3">
            <span>{{ optional($p->published_at)->format('Y-m-d') }}</span>
            <span>by {{ $p->user->name ?? '—' }}</span>
            {{-- カテゴリ名も出したければ以下をON（同じカテゴリなので省略でもOK） --}}
            {{-- @if($p->category) <span>｜{{ $p->category->name }}</span> @endif --}}
          </div>

          <p class="mt-2 text-sm text-gray-700 line-clamp-3">
            {{ Str::limit(strip_tags($p->excerpt ?? $p->lead ?? $p->body ?? ''), 140) }}
          </p>

          <div class="mt-auto self-end pt-4">
            <a href="{{ route('public.posts.show', $p->slug) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded border hover:bg-gray-50">
              記事を読む
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                      d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"
                      clip-rule="evenodd"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </article>
  @empty
    <p class="text-gray-500">このカテゴリに公開記事はありません。</p>
  @endforelse

  <div class="mt-6">{{ $posts->links() }}</div>

  {{-- （任意）一覧ボトム広告 --}}
  {{-- <x-ad.slot id="list-bottom" class="mt-8" /> --}}
  {{-- @includeIf('partials.ads.list-bottom') --}}
@endsection

{{-- Tailwindのline-clamp未導入環境向けの簡易フォールバック --}}
@push('styles')
  <style>
    .line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
  </style>
@endpush
