{{-- resources/views/public/posts/show.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // メタ（DB優先 → 自動生成）
  $metaTitle = $post->meta_title ?: $post->title;
  $metaDesc  = $post->meta_description
    ?: ($post->lead ? Str::limit($post->lead, 120) : Str::limit(strip_tags($post->body), 160, '…'));

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

  // OGP 用は絶対URL推奨（data: の場合はロゴへフォールバック）
  $ogImage = Str::startsWith($ey, 'data:')
      ? url(asset('tools_hub_logo.png'))
      : url($ey);
@endphp

{{-- ▼ レイアウトの <head> に反映されるスロット --}}
@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@section('og_image', $ogImage)

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

        {{-- ▼ 目次（フロント生成、初期は非表示） --}}
        <nav id="postToc" class="mt-6 rounded border bg-white p-4" aria-label="目次" hidden>
          <div class="mb-2 text-sm font-semibold text-gray-700">目次</div>
          <ul class="toc-root"></ul>
        </nav>
      </header>

      @if($post->lead)
        <div class="mb-4 editor-prose text-[0.95rem] text-gray-800">
          {!! nl2br(e($post->lead)) !!}
        </div>
      @endif

      {{-- 本文 --}}
      <div id="postBody" class="editor-prose mb-8">
        {!! $post->body !!}
      </div>
    </article>

    {{-- ★ 右サイドレール：モバイル＝記事下 / PC＝右サイド --}}
    <aside class="block mt-8 lg:mt-0 lg:col-start-2 lg:row-start-1" role="complementary" aria-label="サイドコンテンツ">
      <div class="w-full mx-auto space-y-6 lg:sticky lg:top-4 lg:w-[300px]">

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

  /* 固定ヘッダー対策 */
  .editor-prose h2, .editor-prose h3 { scroll-margin-top: 80px; }

  /* --- TOC用の軽量スタイル --- */
  #postToc .toc-root { list-style: none; padding-left: 0; margin: 0; }
  #postToc .toc-root > .toc-lv2 { margin: .35rem 0; }
  #postToc .toc-root a { text-decoration: none; color: #374151; }
  #postToc .toc-root a:hover { text-decoration: underline; }
  #postToc .toc-sub { list-style: none; padding-left: 1rem; margin-top: .25rem; }
  #postToc .is-active > a { color: #2563eb; font-weight: 600; }

  /* スムーズスクロール（対応ブラウザで有効） */
  html { scroll-behavior: smooth; }
</style>


<script>
(() => {
  const body = document.getElementById('postBody');
  const toc  = document.getElementById('postToc');
  if (!body || !toc) return;

  // h2/h3 を拾う
  const headings = Array.from(body.querySelectorAll('h2, h3'));
  if (headings.length === 0) return;

  // 一意なIDを付与（日本語見出し対応・重複回避）
  const used = new Set();
  const mkId = (text, level) => {
    const base = (text || '').trim()
      .toLowerCase()
      .replace(/[^\p{Letter}\p{Number}]+/gu, '-')
      .replace(/^-+|-+$/g, '') || `h${level}-${(Math.random().toString(36).slice(2,10))}`;
    let id = base, i = 2;
    while (used.has(id) || document.getElementById(id)) id = `${base}-${i++}`;
    used.add(id);
    return id;
  };
  headings.forEach(h => { if (!h.id) h.id = mkId(h.textContent, Number(h.tagName.slice(1))); });

  // TOCを生成（h2直下にh3を入れ子）
  const root = toc.querySelector('.toc-root');
  let currentLi = null, subUl = null;
  headings.forEach((h, idx) => {
    const level = Number(h.tagName.slice(1));
    if (level === 2) {
      currentLi && root.appendChild(currentLi);
      currentLi = document.createElement('li');
      currentLi.className = 'toc-lv2';
      const a = document.createElement('a');
      a.href = `#${h.id}`;
      a.textContent = h.textContent.trim();
      currentLi.appendChild(a);

      const next = headings[idx+1];
      if (next && next.tagName === 'H3') {
        subUl = document.createElement('ul');
        subUl.className = 'toc-sub';
        currentLi.appendChild(subUl);
      } else {
        subUl = null;
      }
    } else if (level === 3) {
      if (!currentLi) {
        currentLi = document.createElement('li');
        currentLi.className = 'toc-lv2';
        root.appendChild(currentLi);
      }
      if (!subUl) {
        subUl = document.createElement('ul');
        subUl.className = 'toc-sub';
        currentLi.appendChild(subUl);
      }
      const li = document.createElement('li');
      li.className = 'toc-lv3';
      const a = document.createElement('a');
      a.href = `#${h.id}`;
      a.textContent = h.textContent.trim();
      li.appendChild(a);
      subUl.appendChild(li);

      const next = headings[idx+1];
      if (!next || next.tagName !== 'H3') {
        root.appendChild(currentLi);
        currentLi = null; subUl = null;
      }
    }
  });
  if (currentLi) root.appendChild(currentLi);

  // 目次に項目ができたら表示
  toc.hidden = root.children.length === 0;

  // ScrollSpy（現在位置の見出しに対応するリンクをハイライト）
  const links = toc.querySelectorAll('a[href^="#"]');
  const map = new Map(); // id => <li>
  links.forEach(a => { map.set(decodeURIComponent(a.getAttribute('href').slice(1)), a.closest('li')); });

  const io = new IntersectionObserver((entries) => {
    // 上に近い見出しを優先
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const id = e.target.getAttribute('id');
      toc.querySelectorAll('.is-active').forEach(el => el.classList.remove('is-active'));
      const li = map.get(id);
      if (li) {
        li.classList.add('is-active');
        // h3の場合は親のh2も強調
        const parentLi = li.closest('.toc-sub')?.closest('.toc-lv2');
        if (parentLi) parentLi.classList.add('is-active');
      }
    });
  }, { rootMargin: '0px 0px -70% 0px', threshold: [0, 1] });

  document.querySelectorAll('#postBody h2[id], #postBody h3[id]').forEach(h => io.observe(h));

  // a11y: クリック後に見出しへフォーカス
  links.forEach(a => {
    a.addEventListener('click', () => {
      const id = a.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if (target) target.setAttribute('tabindex', '-1'), target.focus({preventScroll:true});
    });
  });
})();
</script>

@endsection