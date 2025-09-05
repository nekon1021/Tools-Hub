{{-- resources/views/admin/posts/edit.blade.php --}}
@extends('layouts.app')

@section('title', '記事編集｜' . config('app.name'))

@php
  use Illuminate\Support\Facades\Storage;

  // datetime-local 用（例: 2025-09-04T13:45）
  $publishedLocal = old('published_at', optional($post->published_at)->format('Y-m-d\TH:i'));

  // 管理画面内の画像アップロードエンドポイント（任意）
  $uploadUrl = \Illuminate\Support\Facades\Route::has('admin.editor.upload')
    ? route('admin.editor.upload')
    : null;

  // 既存のアイキャッチURL
  $ey = $post->eyecatch_url
      ?? ($post->thumbnail_url ?? null)
      ?? (!empty($post->og_image_path) ? Storage::disk('public')->url($post->og_image_path) : null);
@endphp

@section('content')
<div class="container mx-auto max-w-5xl px-4 py-6">

  <div class="mb-6 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">記事編集</h1>

    <div class="ml-auto flex flex-wrap gap-2">
      <a href="{{ route('admin.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
      @if ($post->slug && $post->is_published)
        <a href="{{ route('public.posts.show', $post->slug) }}" target="_blank" class="px-3 py-1.5 border rounded">公開ページ</a>
      @endif
    </div>
  </div>

  <form id="postForm" method="POST" action="{{ route('admin.posts.update', $post) }}"
        enctype="multipart/form-data" novalidate
        class="grid gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')

    <div class="lg:col-span-2 space-y-4">
      {{-- タイトル --}}
      <div class="rounded border bg-white p-4">
        <label for="title" class="block text-sm text-gray-700">タイトル <span class="text-red-500">*</span></label>
        <input name="title" id="title" value="{{ old('title', $post->title) }}" class="mt-1 w-full border rounded px-3 py-2">
      </div>

      {{-- アイキャッチ --}}
      <div class="rounded border bg-white p-4">
        <label for="eyecatch" class="block text-sm text-gray-700">アイキャッチ画像</label>
        <input type="file" name="eyecatch" id="eyecatch" accept="image/*" class="w-full">
        <p class="text-xs text-gray-500 mt-1">jpg/jpeg/png/webp、<b>4MBまで</b></p>

        @if ($ey)
          <figure class="mt-3">
            <img src="{{ $ey }}" alt="現在のアイキャッチ" class="w-full aspect-[16/9] object-cover rounded" width="1200" height="675">
          </figure>
        @endif

        <figure class="mt-3">
          <img id="eyThumb" class="hidden w-full aspect-[16/9] rounded object-cover" alt="新しいアイキャッチのプレビュー" width="1200" height="675">
        </figure>
      </div>

      {{-- 導入文 --}}
      <div class="rounded border bg-white p-4">
        <label for="lead" class="block text-sm text-gray-700">導入文</label>
        <textarea name="lead" id="lead" rows="4" class="w-full border rounded px-3 py-2"
                  placeholder="記事冒頭の概要・リード文">{{ old('lead', $post->lead) }}</textarea>
      </div>

      {{-- 本文エディタ --}}
      <div class="rounded border bg-white">
        <div id="toolbar" class="flex flex-wrap items-center gap-1 p-2 border-b bg-gray-50 rounded-t">
          <button type="button" data-cmd="bold" class="btn" title="太字">B</button>
          <button type="button" data-cmd="underline" class="btn" title="下線"><u>U</u></button>
          <span class="mx-2"></span>
          <label class="text-sm text-gray-600 mr-1">ブロック</label>
          <select id="blockSelect" class="border rounded px-2 py-1 text-sm">
            <option value="P">段落</option>
            <option value="H2">見出し H2</option>
            <option value="H3">見出し H3</option>
            <option value="BLOCKQUOTE">引用</option>
            <option value="PRE">コード</option>
          </select>
          <span class="mx-2"></span>
          <button type="button" data-cmd="insertUnorderedList" class="btn" title="箇条書き">• List</button>
          <button type="button" data-cmd="insertOrderedList" class="btn" title="番号リスト">1. List</button>
          <span class="mx-2"></span>
          <input type="file" id="imgInput" accept="image/*" class="hidden">
          <button type="button" id="btnImage" class="btn" title="画像">画像</button>
        </div>

        {{-- 表示用（編集用） --}}
        <div id="editor"
             contenteditable="true"
             role="textbox" aria-multiline="true"
             class="p-3 min-h-[320px] bg-white editor-prose focus:outline-none">{!! old('body', $post->body) !!}</div>

        {{-- サーバー送信用（これを更新） --}}
        <textarea id="bodyField" name="body" class="hidden">{{ old('body', $post->body) }}</textarea>

        <div class="px-4 py-2 text-xs text-gray-500 border-t">
          ※ 送信時、上のエディタ内容が <code>body</code> に保存されます（サーバー側で公開/下書きを分岐）。
        </div>
      </div>

      {{-- 公開設定 --}}
      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">公開設定</h2>
        <label class="block text-sm text-gray-700">公開日時</label>
        <input type="datetime-local" name="published_at" value="{{ $publishedLocal }}" class="mt-1 w-full border rounded px-3 py-2">
        <p class="text-xs text-gray-500 mt-1">※「公開する」を押して未入力なら現在時刻で公開します。下書きの場合は無視されます。</p>
      </section>

      {{-- 広告設定 --}}
      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">広告設定</h2>
        <label class="inline-flex items-center gap-2 mb-2">
          <input type="checkbox" name="show_ad_under_lead" value="1" {{ old('show_ad_under_lead', (int)$post->show_ad_under_lead) ? 'checked' : '' }}>
          <span>導入文の直下に広告を表示</span>
        </label>
        <div class="mt-2">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="show_ad_in_body" value="1" {{ old('show_ad_in_body', (int)$post->show_ad_in_body) ? 'checked' : '' }}>
            <span>本文中（H2の直後）に広告を挿入</span>
          </label>
          <div class="mt-2 pl-6">
            <label class="block text-sm text-gray-700 mb-1">本文中の最大表示枠数</label>
            <input type="number" name="ad_in_body_max" min="0" max="5"
                   value="{{ old('ad_in_body_max', $post->ad_in_body_max) }}"
                   class="w-24 border rounded px-2 py-1">
          </div>
        </div>
        <label class="inline-flex items-center gap-2 mt-3">
          <input type="checkbox" name="show_ad_below" value="1" {{ old('show_ad_below', (int)$post->show_ad_below) ? 'checked' : '' }}>
          <span>本文の下（記事末尾）に広告を表示</span>
        </label>
      </section>

      {{-- SEO --}}
      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">SEO</h2>
        <label class="block text-sm text-gray-700">メタタイトル（70文字）</label>
        <input name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="mt-1 w-full border rounded px-3 py-2" maxlength="70">
        <label class="block text-sm text-gray-700 mt-3">メタディスクリプション（160文字）</label>
        <textarea name="meta_description" rows="3" class="w-full border rounded px-3 py-2" maxlength="160">{{ old('meta_description', $post->meta_description) }}</textarea>
      </section>

      {{-- アクション（サーバーが name="action" で分岐） --}}
      <section class="rounded border bg-white p-4">
        <div class="flex flex-wrap gap-2">
          <button type="submit" name="action" value="save_draft" class="px-4 py-2 border rounded">下書き保存</button>
          <button type="submit" name="action" value="publish" class="px-4 py-2 rounded bg-blue-600 text-white">公開する</button>
          <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 border rounded text-center">一覧へ戻る</a>
        </div>
      </section>
    </div>

    {{-- 右サイド --}}
    <aside class="lg:col-span-1">
      <div class="lg:sticky lg:top-4 space-y-4">
        {{-- カテゴリー --}}
        <section class="rounded border bg-white p-4">
          <h2 class="font-semibold mb-2">カテゴリー</h2>
          @if(isset($categories) && count($categories))
            <label for="category_id" class="block text-sm text-gray-700 mb-1">カテゴリを選択</label>
            <select name="category_id" id="category_id" class="w-full border rounded px-3 py-2">
              <option value="" hidden>選択してください</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string)old('category_id', (string)$post->category_id)===(string)$cat->id ? 'selected' : '' }}>
                  {{ $cat->name }}
                </option>
              @endforeach
            </select>
          @else
            <p class="text-sm text-gray-600">カテゴリー候補がありません。先に作成してください。</p>
          @endif
        </section>

        {{-- スラッグ --}}
        <section class="rounded border bg-white p-4">
          <h2 class="font-semibold mb-2">スラッグ（URL）</h2>
          <input id="slug" name="slug" value="{{ old('slug', $post->slug) }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="例: my-article">
          <p class="text-xs text-gray-500 mt-1">※ 自動生成はしません。公開時は必須運用（サーバー側でチェック）。</p>
        </section>
      </div>
    </aside>
  </form>
</div>

{{-- スタイル（簡易） --}}
<style>
  .btn{border:1px solid #d1d5db;border-radius:.375rem;padding:.25rem .5rem;font-size:.875rem;background:#fff}
  .btn:hover{background:#f9fafb}
  .editor-prose { color:#111827; }
  .editor-prose p { margin:.7em 0; line-height:1.8; }
  .editor-prose h2 { font-weight:700; line-height:1.35; margin:1.25em 0 .6em; font-size:1.5rem; }
  @media (min-width:640px){ .editor-prose h2{ font-size:1.75rem; } }
  .editor-prose h3 { font-weight:600; line-height:1.45; margin:1.1em 0 .5em; font-size:1.25rem; }
  .editor-prose ul, .editor-prose ol { margin:.6em 0 .8em; padding-left:1.4em; }
  .editor-prose ul { list-style:disc; }
  .editor-prose ol { list-style:decimal; }
  .editor-prose a { color:#2563eb; text-decoration:underline; }
  .editor-prose blockquote{ margin:1em 0; padding:.6em 1em; color:#374151; border-left:4px solid #e5e7eb; background:#f9fafb; border-radius:.25rem; }
  .editor-prose pre{ margin:.8em 0; padding:.75rem; border-radius:.5rem; background:#0b1220; color:#e5e7eb; overflow-x:auto; line-height:1.6; }
  .editor-prose pre code{ background:transparent; padding:0; }
  .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:4px; }
  .editor-prose img{ max-width:100%; height:auto; border-radius:.25rem; }
</style>

{{-- エディタ用JS（最小限） --}}
<script>
  const $ = (s) => document.querySelector(s);
  const exec = (cmd, value = null) => document.execCommand(cmd, false, value);

  function disableItalicShortcut(el) {
    el.addEventListener('keydown', (e) => {
      const isMac = navigator.platform.toUpperCase().includes('MAC');
      const mod = isMac ? e.metaKey : e.ctrlKey;
      if (mod && (e.key === 'i' || e.key === 'I')) e.preventDefault();
    });
  }
  function stripItalics(root) {
    root.querySelectorAll('em, i').forEach((node) => {
      const frag = document.createDocumentFragment();
      while (node.firstChild) frag.appendChild(node.firstChild);
      node.replaceWith(frag);
    });
  }

  (function initEditPage() {
    const ed   = $('#editor');
    const form = $('#postForm');

    // editor 初期化
    disableItalicShortcut(ed);
    stripItalics(ed);
    ed.addEventListener('input', () => stripItalics(ed));
    ed.addEventListener('paste', () => setTimeout(() => stripItalics(ed), 0));

    // ツールバー
    document.querySelectorAll('#toolbar [data-cmd]').forEach((b) => {
      b.addEventListener('click', () => exec(b.dataset.cmd));
    });
    $('#blockSelect').addEventListener('change', (e) => {
      const map = { P:'p', H2:'h2', H3:'h3', BLOCKQUOTE:'blockquote', PRE:'pre' };
      exec('formatBlock', map[e.target.value] || 'p');
      ed.focus();
    });

    // 本文 → textarea へ同期
    form.addEventListener('submit', () => {
      document.getElementById('bodyField').value = ed.innerHTML.trim();
    });

    // 本文内画像アップロード（任意：アップロードルートがある場合のみ）
    const imgInput = $('#imgInput');
    $('#btnImage').addEventListener('click', () => imgInput.click());
    imgInput.addEventListener('change', async () => {
      const f = imgInput.files?.[0]; if (!f) return;
      @if ($uploadUrl)
        try {
          const fd = new FormData(); fd.append('file', f);
          const res = await fetch(@json($uploadUrl), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: fd
          });
          if (!res.ok) throw new Error('upload failed');
          const json = await res.json();
          exec('insertImage', json.location);
        } catch {
          insertAsDataURL(f);
        }
      @else
        insertAsDataURL(f);
      @endif
      imgInput.value = '';
    });
    function insertAsDataURL(file) {
      const r = new FileReader();
      r.onload = () => exec('insertImage', r.result);
      r.readAsDataURL(file);
    }

    // アイキャッチ preview
    const ey = $('#eyecatch'), th = $('#eyThumb');
    if (ey && th) {
      ey.addEventListener('change', () => {
        const f = ey.files?.[0];
        if (!f) { th.classList.add('hidden'); th.removeAttribute('src'); return; }
        if (f.size > 4 * 1024 * 1024) {
          alert('アイキャッチは 4MB 以下にしてください。');
          ey.value = ''; th.classList.add('hidden'); th.removeAttribute('src'); return;
        }
        const r = new FileReader();
        r.onload = (e) => { th.src = e.target.result; th.classList.remove('hidden'); };
        r.readAsDataURL(f);
      });
    }
  })();
</script>
@endsection
