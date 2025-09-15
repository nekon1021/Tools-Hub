{{-- resources/views/admin/posts/show.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;

  $metaTitle = $post->meta_title ?: $post->title;
  $metaDesc  = $post->meta_description ?: ($post->lead ? Str::limit($post->lead, 120) : null);

  // --- toc_json 正規化（配列/JSON文字列/オブジェクト/文字列配列を許容） ---
  $raw = is_array($post->toc_json)
    ? $post->toc_json
    : (is_string($post->toc_json)
        ? (json_decode($post->toc_json, true) ?: [])
        : (is_object($post->toc_json) ? (array) $post->toc_json : [])
      );

  $toc = [];
  if (is_array($raw)) {
    foreach ($raw as $item) {
      if (is_string($item)) {
        $toc[] = [
          'id'    => Str::slug($item) ?: ('h2-' . (count($toc) + 1)),
          'text'  => $item,
          'level' => 2,
        ];
        continue;
      }
      if (is_object($item)) $item = (array) $item;

      if (is_array($item)) {
        $text  = (string) ($item['text'] ?? $item['title'] ?? '');
        $level = $item['level'] ?? $item['depth'] ?? 2;
        if (is_string($level)) $level = strtolower($level) === 'h3' ? 3 : 2;
        $level = (int) $level === 3 ? 3 : 2;

        $id = (string) ($item['id'] ?? '');
        if ($id === '') $id = Str::slug($text) ?: ('h' . $level . '-' . (count($toc) + 1));

        if ($text !== '') $toc[] = ['id' => $id, 'text' => $text, 'level' => $level];
      }
    }
  }

  // --- アイキャッチURL 決定（空文字除外・フルURL/相対パス両対応・二重storage対策） ---
  $ey = null;

  if (filled($post->eyecatch_url)) {
    $ey = $post->eyecatch_url;
  } elseif (filled($post->thumbnail_url)) {
    $ey = $post->thumbnail_url;
  } elseif (filled($post->og_image_path)) {
    $p = ltrim((string) $post->og_image_path, '/');

    if (Str::startsWith($p, ['http://','https://'])) {
      // 既にフルURL
      $ey = $p;
    } else {
      // "storage/..." が保存されている旧データを考慮して二重にならないように
      if (Str::startsWith($p, 'storage/')) {
        $p = Str::after($p, 'storage/');
      }
      $ey = \Illuminate\Support\Facades\Storage::disk('public')->url($p);
    }
  }
@endphp

@section('title', '記事詳細（管理）｜' . config('app.name'))

@section('content')
<div class="container mx-auto max-w-5xl px-4 py-6">
  <div class="mb-6 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">記事詳細（管理）</h1>

    <div class="flex items-center gap-2">
      @if(!$post->is_published)
        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-sm">下書き</span>
      @elseif($post->published_at && $post->published_at->gt(now()))
        <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 text-sm">
          公開予定 {{ $post->published_at->format('Y-m-d H:i') }}
        </span>
      @else
        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-sm">公開中</span>
      @endif
    </div>

    <div class="ml-auto flex flex-wrap gap-2">
      <a href="{{ route('admin.posts.edit', $post) }}" class="px-3 py-1.5 border rounded">編集</a>
      @if($post->is_published && $post->slug)
        <a href="{{ route('public.posts.show', $post->slug) }}" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 border rounded">
          公開ページを見る
        </a>
      @endif
      <a href="{{ route('admin.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
    </div>
  </div>

  @if(!$post->is_published)
    <div class="mb-4 rounded border border-gray-300 bg-gray-50 px-4 py-2 text-gray-700">
      下書き状態のプレビューです（一般公開されません）。
    </div>
  @elseif($post->published_at && $post->published_at->gt(now()))
    <div class="mb-4 rounded border border-yellow-300 bg-yellow-50 px-4 py-2 text-yellow-900">
      予約投稿のプレビューです。公開予定: {{ $post->published_at->format('Y-m-d H:i') }}
    </div>
  @endif

  <article class="mx-auto max-w-3xl">
    <header class="mb-6">
      <h2 class="text-3xl font-bold mb-2">{{ $post->title }}</h2>
      <div class="text-sm text-gray-500 space-x-2">
        <span>スラッグ:
          @if($post->slug)
            <code>/posts/{{ $post->slug }}</code>
          @else
            <code>（未設定）</code>
          @endif
        </span>
        @if($post->published_at)<span>｜公開日時: {{ $post->published_at->format('Y-m-d H:i') }}</span>@endif
        @if($post->category)<span>｜カテゴリ: {{ $post->category->name }}</span>@endif
        @if($post->user)<span>｜作成者: {{ $post->user->name }}</span>@endif
      </div>
    </header>

    @if($ey)
      <figure class="mb-4">
        <img src="{{ $ey }}" alt="{{ $post->title }}" class="w-full aspect-[16/9] object-cover rounded" width="1200" height="675" loading="lazy">
      </figure>
    @endif

    @if($post->lead)
      <div class="mb-4 editor-prose text-[0.95rem] text-gray-800">
        {!! nl2br(e($post->lead)) !!}
      </div>
    @endif

    @if($post->show_ad_under_lead)
      @includeIf('partials.ads.under-lead')
    @endif

    {{-- 目次（H2配下にH3をネスト） --}}
    @if(!empty($toc))
      <nav id="tocNav" class="mb-6 rounded border bg-gray-50 p-4 text-sm">
        <h3 class="font-semibold mb-2">目次</h3>
        <ol class="list-decimal pl-5 space-y-1">
          @php $open = false; @endphp
          @foreach($toc as $it)
            @php
              $id    = $it['id'] ?? '';
              $text  = $it['text'] ?? '';
              $level = (int) ($it['level'] ?? 2);
            @endphp

            @if($level === 2)
              @if($open)</ul></li>@php $open=false; @endphp @endif
              <li>
                <a class="underline" href="#{{ $id }}">{{ $text }}</a>
                @php $open=true; @endphp
                <ul class="toc-sub pl-5 mt-1 space-y-1">
            @elseif($level === 3)
                @if(!$open)
                  {{-- H3が先行する場合のフォールバック --}}
                  <li><span class="text-gray-400">（小見出し）</span><ul class="toc-sub pl-5 mt-1 space-y-1">@php $open=true; @endphp
                @endif
                <li><a class="underline" href="#{{ $id }}">{{ $text }}</a></li>
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

    {{-- 本文中広告（H2直後に最大n枠） --}}
    @if($post->show_ad_in_body)
      <template id="ad-in-body-tpl">
        @includeIf('partials.ads.in-body', ['max' => 1])
      </template>
    @endif

    @if($post->show_ad_below)
      @includeIf('partials.ads.below')
    @endif
  </article>
</div>

<style>
  /* スムーズスクロール & 見出しの頭の隠れ対策 */
  html { scroll-behavior: smooth; }
  :root { --header-offset: 80px; }
  .editor-prose h1,
  .editor-prose h2,
  .editor-prose h3,
  .editor-prose h4,
  .editor-prose h5,
  .editor-prose h6 { scroll-margin-top: var(--header-offset); }

  .editor-prose { color:#111827; }
  .editor-prose p { margin:.7em 0; line-height:1.8; }
  .editor-prose h2 { font-weight:700; line-height:1.35; margin:1.25em 0 .6em; font-size:1.5rem; }
  @media (min-width:640px){ .editor-prose h2{ font-size:1.75rem; } }
  .editor-prose h3 { font-weight:600; line-height:1.45; margin:1.1em 0 .5em; font-size:1.25rem; }
  .editor-prose ul, .editor-prose ol { margin:.6em 0 .8em; padding-left:1.4em; }
  .editor-prose ul { list-style:disc; }
  .editor-prose ol { list-style:decimal; }
  .editor-prose li { margin:.25em 0; }
  .editor-prose a { color:#2563eb; text-decoration:underline; }
  .editor-prose blockquote{ margin:1em 0; padding:.6em 1em; color:#374151; border-left:4px solid #e5e7eb; background:#f9fafb; border-radius:.25rem; }
  .editor-prose pre{ margin:.8em 0; padding:.75rem; border-radius:.5rem; background:#0b1220; color:#e5e7eb; overflow-x:auto; line-height:1.6; }
  .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:.4rem; }
  .editor-prose img{ max-width:100%; height:auto; border-radius:.25rem; }

  .toc-sub { list-style:none; padding-left:1rem; }
  .toc-sub li { position:relative; padding-left:.9rem; }
  .toc-sub li::before { content:"・"; position:absolute; left:0; top:0; line-height:1.6; }
</style>

<script>
  // 固定ヘッダー分のオフセット（可変ヘッダーに対応）
  function getHeaderOffset() {
    const header = document.querySelector('.site-header, .app-header, header[role="banner"]');
    if (!header) {
      const cssVar = getComputedStyle(document.documentElement).getPropertyValue('--header-offset').trim();
      return Number(cssVar.replace('px','')) || 80;
    }
    return header.offsetHeight || 80;
  }

  // 目次のリンクはオフセット付きスムーズスクロール
  const tocNav = document.getElementById('tocNav');
  tocNav?.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;

    const hash = a.getAttribute('href');
    const target = document.querySelector(hash);
    if (!target) return;

    e.preventDefault();

    const offset = getHeaderOffset();
    const y = target.getBoundingClientRect().top + window.pageYOffset - offset;

    window.scrollTo({ top: y, behavior: 'smooth' });
    history.pushState(null, '', hash);

    target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
  });

  // #直リンク時の初期位置補正
  window.addEventListener('load', () => {
    if (location.hash) {
      const target = document.querySelector(location.hash);
      if (target) {
        const offset = getHeaderOffset();
        const y = target.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: y });
      }
    }
  });

  // 本文中広告（H2直後に最大n枠）
  @if($post->show_ad_in_body && ($post->ad_in_body_max ?? 0) > 0)
  (function(){
    const body = document.getElementById('postBody');
    if (!body) return;

    const h2s  = Array.from(body.querySelectorAll('h2'));
    const max  = Math.min({{ (int)($post->ad_in_body_max ?? 0) }}, 5);
    const tpl  = document.getElementById('ad-in-body-tpl');
    let inserted = 0;

    for (const h2 of h2s) {
      if (inserted >= max) break;
      if (!tpl?.content) break;
      const ad = document.importNode(tpl.content, true);
      if (h2.nextSibling) h2.parentNode.insertBefore(ad, h2.nextSibling);
      else h2.parentNode.appendChild(ad);
      inserted++;
    }
  })();
  @endif
</script>
@endsection
