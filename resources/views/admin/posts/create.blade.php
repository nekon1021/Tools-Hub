@extends('layouts.app')

@section('title', '記事作成｜' . config('app.name'))

@section('content')
  <h1 class="text-2xl font-bold mb-6">記事作成</h1>

  {{-- バリデーションエラー --}}
  @if ($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  @php
    $uploadUrl = \Illuminate\Support\Facades\Route::has('admin.editor.upload')
      ? route('admin.editor.upload') : null;
  @endphp

  <form id="postForm" method="POST" action="{{ route('admin.posts.store') }}"
        enctype="multipart/form-data" novalidate
        class="grid gap-6 lg:grid-cols-3">
    @csrf

    <div class="lg:col-span-2 space-y-4">
      <div class="rounded border bg-white p-4">
        <label for="title" class="block text-sm text-gray-700">タイトル <span class="text-red-500">*</span></label>
        <input name="title" id="title" value="{{ old('title') }}" class="mt-1 w-full border rounded px-3 py-2">
      </div>

      <div class="rounded border bg-white p-4">
        <label for="eyecatch" class="block text-sm text-gray-700">アイキャッチ画像</label>
        <input type="file" name="eyecatch" id="eyecatch" accept="image/*" class="w-full">
        <p class="text-xs text-gray-500 mt-1">jpg/jpeg/png/webp、<b>4MBまで</b></p>
        <figure class="mt-3">
          <img id="eyThumb" class="hidden w-full aspect-[16/9] rounded object-cover" alt="eyecatch preview" width="1200" height="675">
        </figure>
      </div>

      <div class="rounded border bg-white p-4">
        <label for="lead" class="block text-sm text-gray-700">導入文</label>
        <textarea name="lead" id="lead" rows="4" class="w-full border rounded px-3 py-2"
                  placeholder="記事冒頭の概要・リード文（本文の前に表示されます）">{{ old('lead') }}</textarea>
      </div>

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

        {{-- エディタ（contenteditable） --}}
        <div id="editor"
             contenteditable="true"
             role="textbox" aria-multiline="true"
             class="p-3 min-h-[320px] bg-white editor-prose focus:outline-none">{!! old('body') !!}</div>

        {{-- 送信用の実体（サーバーが読むのはこれだけ） --}}
        <textarea id="bodyField" name="body" class="hidden">{{ old('body') }}</textarea>

        <noscript>
          <div class="p-4 border-t">
            <label for="body_fallback" class="block text-sm text-gray-700">本文（JSオフ用）</label>
            <textarea id="body_fallback" name="body" rows="16" class="w-full border rounded px-3 py-2">{{ old('body') }}</textarea>
          </div>
        </noscript>

        <div class="px-4 py-2 text-xs text-gray-500 border-t">
          ※ 保存時に上のエディタ内容が <code>body</code> に入ります（サーバー側で分岐）。
        </div>
      </div>

      {{-- 公開設定（分岐はサーバーが action を見る） --}}
      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">公開設定</h2>
        <label class="block text-sm text-gray-700">公開日時</label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at') }}" class="mt-1 w-full border rounded px-3 py-2">
        <p class="text-xs text-gray-500 mt-1">※「公開する」を押して未入力なら現在時刻で公開します。</p>
      </section>

      {{-- 広告/SEO は省略可（必要なら前の版をそのまま） --}}
      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">広告設定</h2>
        <label class="inline-flex items-center gap-2 mb-2">
          <input type="checkbox" name="show_ad_under_lead" value="1" {{ old('show_ad_under_lead', 1) ? 'checked' : '' }}>
          <span>導入文の直下に広告を表示</span>
        </label>
        <div class="mt-2">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="show_ad_in_body" value="1" {{ old('show_ad_in_body', 1) ? 'checked' : '' }}>
            <span>本文中（H2の直後）に広告を挿入</span>
          </label>
          <div class="mt-2 pl-6">
            <label class="block text-sm text-gray-700 mb-1">本文中の最大表示枠数</label>
            <input type="number" name="ad_in_body_max" min="0" max="5" value="{{ old('ad_in_body_max', 2) }}" class="w-24 border rounded px-2 py-1">
          </div>
        </div>
        <label class="inline-flex items-center gap-2 mt-3">
          <input type="checkbox" name="show_ad_below" value="1" {{ old('show_ad_below', 1) ? 'checked' : '' }}>
          <span>本文の下（記事末尾）に広告を表示</span>
        </label>
      </section>

      <section class="rounded border bg-white p-4">
        <h2 class="font-semibold mb-2">SEO</h2>
        <label class="block text-sm text-gray-700">メタタイトル（70文字）</label>
        <input name="meta_title" value="{{ old('meta_title') }}" class="mt-1 w-full border rounded px-3 py-2" maxlength="70">
        <label class="block text-sm text-gray-700 mt-3">メタディスクリプション（160文字）</label>
        <textarea name="meta_description" rows="3" class="w-full border rounded px-3 py-2" maxlength="160">{{ old('meta_description') }}</textarea>
      </section>

      {{-- サーバーが action で分岐 --}}
      <section class="rounded border bg-white p-4">
        <div class="flex flex-wrap gap-2">
          <button type="submit" name="action" value="save_draft" class="px-4 py-2 border rounded">下書き保存</button>
          <button type="submit" name="action" value="publish" class="px-4 py-2 rounded bg-blue-600 text-white">公開する</button>
          <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 border rounded text-center">一覧へ戻る</a>
        </div>
      </section>
    </div>

    <aside class="lg:col-span-1">
      <div class="lg:sticky lg:top-4 space-y-4">
        @isset($categories)
          <section class="rounded border bg-white p-4">
            <h2 class="font-semibold mb-2">カテゴリー</h2>
            @if(count($categories))
              <label for="category_id" class="block text-sm text-gray-700 mb-1">カテゴリを選択</label>
              <select name="category_id" id="category_id" class="w-full border rounded px-3 py-2">
                <option value="" hidden>選択してください</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}" {{ (string)old('category_id')===(string)$cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                  </option>
                @endforeach
              </select>
            @else
              <p class="text-sm text-gray-600">カテゴリー候補がありません。先に作成してください。</p>
            @endif
          </section>
        @endisset

        <section class="rounded border bg-white p-4">
          <h2 class="font-semibold mb-2">スラッグ（URL）</h2>
          <input id="slug" name="slug" value="{{ old('slug') }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="例: my-article">
          <p class="text-xs text-gray-500 mt-1">※ 自動生成はしません。必要に応じて手入力してください。</p>
        </section>
      </div>
    </aside>
  </form>

  <style>
    .btn{border:1px solid #d1d5db;border-radius:.375rem;padding:.25rem .5rem;font-size:.875rem;background:#fff}
    .btn:hover{background:#f9fafb}
    .editor-prose { color:#111827; }
    .editor-prose p { margin: .7em 0; line-height: 1.8; }
    .editor-prose h2 { font-weight: 700; line-height: 1.35; margin: 1.25em 0 .6em; font-size: 1.5rem; }
    @media (min-width:640px){ .editor-prose h2{ font-size:1.75rem; } }
    .editor-prose h3 { font-weight: 600; line-height: 1.45; margin: 1.1em 0 .5em; font-size: 1.25rem; }
    .editor-prose ul, .editor-prose ol { margin: .6em 0 .8em; padding-left: 1.4em; }
    .editor-prose ul { list-style: disc; }
    .editor-prose ol { list-style: decimal; }
    .editor-prose blockquote{ margin:1em 0; padding:.6em 1em; color:#374151; border-left:4px solid #e5e7eb; background:#f9fafb; border-radius:.25rem; }
    .editor-prose pre{ margin: .8em 0; padding: .75rem; border-radius:.5rem; background:#0b1220; color:#e5e7eb; overflow-x:auto; line-height:1.6; }
    .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:4px; }
    .editor-prose img{ max-width:100%; height:auto; border-radius:.25rem; }
  </style>

  <script>
    // 最小補助: イタリック禁止、簡易コマンド、画像挿入、submit前に body へ反映
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

    (function initCreatePost() {
      const ed   = $('#editor');
      const form = $('#postForm');

      disableItalicShortcut(ed);
      stripItalics(ed);
      ed.addEventListener('input', () => stripItalics(ed));
      ed.addEventListener('paste', () => setTimeout(() => stripItalics(ed), 0));

      document.querySelectorAll('#toolbar [data-cmd]').forEach((b) => {
        b.addEventListener('click', () => exec(b.dataset.cmd));
      });

      $('#blockSelect').addEventListener('change', (e) => {
        const map = { P: 'p', H2: 'h2', H3: 'h3', BLOCKQUOTE: 'blockquote', PRE: 'pre' };
        exec('formatBlock', map[e.target.value] || 'p');
        ed.focus();
      });

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

      form.addEventListener('submit', () => {
        document.getElementById('bodyField').value = ed.innerHTML.trim();
      });

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
