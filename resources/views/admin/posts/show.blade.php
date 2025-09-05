@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  $metaTitle = $post->meta_title ?: $post->title;
  $metaDesc  = $post->meta_description ?: ($post->lead ? Str::limit($post->lead, 120) : null);
  $toc = is_array($post->toc_json) ? $post->toc_json : (json_decode($post->toc_json ?? '[]', true) ?: []);
@endphp

@section('title', '記事詳細（管理）｜' . config('app.name'))

@section('content')
<div class="container mx-auto max-w-5xl px-4 py-6">
  <div class="mb-6 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">記事詳細（管理）</h1>
    <div class="flex items-center gap-2">
      @if($isDraft)
        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-sm">下書き</span>
      @elseif($isScheduled)
        <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 text-sm">
          公開予定 {{ $post->published_at?->format('Y-m-d H:i') }}
        </span>
      @else
        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-sm">公開中</span>
      @endif
    </div>

    <div class="ml-auto flex flex-wrap gap-2">
      <a href="{{ route('admin.posts.edit', $post) }}" class="px-3 py-1.5 border rounded">編集</a>
      @if(!$isDraft)
        <a href="{{ route('public.posts.show', $post->slug) }}" target="_blank" class="px-3 py-1.5 border rounded">
          公開ページを見る
        </a>
      @endif
      <a href="{{ route('admin.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
    </div>
  </div>

  @if($isDraft)
    <div class="mb-4 rounded border border-gray-300 bg-gray-50 px-4 py-2 text-gray-700">
      下書き状態のプレビューです（一般公開されません）。
    </div>
  @elseif($isScheduled)
    <div class="mb-4 rounded border border-yellow-300 bg-yellow-50 px-4 py-2 text-yellow-900">
      予約投稿のプレビューです。公開予定: {{ $post->published_at?->format('Y-m-d H:i') }}
    </div>
  @endif

  <article class="mx-auto max-w-3xl">
    <header class="mb-6">
      <h2 class="text-3xl font-bold mb-2">{{ $post->title }}</h2>
      <div class="text-sm text-gray-500 space-x-2">
        <span>スラッグ: <code>/posts/{{ $post->slug }}</code></span>
        @if($post->published_at)<span>｜公開日時: {{ $post->published_at->format('Y-m-d H:i') }}</span>@endif
        @if($post->category)<span>｜カテゴリ: {{ $post->category->name }}</span>@endif
        @if($post->user)<span>｜作成者: {{ $post->user->name }}</span>@endif
      </div>
    </header>

    @php
      $ey = $post->eyecatch_url
        ?? ($post->thumbnail_url ?? null)
        ?? (!empty($post->og_image_path) ? \Illuminate\Support\Facades\Storage::url($post->og_image_path) : null);
    @endphp
    @if($ey)
      <figure class="mb-4">
        <img src="{{ $ey }}" alt="{{ $post->title }}" class="w-full aspect-[16/9] object-cover rounded">
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

    @if(!empty($toc))
      <nav class="mb-6 rounded border bg-gray-50 p-4 text-sm">
        <h3 class="font-semibold mb-2">目次</h3>
        <ol class="list-decimal pl-5 space-y-1">
          @php $open=false; @endphp
          @foreach($toc as $it)
            @if(($it['level'] ?? $it['depth'] ?? 2)==2)
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
</div>

<style>
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
  .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:4px; }
  .editor-prose img{ max-width:100%; height:auto; border-radius:.25rem; }
  .toc-sub { list-style:none; padding-left:1rem; }
  .toc-sub li { position:relative; padding-left:.9rem; }
  .toc-sub li::before { content:"・"; position:absolute; left:0; top:0; line-height:1.6; }
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
  @if($post->show_ad_in_body && ($post->ad_in_body_max ?? 0) > 0)
  (function(){
    const body = document.getElementById('postBody');
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
