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
<section class="mx-auto max-w-screen-xl px-4 pt-6 sm:pt-10">
  <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
    <nav aria-label="Breadcrumb">
      <ol class="flex flex-wrap items-center gap-1">
        <li><a href="{{ route('admin.posts.index') }}" class="hover:underline">記事一覧</a></li>
        <li aria-hidden="true">/</li>
        <li class="text-gray-400">記事編集</li>
      </ol>
    </nav>

    <div class="ml-auto flex flex-wrap gap-2">
      @if ($post->slug && $post->is_published)
        <a href="{{ route('public.posts.show', $post->slug) }}" target="_blank" class="px-3 py-1.5 border rounded">公開ページ</a>
      @endif
      <a href="{{ route('admin.posts.index') }}" class="px-3 py-1.5 border rounded">一覧へ戻る</a>
    </div>
  </div>

  <header class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
      <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">記事編集</h1>
      <p class="mt-3 text-gray-600">
        タイトル・導入文・アイキャッチを確認し、本文を編集してください。公開は右側のアクションから行えます。
      </p>
    </div>

    {{-- Eyecatch（単一の枠に統一） --}}
    <aside class="lg:col-span-1">
      <div class="rounded-xl border bg-white p-3 shadow-sm">
        <figure id="eyFrame" class="aspect-[16/9] w-full overflow-hidden rounded-lg bg-gray-100">
          <img
            id="eyImg"
            @if ($ey)
              src="{{ $ey }}"
              data-original="{{ $ey }}"
              class="h-full w-full object-cover"
            @else
              data-original=""
              class="hidden h-full w-full object-cover"
            @endif
            alt="アイキャッチ"
            width="1200" height="675">
        </figure>
        <div class="mt-3 space-y-2">
          <label for="eyecatch" class="block text-sm text-gray-700">アイキャッチ画像</label>
          <input type="file" name="eyecatch" id="eyecatch" accept="image/*" class="w-full text-sm">
          <p class="text-xs text-gray-500">jpg/jpeg/png/webp、<b>4MBまで</b>・16:9推奨（1200×675）</p>
        </div>
      </div>
    </aside>
  </header>
</section>

@if ($errors->any())
  <div class="mx-auto max-w-screen-xl px-4 mt-6">
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  </div>
@endif

<form id="postForm" method="POST" action="{{ route('admin.posts.update', $post) }}"
      enctype="multipart/form-data" novalidate class="mx-auto max-w-screen-xl px-4 mt-6">
  @csrf
  @method('PUT')

  {{-- 3カラム：左=シェア, 中央=本文/基本, 右=SEO/広告/アクション --}}
  <div class="grid gap-6 lg:grid-cols-[56px_minmax(0,1fr)_320px]">
    {{-- 左：シェアUI --}}
    <aside class="hidden lg:block">
      <div class="sticky top-24 flex flex-col items-center gap-2 text-gray-500">
        <button type="button" title="リンクをコピー" class="rounded-full border p-2 hover:bg-gray-50"
                aria-label="Copy link"
                onclick="navigator.clipboard.writeText(location.href).then(()=>alert('リンクをコピーしました'));">
          <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3.9 12a4.6 4.6 0 0 1 4.6-4.6h3v2.3h-3a2.3 2.3 0 1 0 0 4.6h3v2.3h-3A4.6 4.6 0 0 1 3.9 12Zm6.1 1.1h4V11h-4v2.1Zm5.4-5.7h-3V5.1h3A4.6 4.6 0 0 1 22 9.7a4.6 4.6 0 0 1-4.6 4.6h-3v-2.3h3a2.3 2.3 0 1 0 0-4.6Z"/></svg>
        </button>
      </div>
    </aside>

    {{-- メイン列（中央） --}}
    <main class="min-w-0 space-y-6">
      {{-- 基本情報 --}}
      <section class="rounded-xl border bg-white shadow-sm">
        <div class="p-5 sm:p-6 space-y-4">
          <div>
            <label for="title" class="block text-sm text-gray-700">タイトル <span class="text-red-500">*</span></label>
            <input name="title" id="title" value="{{ old('title', $post->title) }}"
                   class="mt-1 w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>

          <div>
            <label for="lead" class="block text-sm text-gray-700">導入文</label>
            <textarea name="lead" id="lead" rows="3"
                      class="mt-1 w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="記事冒頭の概要・リード文">{{ old('lead', $post->lead) }}</textarea>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm text-gray-700">公開日時</label>
              <input type="datetime-local" name="published_at" value="{{ $publishedLocal }}"
                     class="mt-1 w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p class="mt-1 text-xs text-gray-500">※「公開する」で未入力なら現在時刻で公開します。</p>
            </div>
            <div>
              <label for="category_id" class="block text-sm text-gray-700">カテゴリー</label>
              @isset($categories)
                @if(count($categories))
                  <select name="category_id" id="category_id"
                          class="mt-1 w-full rounded-md border px-3 py-2">
                    <option value="" hidden>選択してください</option>
                    @foreach ($categories as $cat)
                      <option value="{{ $cat->id }}" {{ (string)old('category_id', (string)$post->category_id)===(string)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                      </option>
                    @endforeach
                  </select>
                @else
                  <p class="mt-2 text-sm text-gray-600">カテゴリー候補がありません。先に作成してください。</p>
                @endif
              @endisset
            </div>
          </div>
        </div>
      </section>

      {{-- 本文エディタ（ツールバー sticky） --}}
      <section class="rounded-xl border bg-white shadow-sm overflow-visible">
        <div id="toolbar"
             class="sticky z-30 flex flex-wrap items-center gap-1 p-2 border-b bg-gray-50"
             style="top: var(--editor-sticky-top, 0px);">
          <button type="button" data-cmd="bold" class="btn" title="太字">B</button>
          <button type="button" data-cmd="underline" class="btn" title="下線"><u>U</u></button>
          <span class="mx-1 hidden sm:inline">|</span>

          <label class="text-sm text-gray-600 mr-1">ブロック</label>
          <select id="blockSelect" class="border rounded px-2 py-1 text-sm">
            <option value="P">段落</option>
            <option value="H2">見出し H2（目次対象）</option>
            <option value="H3">見出し H3</option>
            <option value="BLOCKQUOTE">引用</option>
            <option value="PRE">コード</option>
          </select>

          <span class="mx-1 hidden sm:inline">|</span>
          <button type="button" data-cmd="insertUnorderedList" class="btn" title="箇条書き">• List</button>
          <button type="button" data-cmd="insertOrderedList" class="btn" title="番号リスト">1. List</button>

          <span class="mx-1 hidden sm:inline">|</span>
          <input type="file" id="imgInput" accept="image/*" class="hidden">
          <button type="button" id="btnImage" class="btn" title="画像">画像</button>

          <span class="mx-1 hidden sm:inline">|</span>
          <div class="flex flex-wrap gap-1">
            <button type="button" class="btn" data-insert="section">セクション見出し</button>
            <button type="button" class="btn" data-insert="tips">ポイント / Tips</button>
            <button type="button" class="btn" data-insert="steps">番号ステップ</button>
            <button type="button" class="btn" data-insert="quote">注目引用</button>
            <button type="button" class="btn" data-insert="figure">画像＋キャプション</button>
          </div>
        </div>

        {{-- 表示用（編集用） --}}
        <div class="px-3 sm:px-6 py-4">
          <label for="editor" class="block mb-2 text-sm text-gray-700">本文</label>
          <div id="editor"
               contenteditable="true"
               role="textbox" aria-multiline="true"
               class="editor-prose min-h-[420px] focus:outline-none"
               data-ph="ここに本文を入力してください">{!! old('body', $post->body) !!}</div>
        </div>

        {{-- サーバー送信用（これを更新） --}}
        <textarea id="bodyField" name="body" class="hidden">{{ old('body', $post->body) }}</textarea>

        <div class="px-4 py-2 text-xs text-gray-500 border-t bg-gray-50">
          ※ 保存時に上のエディタ内容が <code>body</code> に入ります。
        </div>
      </section>
    </main>

    {{-- 右カラム：SEO / 広告設定 / アクション --}}
    <aside>
      <div class="lg:sticky lg:top-24 space-y-4">
        {{-- SEO --}}
        <section class="rounded-xl border bg-white shadow-sm p-5 sm:p-6">
          <h2 class="font-semibold mb-3">SEO</h2>
          <label class="block text-sm text-gray-700">メタタイトル（70文字）</label>
          <input name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="mt-1 w-full border rounded px-3 py-2" maxlength="70">

          <label class="block text-sm text-gray-700 mt-3">メタディスクリプション（160文字）</label>
          <textarea name="meta_description" rows="3" class="w-full border rounded px-3 py-2" maxlength="160">{{ old('meta_description', $post->meta_description) }}</textarea>

          <div class="mt-3">
            <label class="block text-sm text-gray-700">スラッグ（URL）</label>
            <input id="slug" name="slug" value="{{ old('slug', $post->slug) }}" class="mt-1 w-full border rounded px-3 py-2" placeholder="例: my-article">
            <p class="text-xs text-gray-500 mt-1">※ 自動生成はしません。公開時は必須（サーバー側でチェック）。</p>
          </div>
        </section>

        {{-- 広告設定 --}}
        <section class="rounded-xl border bg-white shadow-sm p-5 sm:p-6">
          <h2 class="font-semibold mb-3">広告設定</h2>
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

        {{-- アクション --}}
        <section class="rounded-xl border bg-white shadow-sm p-5 sm:p-6">
          <div class="flex flex-wrap gap-2">
            <button type="submit" name="action" value="save_draft" class="px-4 py-2 border rounded">下書き保存</button>
            <button type="submit" name="action" value="publish" class="px-4 py-2 rounded bg-blue-600 text-white">公開する</button>
            <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 border rounded text-center">一覧へ戻る</a>
          </div>
        </section>
      </div>
    </aside>
  </div>
</form>

<style>
  :root { --editor-sticky-top: 0px; } /* ヘッダー分オフセット（JSで上書き） */

  .btn{border:1px solid #d1d5db;border-radius:.375rem;padding:.25rem .5rem;font-size:.875rem;background:#fff}
  .btn:hover{background:#f9fafb}
  .editor-prose { color:#111827; max-width: 70ch; margin-inline: auto; }
  .editor-prose p { margin:.85em 0; line-height:1.9; font-size:1.05rem; }
  .editor-prose h2 { font-weight:800; line-height:1.3; margin:1.6em 0 .7em; font-size:1.8rem; letter-spacing:-0.01em; }
  @media (min-width:640px){ .editor-prose h2{ font-size:2rem; } }
  .editor-prose h3 { font-weight:700; line-height:1.4; margin:1.2em 0 .6em; font-size:1.25rem; }
  .editor-prose ul, .editor-prose ol { margin:.6em 0 .9em; padding-left:1.4em; }
  .editor-prose ul { list-style:disc; } .editor-prose ol { list-style:decimal; }
  .editor-prose blockquote{
    margin:1.2em 0; padding:1rem 1.2rem; color:#374151;
    border-left:4px solid #22c55e1a; background:#10b9810a; border-radius:.5rem;
  }
  .editor-prose pre{
    margin:1em 0; padding:.9rem 1rem; border-radius:.75rem;
    background:#0b1220; color:#e5e7eb; overflow-x:auto; line-height:1.7; font-size:.95rem;
  }
  .editor-prose code{ background:#f6f8fa; padding:.15em .35em; border-radius:4px; }
  .editor-prose img{ max-width:100%; height:auto; border-radius:.5rem; }

  .tips { background:#f0f9ff; border:1px solid #bae6fd; border-radius:.75rem; padding:1rem; }
  .steps { counter-reset: step; }
  .steps li { counter-increment: step; margin:.5rem 0; }
  .steps li::marker { content: counter(step) ". "; font-weight:700; }
</style>

<script>
const $ = (s) => document.querySelector(s);
const exec = (cmd, value = null) => document.execCommand(cmd, false, value);

/** 斜体禁止 */
function disableItalicShortcut(el){
  el.addEventListener('keydown', (e)=>{
    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const mod = isMac ? e.metaKey : e.ctrlKey;
    if(mod && (e.key==='i'||e.key==='I')) e.preventDefault();
  });
}
function stripItalics(root){
  root.querySelectorAll('em,i').forEach((node)=>{
    const frag=document.createDocumentFragment();
    while(node.firstChild) frag.appendChild(node.firstChild);
    node.replaceWith(frag);
  });
}

/** 「実質的に空」かどうか（フロント判定） */
function hasMeaningfulContent(html){
  if(!html) return false;

  if (/<(img|video|iframe|pre|blockquote|ul|ol|h2|h3)\b/i.test(html)) return true;

  const textish = html
    .replace(/<style[\s\S]*?<\/style>/gi,'')
    .replace(/<script[\s\S]*?<\/script>/gi,'')
    .replace(/<!--[\s\S]*?-->/g,'')
    .replace(/<[^>]+>/g,'')
    .replace(/&nbsp;|\u00A0/g,' ')
    .trim();

  return textish.length > 0;
}

/** 本文HTMLをhiddenに同期 */
function syncEditorToField(ed, field){
  let html = ed.innerHTML
    .replace(/^(?:\s|<br\s*\/?>)+/gi,'')
    .replace(/(?:\s|<br\s*\/?>)+$/gi,'')
    .trim();
  field.value = hasMeaningfulContent(html) ? html : '';
}

/** 便利テンプレ（FAQなし） */
const TPL = {
  section: `<h2>見出し（例：重要ポイント）</h2>
<p>ここに本文。要点は箇条書きでもOK。</p>`,
  tips: `<div class="tips">
<strong>ポイント：</strong>
<ul>
  <li>具体例を1つ入れる</li>
  <li>数値や比較で根拠を示す</li>
  <li>次のアクションを提示</li>
</ul>
</div>`,
  steps: `<ol class="steps">
  <li><strong>準備：</strong> コンセプトとペルソナを決める</li>
  <li><strong>設計：</strong> プロフィールとCTAを最適化</li>
  <li><strong>運用：</strong> 週◯本投稿</li>
</ol>`,
  quote: `<blockquote><p>引用：重要な洞察を短く強調。</p></blockquote>`,
  figure: `<figure class="figure">
  <img src="" alt="説明画像" />
  <figcaption>画像の説明（キャプション）</figcaption>
</figure>`
};

(function initEditPage() {
  const ed   = $('#editor');
  const form = $('#postForm');
  const field= $('#bodyField');

  // editor 初期化
  disableItalicShortcut(ed);
  stripItalics(ed);
  ed.addEventListener('input', () => stripItalics(ed));
  ed.addEventListener('paste', () => setTimeout(() => stripItalics(ed), 0));

  const debounced = (()=>{ let t; return (fn)=>{ clearTimeout(t); t=setTimeout(fn,120);} })();
  const onChange = ()=>{ syncEditorToField(ed, field); };
  ed.addEventListener('input', ()=>debounced(onChange));
  ed.addEventListener('blur', onChange);
  syncEditorToField(ed, field);

  // ツールバー操作
  document.querySelectorAll('#toolbar [data-cmd]').forEach((b) => {
    b.addEventListener('click', () => {
      exec(b.dataset.cmd);
      ed.focus();
      debounced(onChange);
    });
  });
  $('#blockSelect').addEventListener('change', (e) => {
    const map = { P:'p', H2:'h2', H3:'h3', BLOCKQUOTE:'blockquote', PRE:'pre' };
    exec('formatBlock', map[e.target.value] || 'p');
    ed.focus();
    debounced(onChange);
  });

  // 本文用 画像アップロード
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
    debounced(onChange);
  });
  function insertAsDataURL(file) {
    const r = new FileReader();
    r.onload = () => { exec('insertImage', r.result); debounced(onChange); };
    r.readAsDataURL(file);
  }

  // 挿入テンプレ
  document.querySelectorAll('#toolbar [data-insert]').forEach((b)=>{
    b.addEventListener('click', ()=>{
      const type = b.getAttribute('data-insert');
      const tpl = TPL[type] || '';
      document.execCommand('insertHTML', false, tpl);
      ed.focus();
      debounced(onChange);
    });
  });

  // submit 前チェック
  form.addEventListener('submit', (e)=>{
    syncEditorToField(ed, field);
    const html = ed.innerHTML
      .replace(/^(?:\s|<br\s*\/?>)+/gi,'')
      .replace(/(?:\s|<br\s*\/?>)+$/gi,'')
      .trim();
    if(!hasMeaningfulContent(html)){
      e.preventDefault();
      alert('本文を入力してください。');
      ed.focus();
    }
  }, true);

  // === アイキャッチ：単一枠でプレビュー（重複防止） ===
  const ey = $('#eyecatch');
  const eyImg = $('#eyImg');
  const original = eyImg ? (eyImg.getAttribute('data-original') || '') : '';

  if (ey && eyImg) {
    ey.addEventListener('change', () => {
      const f = ey.files?.[0];

      // 未選択に戻したら元画像に戻す（元が無ければ非表示）
      if (!f) {
        if (original) {
          eyImg.src = original;
          eyImg.classList.remove('hidden');
        } else {
          eyImg.removeAttribute('src');
          eyImg.classList.add('hidden');
        }
        return;
      }

      // サイズ制限
      if (f.size > 4 * 1024 * 1024) {
        alert('アイキャッチは 4MB 以下にしてください。');
        ey.value = '';
        // 元に戻す
        if (original) {
          eyImg.src = original;
          eyImg.classList.remove('hidden');
        } else {
          eyImg.removeAttribute('src');
          eyImg.classList.add('hidden');
        }
        return;
      }

      // プレビューは同じ <img> を差し替えるだけ（枠は1つ）
      const r = new FileReader();
      r.onload = (ev) => {
        eyImg.src = ev.target.result;
        eyImg.classList.remove('hidden');
      };
      r.readAsDataURL(f);
    });
  }

  // === ツールバーのstickyオフセットをヘッダー高さに合わせる ===
  function applyEditorStickyTop(){
    const header = document.querySelector('.site-header, .app-header, header[role="banner"]');
    const h = header ? header.offsetHeight : 0;
    document.documentElement.style.setProperty('--editor-sticky-top', (h || 0) + 'px');
  }
  applyEditorStickyTop();
  window.addEventListener('resize', applyEditorStickyTop);
})();
</script>
@endsection
