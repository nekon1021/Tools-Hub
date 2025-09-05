{{-- resources/views/admin/posts/index.blade.php --}}
@extends('layouts.app')

@section('title', '記事管理｜' . config('app.name'))

@section('content')
@php
  // ルート名の接頭辞（admin. があれば採用）
  $prefix  = \Illuminate\Support\Facades\Route::has('admin.posts.index') ? 'admin.' : '';
  $rIndex   = $prefix.'posts.index';
  $rCreate  = $prefix.'posts.create';
  $rEdit    = $prefix.'posts.edit';
  $rDestroy = $prefix.'posts.destroy';
  $rBulk    = \Illuminate\Support\Facades\Route::has($prefix.'posts.bulk') ? $prefix.'posts.bulk' : null;
  $rShow = $prefix.'posts.show';
  $rPreview = \Illuminate\Support\Facades\Route::has($prefix. 'posts.preview') ? $prefix.'posts.preview' : null;
  $status  = request('status','all');
  $counts  = $counts ?? ['all'=>0,'published'=>0,'draft'=>0,'trashed'=>0];

  // ソートURL（同じ列なら昇降トグル）
  $mkSort = function (string $key) {
    $current = request('sort','created_at'); $dir = request('dir','desc');
    $newDir  = ($current === $key && $dir === 'desc') ? 'asc' : 'desc';
    return request()->fullUrlWithQuery(['sort'=>$key,'dir'=>$newDir,'page'=>1]);
  };

  $hasPublicShow = \Illuminate\Support\Facades\Route::has('public.posts.show');
  $ph1x = 'https://placehold.jp/80x45.png';
  $ph2x = 'https://placehold.jp/160x90.png';
@endphp

{{-- ヘッダー --}}
<div class="mb-6 flex flex-wrap items-center gap-3">
  <h1 class="text-2xl font-bold">記事管理</h1>
  <span class="text-sm text-gray-500">全 {{ $posts->total() }} 件</span>
  <div class="ml-auto">
    <a href="{{ route($rCreate) }}" class="inline-flex items-center px-3 py-2 rounded bg-blue-600 text-white">
      新規作成
    </a>
  </div>
</div>

{{-- タブ --}}
@php
  $tabs = [
    ['key'=>'all', 'label'=>'すべて'],
    ['key'=>'published', 'label'=>'公開'],
    ['key'=>'draft', 'label'=>'下書き'],
    ['key'=>'trashed', 'label'=>'ゴミ箱'],
  ];
@endphp
<nav class="mb-4 flex flex-wrap gap-2" aria-label="ステータス">
  @foreach($tabs as $t)
    @php
      $key = $t['key']; $label = $t['label'];
      $cnt = $counts[$key] ?? 0;
      $active = $status === $key;
      $url = request()->fullUrlWithQuery(['status'=>$key,'page'=>1]);
    @endphp
    <a href="{{ $url }}"
       class="px-3 py-1 rounded border {{ $active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700' }}">
      {{ $label }} ({{ $cnt }})
    </a>
  @endforeach
</nav>

{{-- 検索（折りたたみ） --}}
<details class="mb-4 rounded border border-gray-200 bg-white">
  <summary class="cursor-pointer select-none px-4 py-2 text-sm text-gray-700">検索・絞り込み</summary>
  <form method="GET" class="px-4 pb-4 pt-2 grid gap-2 sm:grid-cols-6 items-end">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="sm:col-span-2">
      <label class="block text-sm text-gray-600" for="q">キーワード</label>
      <input id="q" name="q" value="{{ request('q') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="タイトル/本文">
    </div>
    <div>
      <label class="block text-sm text-gray-600" for="from">公開日 From</label>
      <input id="from" type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="block text-sm text-gray-600" for="to">公開日 To</label>
      <input id="to" type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="block text-sm text-gray-600" for="per_page">表示件数</label>
      <select id="per_page" name="per_page" class="mt-1 w-full border rounded px-3 py-2">
        @foreach([10,15,20,50,100] as $n)
          <option value="{{ $n }}" {{ (int)request('per_page',15)===$n?'selected':'' }}>{{ $n }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex gap-2">
      <button class="px-3 py-2 border rounded">検索</button>
      <a href="{{ route($rIndex, ['status'=>$status]) }}" class="px-3 py-2 border rounded">クリア</a>
    </div>
  </form>
</details>

{{-- 一括操作（必要時のみ表示） --}}
@if($rBulk)
  <form id="bulk-form" method="POST" action="{{ route($rBulk) }}" class="mb-3 flex items-center gap-2">
    @csrf
    <input type="hidden" name="status" value="{{ $status }}">
    <select name="action" class="border rounded px-2 py-1 text-sm">
      <option value="">一括操作を選択</option>
      @if($status === 'trashed')
        <option value="restore">復元</option>
        <option value="force-delete">完全に削除</option>
      @else
        <option value="delete">削除</option>
      @endif
    </select>
    <button type="submit" class="px-3 py-1 border rounded text-sm">実行</button>
    <span class="text-xs text-gray-500">チェックした項目に適用</span>
  </form>
@endif

{{-- 一覧 --}}
<div class="overflow-x-auto rounded border border-gray-200 bg-white">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 sticky top-0">
      <tr class="text-left">
        <th class="px-3 py-2 w-10"><input type="checkbox" id="check-all" aria-label="全選択"></th>
        <th class="px-3 py-2 w-20">画像</th>
        <th class="px-4 py-2 w-[40%]"><a class="underline" href="{{ $mkSort('title') }}">タイトル</a></th>
        <th class="px-4 py-2">ステータス</th>
        <th class="px-4 py-2"><a class="underline" href="{{ $mkSort('published_at') }}">公開日</a></th>
        <th class="px-4 py-2"><a class="underline" href="{{ $mkSort('created_at') }}">作成日</a></th>
        <th class="px-4 py-2">作成者</th>
        <th class="px-4 py-2 text-right">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse ($posts as $p)
        @php
          $base = $p->eyecatch_url
            ?? $p->thumbnail_url
            ?? (isset($p->cover_path) ? \Illuminate\Support\Facades\Storage::url($p->cover_path) : null);
          $img1x = $p->thumb_80x45_url ?? ($base ?: $ph1x);
          $img2x = $p->thumb_160x90_url ?? ($base ?: $ph2x);
        @endphp
        <tr class="{{ $p->deleted_at ? 'opacity-70' : '' }}">
          <td class="px-3 py-2">
            <input type="checkbox" name="ids[]" form="bulk-form" value="{{ $p->id }}" aria-label="選択: {{ $p->title }}">
          </td>

          <td class="px-3 py-2">
            <div class="w-20">
              <div class="aspect-[16/9] overflow-hidden rounded border bg-gray-50">
                <img
                  src="{{ $img1x }}"
                  srcset="{{ $img1x }} 80w, {{ $img2x }} 160w"
                  sizes="80px"
                  width="160" height="90"
                  alt="{{ $p->title }} のサムネイル"
                  class="w-full h-full object-cover"
                  loading="lazy" decoding="async"
                >
              </div>
            </div>
          </td>

          <td class="px-4 py-2">
            @php
              $detailUrl = ($p->deleted_at && $rPreview)
                ? route($rPreview, $p)   // ゴミ箱はプレビューへ
                : route($rShow, $p);     // 通常は詳細へ
            @endphp

            <div class="font-medium">
              <a href="{{ $detailUrl }}" class="hover:underline">
                {{ $p->title }}
              </a>
            </div>
            <div class="text-xs text-gray-500 break-all">/posts/{{ $p->slug }}</div>

            <div class="mt-1 text-xs">
              @if($hasPublicShow && $p->is_published && !$p->deleted_at)
                <a href="{{ route('public.posts.show', $p->slug) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                  公開ページ
                  <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
              @else
                <span class="text-gray-400">公開ページなし</span>
              @endif
            </div>
          </td>

          <td class="px-4 py-2">
            @if($p->deleted_at)
              <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">削除済み</span>
            @elseif($p->is_published)
              <span class="rounded bg-green-50 px-2 py-0.5 text-green-700">公開</span>
            @else
              <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">下書き</span>
            @endif
          </td>
          <td class="px-4 py-2">{{ $p->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
          <td class="px-4 py-2">{{ $p->created_at->format('Y-m-d H:i') }}</td>
          <td class="px-4 py-2">{{ $p->user->name ?? '—' }}</td>
          <td class="px-4 py-2 text-right">
            @php
              $detailUrl = ($p->deleted_at && $rPreview)
                ? route($rPreview, $p)
                : route($rShow, $p);
            @endphp
            
            <a href="{{ $detailUrl }}" class="px-3 py-1 border rounded">詳細</a>

            @if($p->deleted_at)
              {{-- ゴミ箱内：削除ボタンは非表示。復元のみ --}}
              <form method="POST" action="{{ route($rBulk) }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="ids[]" value="{{ $p->id }}">
                <button class="px-3 py-1 border rounded">復元</button>
              </form>
              {{-- ※ 完全削除を使う場合は上部の「一括操作」で実行させる運用にすると安全です --}}
            @else
              {{-- 通常：編集＋削除（＝ゴミ箱へ移動） --}}
              <a href="{{ route($rEdit, $p) }}" class="px-3 py-1 border rounded">編集</a>
              <form method="POST" action="{{ route($rDestroy, $p) }}" class="inline"
                    onsubmit="return confirm('削除しますか？（ゴミ箱に移動）');">
                @csrf @method('DELETE')
                <button class="px-3 py-1 border rounded text-red-600">削除</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="px-4 py-10 text-center text-gray-500">
            対象のデータがありません。
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- ページング（検索条件保持） --}}
<div class="mt-4 flex items-center justify-between">
  <span class="text-sm text-gray-600">全 {{ $posts->total() }} 件中 {{ $posts->firstItem() }}–{{ $posts->lastItem() }}</span>
  {{ $posts->withQueryString()->links() }}
</div>

{{-- 一括チェック --}}
<script>
  (function () {
    const all = document.getElementById('check-all');
    if (!all) return;
    all.addEventListener('change', () => {
      document.querySelectorAll('input[name="ids[]"]').forEach(ch => { ch.checked = all.checked; });
    });
  })();
</script>
@endsection
