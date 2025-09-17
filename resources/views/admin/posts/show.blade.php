{{-- resources/views/admin/posts/show.blade.php --}}
@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  $metaTitle = $post->meta_title ?: $post->title;
  $metaDesc  = $post->meta_description ?: ($post->lead ? Str::limit($post->lead, 120) : null);

  // --- toc_json 正規化 ---
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
        $toc[] = ['id' => Str::slug($item) ?: ('h2-'.(count($toc)+1)), 'text' => $item, 'level' => 2];
        continue;
      }
      if (is_object($item)) $item = (array) $item;

      if (is_array($item)) {
        $text  = (string)($item['text'] ?? $item['title'] ?? '');
        $level = $item['level'] ?? $item['depth'] ?? 2;
        if (is_string($level)) $level = strtolower($level)==='h3' ? 3 : 2;
        $level = (int)$level===3 ? 3 : 2;

        $id = (string)($item['id'] ?? '');
        if ($id==='') $id = Str::slug($text) ?: ('h'.$level.'-'.(count($toc)+1));

        if ($text!=='') $toc[] = ['id'=>$id,'text'=>$text,'level'=>$level];
      }
    }
  }

  // --- H2ごとにH3をグルーピング（編集/作成プレビュー準拠：先行H3は捨てる） ---
  $tocGroups = [];
  $currentIndex = -1;
  foreach ($toc as $row) {
    $lvl = (int)($row['level'] ?? 2);
    if ($lvl === 2) {
      $tocGroups[] = ['id'=>$row['id'], 'text'=>$row['text'], 'children'=>[]];
      $currentIndex = count($tocGroups) - 1;
    } elseif ($lvl === 3) {
      if ($currentIndex === -1) {
        // 先行H3は表示しない（編集プレビューと同挙動）
        continue;
      }
      $tocGroups[$currentIndex]['children'][] = ['id'=>$row['id'], 'text'=>$row['text']];
    }
  }

  // --- アイキャッチURL（安全に正規化） ---
$toPublicUrl = function ($v) {
  if ($v === null) return null;

  // 文字列化＋トリム＋区切り統一（Windows対策）
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = str_replace('\\', '/', $v);

  // 既にURLならそのまま
  if (Str::startsWith($v, ['http://','https://','//'])) {
    return $v;
  }

  // 既に /storage で配信可能なパス
  if (Str::startsWith($v, ['/storage/'])) {
    return $v;
  }
  if (Str::startsWith($v, ['storage/'])) {
    return '/storage/' . ltrim(Str::after($v, 'storage/'), '/');
  }

  // サーバの絶対パス → /storage へ救済
  if (Str::startsWith($v, ['/'])) {
    if (Str::contains($v, '/public/storage/')) {
      return '/storage/' . ltrim(Str::after($v, '/public/storage/'), '/');
    }
    if (Str::contains($v, '/storage/app/public/')) {
      return '/storage/' . ltrim(Str::after($v, '/storage/app/public/'), '/');
    }
    if (Str::contains($v, '/app/public/')) {
      return '/storage/' . ltrim(Str::after($v, '/app/public/'), '/');
    }
    // それ以外の絶対パスは不可
    return null;
  }

  // 相対キーは publicディスク前提でURL化
  // ※ ここは「常に public」を明示（controller で public に保存しているため）
  return Storage::disk('public')->url(ltrim($v, '/'));
};

    // 既存のアイキャッチURL（優先順）
    $ey = $toPublicUrl($post->eyecatch_url)
   ?: $toPublicUrl($post->thumbnail_url)
   ?: (function() use ($post, $toPublicUrl) {
        $raw = $post->og_image_path;
        if (!$raw) return null;

        // 直URLならそのまま
        $v = trim((string)$raw);
        if (Str::startsWith($v, ['http://','https://','//'])) return $v;

        // よくある接頭辞を剥がす → publicディスクでURL化
        $p = ltrim($v, '/');
        $p = Str::after($p, 'storage/app/public/');
        $p = Str::after($p, 'app/public/');
        $p = Str::after($p, 'public/');
        $p = Str::after($p, 'storage/');
        $p = trim($p, '/');
        if ($p === '') return null;

        return Storage::disk('public')->url($p);
      })();

@endphp

@section('title', '記事詳細（管理）｜' . config('app.name'))

@section('content')
<section class="mx-auto max-w-screen-xl px-4 pt-6 sm:pt-10">
  <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
    <nav aria-label="Breadcrumb">
      <ol class="flex flex-wrap items-center gap-1">
        <li><a href="{{ route('admin.posts.index') }}" class="hover:underline">記事一覧</a></li>
        <li aria-hidden="true">/</li>
        <li class="text-gray-400">記事詳細</li>
      </ol>
    </nav>

    <div class="ml-auto flex flex-wrap gap-2">
      <a href="{{ route('admin.posts.edit', $post) }}" class="px-3 py-1.5 border rounded">編集</a>

      @if($post->slug /* && 公開条件をここで出し分けたいなら併記 */)
        <a href="{{ route('public.posts.show', ['slug' => $post->slug]) }}" target="_blank" rel="noopener">
          公開ページ
        </a>
      @endif

      <a href="{{ route('admin.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
    </div>
  </div>

  <header class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
      <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">記事詳細（管理）</h1>
    </div>

    {{-- Eyecatch --}}
    <aside class="lg:col-span-1">
      @if($ey)
        <div class="rounded-xl border bg-white p-3 shadow-sm">
          <figure class="aspect-[16/9] w-full overflow-hidden rounded-lg bg-gray-100">
            @php
              $phEy = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675"></svg>');
            @endphp
            
            <img src="{{ $ey }}" alt="{{ $post->title }}"
                class="h-full w-full object-cover"
                width="1200" height="675"
                loading="lazy" decoding="async"
                onerror="this.onerror=null; this.src='{{ $phEy }}';">
          </figure>
        </div>
      @endif
    </aside>
  </header>
</section>

{{-- ステータス注意文 --}}
@if(!$post->is_published)
  <div class="mx-auto max-w-screen-xl px-4 mt-6">
    <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-gray-700">下書きプレビュー（一般公開されません）</div>
  </div>
@elseif($post->published_at && $post->published_at->gt(now()))
  <div class="mx-auto max-w-screen-xl px-4 mt-6">
    <div class="rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-900">
      予約投稿のプレビューです。公開予定: {{ $post->published_at->format('Y-m-d H:i') }}
    </div>
  </div>
@endif

{{-- 3カラム：左=シェア, 中央=本文, 右=目次 --}}
<div class="mx-auto max-w-screen-xl px-4 mt-6">
  <div class="grid gap-6 lg:grid-cols-[56px_minmax(0,1fr)_320px]">
    {{-- 左：シェアUI --}}
    <aside class="hidden lg:block">
      <div class="sticky top-24 flex flex-col items-center gap-2 text-gray-500">
        <button type="button" title="リンクをコピー" class="rounded-full border p-2 hover:bg-gray-50"
          onclick="navigator.clipboard.writeText(location.href).then(()=>alert('リンクをコピーしました'));">
          <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3.9 12a4.6 4.6 0 0 1 4.6-4.6h3v2.3h-3a2.3 2.3 0 1 0 0 4.6h3v2.3h-3A4.6 4.6 0 0 1 3.9 12Zm6.1 1.1h4V11h-4v2.1Zm5.4-5.7h-3V5.1h3A4.6 4.6 0 0 1 22 9.7a4.6 4.6 0 0 1-4.6 4.6h-3v-2.3h3a2.3 2.3 0 1 0 0-4.6Z"/></svg>
        </button>
      </div>
    </aside>

    {{-- 中央：本文 --}}
    <main class="min-w-0">
      <article class="mx-auto max-w-3xl">
        <header class="mb-6">
          <h2 class="text-3xl font-bold mb-2">{{ $post->title }}</h2>
          <div class="text-sm text-gray-500 space-x-2">
            <span>スラッグ: {{ $post->slug ? "/posts/{$post->slug}" : '（未設定）' }}</span>
            @if($post->published_at)<span>｜公開日時: {{ $post->published_at->format('Y-m-d H:i') }}</span>@endif
            @if($post->category)<span>｜カテゴリ: {{ $post->category->name }}</span>@endif
            @if($post->user)<span>｜作成者: {{ $post->user->name }}</span>@endif
          </div>
        </header>

        @if($post->lead)
          <div class="mb-4 editor-prose text-[0.95rem] text-gray-800">
            {!! nl2br(e($post->lead)) !!}
          </div>
        @endif

        @if($post->show_ad_under_lead)
          @includeIf('partials.ads.under-lead')
        @endif

        <div id="postBody" class="editor-prose mb-8">
          {!! $post->body !!}
        </div>

        @if($post->show_ad_in_body)
          <template id="ad-in-body-tpl">
            @includeIf('partials.ads.in-body', ['max' => 1])
          </template>
        @endif

        @if($post->show_ad_below)
          @includeIf('partials.ads.below')
        @endif
      </article>
    </main>

    {{-- 右：目次カード（編集/作成と同じUI） --}}
    <aside>
      <div class="lg:sticky lg:top-24 space-y-4">
        @if(!empty($tocGroups))
          <section class="rounded-xl border bg-white p-5 sm:p-6" role="navigation" aria-labelledby="tocHeading">
            <h2 id="tocHeading" class="font-semibold mb-3">目次</h2>
            <nav id="tocNav" class="text-sm text-gray-700">
              <ol class="list-decimal pl-5 space-y-1">
                @foreach($tocGroups as $g)
                  <li>
                    @if(!empty($g['id']) && !empty($g['text']))
                      <a class="underline toc-link" href="#{{ $g['id'] }}">{{ $g['text'] }}</a>
                    @else
                      <span class="text-gray-400">（見出し）</span>
                    @endif

                    @if(!empty($g['children']))
                      <ul class="pl-5 mt-1 space-y-1">
                        @foreach($g['children'] as $c)
                          <li><a class="underline toc-link" href="#{{ $c['id'] }}">{{ $c['text'] }}</a></li>
                        @endforeach
                      </ul>
                    @endif
                  </li>
                @endforeach
              </ol>
            </nav>
          </section>
        @endif
      </div>
    </aside>
  </div>
</div>

<style>
  html { scroll-behavior: smooth; }
  :root { --header-offset: 80px; }
  .editor-prose h1,.editor-prose h2,.editor-prose h3,.editor-prose h4,.editor-prose h5,.editor-prose h6 { scroll-margin-top: var(--header-offset); }

  .editor-prose { color:#111827; }
  .editor-prose p { margin:.7em 0; line-height:1.8; }
  .editor-prose h2 { font-weight:700; line-height:1.35; margin:1.25em 0 .6em; font-size:1.5rem; }
  @media (min-width:640px){ .editor-prose h2{ font-size:1.75rem; } }
  .editor-prose h3 { font-weight:600; line-height:1.45; margin:1.1em 0 .5em; font-size:1.25rem; }
  .editor-prose a { color:#2563eb; text-decoration:underline; }
  #tocNav a[aria-current="true"]{ color:#111827; font-weight:600; text-decoration:underline; }
</style>

<script>
  // ヘッダー分のオフセット
  function getHeaderOffset(){
    const header = document.querySelector('.site-header, .app-header, header[role="banner"]');
    if (!header) {
      const cssVar = getComputedStyle(document.documentElement).getPropertyValue('--header-offset').trim();
      return Number(cssVar.replace('px','')) || 80;
    }
    return header.offsetHeight || 80;
  }
  function applyHeaderOffsetVar(){
    document.documentElement.style.setProperty('--header-offset', getHeaderOffset() + 'px');
  }
  applyHeaderOffsetVar();
  window.addEventListener('resize', applyHeaderOffsetVar);

  // 目次リンク：オフセット付きスクロール
  const tocNav = document.getElementById('tocNav');
  tocNav?.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]'); if (!a) return;
    const hash = a.getAttribute('href');
    const target = document.querySelector(hash); if (!target) return;
    e.preventDefault();
    const y = target.getBoundingClientRect().top + window.pageYOffset - getHeaderOffset();
    window.scrollTo({ top: y, behavior: 'smooth' });
    history.pushState(null, '', hash);
    target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
  });

  // #直リンク時の補正
  window.addEventListener('load', () => {
    if (location.hash) {
      const target = document.querySelector(location.hash);
      if (target) {
        const y = target.getBoundingClientRect().top + window.pageYOffset - getHeaderOffset();
        window.scrollTo({ top: y });
      }
    }
  });

  // 現在位置ハイライト（IntersectionObserver）
  (function observeActiveHeading(){
    const links = Array.from(document.querySelectorAll('#tocNav a[href^="#"]'));
    if(!links.length) return;
    const map = new Map();
    links.forEach(a => { const id = a.getAttribute('href').slice(1); const h = document.getElementById(id); if (h) map.set(h, a); });

    let active;
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (!en.isIntersecting) return;
        active?.setAttribute('aria-current', 'false');
        const link = map.get(en.target);
        if (link) { link.setAttribute('aria-current', 'true'); active = link; }
      });
    }, { rootMargin: `-${getHeaderOffset()+8}px 0px -70% 0px`, threshold: [0,1] });

    map.forEach((_, h) => io.observe(h));
  })();

  // 本文中広告（H2直後に最大n枠）
  @if($post->show_ad_in_body && ($post->ad_in_body_max ?? 0) > 0)
  (function(){
    const body = document.getElementById('postBody'); if (!body) return;
    const h2s  = Array.from(body.querySelectorAll('h2'));
    const max  = Math.min({{ (int)($post->ad_in_body_max ?? 0) }}, 5);
    const tpl  = document.getElementById('ad-in-body-tpl'); let inserted = 0;
    for (const h2 of h2s) {
      if (inserted >= max || !tpl?.content) break;
      const ad = document.importNode(tpl.content, true);
      if (h2.nextSibling) h2.parentNode.insertBefore(ad, h2.nextSibling);
      else h2.parentNode.appendChild(ad);
      inserted++;
    }
  })();
  @endif

  (function enhanceBodyImages(){
    const imgs = document.querySelectorAll('#postBody img');
    imgs.forEach(img => {
      if (!img.hasAttribute('loading'))  img.setAttribute('loading','lazy');
      if (!img.hasAttribute('decoding')) img.setAttribute('decoding','async');
    });
  })();
</script>
@endsection
