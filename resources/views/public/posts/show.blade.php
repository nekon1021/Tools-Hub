{{-- resources/views/public/posts/show.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // メタ
  $metaTitle = $post->meta_title ?: $post->title;
  $metaDesc  = $post->meta_description
    ?: ($post->lead ? Str::limit($post->lead, 120) : Str::limit(strip_tags($post->body), 160, '…'));

  // 目次
  $toc = is_array($post->toc_json) ? $post->toc_json : (json_decode($post->toc_json ?? '[]', true) ?: []);

  // アイキャッチ（存在しなければダミーSVG）
  $ey = !empty($post->og_image_path) ? Storage::disk('public')->url($post->og_image_path) : null;
  if (!$ey) {
      $ey = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
        "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1600 900'>
           <rect width='1600' height='900' fill='#e5e7eb'/>
           <text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle'
                 font-family='system-ui, -apple-system, Segoe UI, Roboto'
                 font-size='56' fill='#9ca3af'>DUMMY IMAGE 16:9</text>
         </svg>"
      );
  }
@endphp

@section('title', $metaTitle)

@section('meta')
  <meta name="description" content="{{ $metaDesc }}">
  <link rel="canonical" href="{{ route('public.posts.show', $post->slug) }}">
@endsection

@section('content')
<div class="container mx-auto max-w-5xl px-4 py-6">
  <div class="mb-6 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">記事詳細</h1>
    <div class="ml-auto">
      <a href="{{ route('public.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
    </div>
  </div>

  {{-- 2カラム（PCでサイドレール） --}}
  <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px] items-start">
    <article class="mx-auto max-w-3xl lg:col-start-1 lg:row-start-1">
      <header class="mb-6">
        <h2 class="text-3xl font-bold mb-2">{{ $post->title }}</h2>
        <div class="text-sm text-gray-500 space-x-2">
          @if($post->published_at)
            <span>公開日時: {{ $post->published_at->format('Y-m-d H:i') }}</span>
          @endif
          @if($post->category)
            <span>｜カテゴリ:
              <a href="{{ route('public.categories.posts.index', $post->category->slug) }}"
                class="underline hover:no-underline">
                {{ $post->category->name }}
              </a>
            </span>
          @endif

          @if($post->user)
            <span>｜作成者: {{ $post->user->name }}</span>
          @endif
        </div>
        <figure class="mt-4">
          <img src="{{ $ey }}" alt="{{ $post->title }}" class="w-full aspect-[16/9] object-cover rounded">
        </figure>
      </header>

      @if($post->lead)
        <div class="mb-4 editor-prose text-[0.95rem] text-gray-800">
          {!! nl2br(e($post->lead)) !!}
        </div>
      @endif

      {{-- リード直下広告（必要時ON） --}}
      {{-- @if(!empty($post->show_ad_under_lead)) --}}
        {{-- <x-ad.slot id="under-lead" class="my-6" /> --}}
      {{-- @endif --}}

      {{-- 目次 --}}
      @if(!empty($toc))
        <nav class="mb-6 rounded border bg-gray-50 p-4 text-sm">
          <h3 class="font-semibold mb-2">目次</h3>
          <ol class="list-decimal pl-5 space-y-1">
            @php $open=false; @endphp
            @foreach($toc as $it)
              @if(($it['level'] ?? $it['depth'] ?? 2) == 2)
                @if($open)</ul></li>@php $open=false; @endphp @endif
                <li>
                  <a class="underline" href="#{{ $it['id'] ?? '' }}">{{ $it['text'] ?? '' }}</a>
                  @php $open=true; @endphp
                  <ul class="toc-sub pl-5 mt-1 space-y-1">
              @else
                  <li><a class="underline" href="#{{ $it['id'] ?? '' }}">{{ $it['text'] ?? '' }}</a></li>
              @endif
            @endforeach
            @if($open)</ul></li>@endif
          </ol>
        </nav>
      @endif

      {{-- 本文 --}}
      <div id="postBody" class="editor-prose mb-8">
        {!! $post->body !!}
      </div>

      {{-- 本文中広告テンプレ（必要時ON） --}}
      {{-- @if(!empty($post->show_ad_in_body)) --}}
        {{-- <template id="ad-in-body-tpl">
          <x-ad.slot id="in-body" class="my-6" />
        </template> --}}
      {{-- @endif --}}

      {{-- 記事末尾広告（必要時ON） --}}
      {{-- @if(!empty($post->show_ad_below)) --}}
        {{-- <x-ad.slot id="below" class="my-10" /> --}}
      {{-- @endif --}}
    </article>

    {{-- ★ 右サイドレール：モバイル＝記事下 / PC＝右サイド --}}
    <aside class="block mt-8 lg:mt-0 lg:col-start-2 lg:row-start-1" role="complementary" aria-label="サイドコンテンツ">
      <div class="w-full mx-auto space-y-6 lg:sticky lg:top-4 lg:w-[300px]">

        {{-- 最新記事 --}}
        <section aria-labelledby="latest-heading" class="rounded border bg-white">
          <h3 id="latest-heading" class="px-3 py-2 text-sm font-semibold border-b">最新記事</h3>
          @if(!empty($latestPosts) && $latestPosts->count())
            <ul class="divide-y">
              @foreach($latestPosts as $lp)
                @php
                  $thumb = $lp->og_image_path
                    ? Storage::disk('public')->url($lp->og_image_path)
                    : 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
                        "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 200 200\">
                          <rect width=\"200\" height=\"200\" fill=\"#eef2f7\"/>
                          <text x=\"50%\" y=\"50%\" dominant-baseline=\"middle\" text-anchor=\"middle\"
                                font-family=\"system-ui, -apple-system, Segoe UI, Roboto\"
                                font-size=\"14\" fill=\"#94a3b8\">NO IMAGE</text>
                        </svg>"
                      );
                @endphp
                <li class="p-3">
                  <a href="{{ route('public.posts.show', $lp->slug) }}" class="flex gap-3 group">
                    <img src="{{ $thumb }}" alt="" loading="lazy"
                          class="w-16 h-16 sm:w-20 sm:h-20 rounded object-cover shrink-0">
                    <div class="min-w-0">
                      <div class="text-sm font-medium group-hover:underline clamp-2">{{ $lp->title }}</div>
                      <div class="text-xs text-gray-500 mt-1">{{ optional($lp->published_at)->format('Y-m-d') }}</div>
                    </div>
                  </a>
                </li>
              @endforeach
            </ul>
          @else
            <div class="px-3 py-4 text-sm text-gray-500">記事がありません。</div>
          @endif
        </section>

        {{-- カテゴリ --}}
        <section aria-labelledby="cat-heading" class="rounded border bg-white">
          <h3 id="cat-heading" class="px-3 py-2 text-sm font-semibold border-b">カテゴリ</h3>
          @if(!empty($sidebarCategories) && $sidebarCategories->count())
            <ul class="p-2 text-sm">
              @foreach($sidebarCategories as $cat)
                <li>
                  <a href="{{ route('public.categories.posts.index', $cat->slug) }}"
                    class="flex items-center justify-between px-2 py-2 rounded hover:bg-gray-50">
                    <span>{{ $cat->name }}</span>
                    <span class="text-xs text-gray-500">{{ $cat->posts_count }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @else
            <div class="px-3 py-4 text-sm text-gray-500">カテゴリがありません。</div>
          @endif
        </section>

        {{-- （任意）サイド広告はPCのみ固定表示にしたい場合はこのままでOK --}}
        {{-- <x-ad.slot id="side-rail" class="block w-full lg:w-[300px] mx-auto" /> --}}
      </div>
    </aside>
  </div>
</div>

<style>
  .editor-prose { color:#111827; }
  .editor-prose p { margin:.7em 0; line-height:1.8; }
  .editor-prose h2 { font-weight:700; line-height:1.35; margin:1.25em 0 .6em; font-size:1.5rem; }
  @media (min-width:640px){ .editor-prose h2{ font-size:1.75rem; } }
  .editor-prose h3 { font-weight:600; line-height:1.45; margin:1.1em 0 .5em; font-size:1.25rem; }
  .editor-prose ul, .editor-prose ol { margin:.6em 0 .8em; padding-left:1.4em; }
  .editor-prose a { color:#2563eb; text-decoration:underline; }
  .editor-prose blockquote{ margin:1em 0; padding:.6em 1em; color:#374151; border-left:4px solid #e5e7eb; background:#f9fafb; border-radius:.25rem; }
  .editor-prose pre{ margin:.8em 0; padding:.75rem; border-radius:.5rem; background:#0b1220; color:#e5e7eb; overflow-x:auto; line-height:1.6; }
  .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:4px; }
  .editor-prose img{ max-width:100%; height:auto; border-radius:.25rem; }
  .toc-sub { list-style:none; padding-left:1rem; }
  .toc-sub li { position:relative; padding-left:.9rem; }
  .toc-sub li::before { content:"・"; position:absolute; left:0; top:0; line-height:1.6; }

  /* タイトル2行クランプ（Tailwindのline-clamp未使用環境用） */
  .clamp-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
</style>

<script>
  // 目次のスムーススクロール
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const t = document.querySelector(a.getAttribute('href'));
    if (!t) return;
    e.preventDefault();
    t.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  // 本文内広告（H2直後に最大n枠）
  // @if(!empty($post->show_ad_in_body) && (int)($post->ad_in_body_max ?? 0) > 0)
  (function(){
    const body = document.getElementById('postBody');
    if (!body) return;
    const tpl  = document.getElementById('ad-in-body-tpl');
    if (!tpl?.content) return;

    const max  = Math.min({{ (int)($post->ad_in_body_max ?? 0) }}, 5);
    let inserted = 0;

    // 1) H2直後に差し込み
    const h2s = Array.from(body.querySelectorAll('h2'));
    for (const h2 of h2s) {
      if (inserted >= max) break;
      const frag = document.importNode(tpl.content, true);
      h2.parentNode.insertBefore(frag, h2.nextSibling);
      inserted++;
    }

    // 2) 段落フォールバック（3段落ごと）
    if (inserted < max) {
      const ps = Array.from(body.querySelectorAll('p'));
      for (let i = 3; i <= ps.length && inserted < max; i += 3) {
        const p = ps[i - 1];
        const frag = document.importNode(tpl.content, true);
        p.parentNode.insertBefore(frag, p.nextSibling);
        inserted++;
      }
    }
  })();
  // @endif
</script>
@endsection
