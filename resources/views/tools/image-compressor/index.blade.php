@extends('layouts.app')

@section('title', '画像圧縮ツール｜' . config('app.name'))

@section('meta_description', 'ブラウザで完結する無料の画像圧縮ツール。JPEG・PNG・WebPに対応。画質を保ったままファイルサイズを大幅削減し、セキュアかつ高速にご利用いただけます。')

@section('theme','image')

@section('content')
<div class="mx-auto max-w-5xl px-4">
  <div class="max-w-2xl mx-auto w-full space-y-6">
    @include('partials.breadcrumbs', [
      'items' => [
        ['name' => 'ホーム', 'url' => url('/')],
        ['name' => '画像圧縮ツール'],
      ]
    ])

    {{-- 見出し --}}
    <h1 class="text-2xl font-bold">画像圧縮ツール</h1>

    {{-- エラー（個別tryのエラー一覧） --}}
    @isset($errorList)
      @if(!empty($errorList))
        <div class="rounded-xl bg-amber-50 ring-1 ring-amber-200 px-4 py-3">
          <ul class="list-disc pl-5 text-amber-900">
            @foreach($errorList as $msg)
              <li>{{ $msg }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    @endisset

    {{-- 圧縮フォーム（オプションなし） --}}
    <form id="compress-form"
          method="POST"
          action="{{ route('tools.image.compressor.run') }}"
          enctype="multipart/form-data"
          class="space-y-5">
      @csrf

      {{-- アップロードUI（初期はボタン→投入後は枠＋プレビュー） --}}
      <div id="dz-root" class="w-full" role="group" aria-label="画像アップロード">
        {{-- 1) 初期：ボタン風 --}}
        <div id="dz-compact" class="relative">
          <input id="file-input" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp" multiple class="sr-only">
          <button type="button" id="compact-btn"
                  class="w-full inline-flex items-center justify-center gap-3 rounded-2xl
                         bg-emerald-600 text-white px-5 py-4 shadow-sm
                         hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5v14M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="font-semibold">画像を追加する</span>
            <span class="text-emerald-100/90 text-sm">(ドラッグ＆ドロップも可)</span>
          </button>
          <div id="compact-overlay"
               class="hidden absolute inset-0 rounded-2xl ring-4 ring-white/70 bg-white/10 pointer-events-none"></div>
        </div>

        {{-- 2) 画像投入後：枠＋プレビュー --}}
        <div id="dz-expanded"
             class="hidden relative rounded-2xl border-2 border-dashed border-emerald-300 bg-white/70
                    hover:border-emerald-400 transition p-3 sm:p-4 text-center overflow-hidden">
          <div id="dz-placeholder" class="flex flex-col items-center justify-center py-6 pointer-events-none select-none">
            <svg class="h-8 w-8 opacity-70" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M19 15v4a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-4M7 10l5-5 5 5M12 5v12"/>
            </svg>
            <p class="mt-2 font-medium text-gray-900 text-sm">ここにドロップ、または下のボタンから追加</p>
            <p class="text-xs text-gray-600">JPEG/PNG/WebP、1ファイル最大10MB、最大20ファイルまで</p>
            <p class="text-[11px] text-gray-500">※ クリップボードから <kbd>⌘/Ctrl</kbd>+<kbd>V</kbd> で貼り付け可</p>
          </div>

          <div id="preview"
               class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 text-left max-h-80 overflow-auto p-2"></div>

          <div id="dz-overlay"
               class="hidden absolute inset-0 rounded-2xl ring-4 ring-emerald-400/70 bg-emerald-50/60 z-10"></div>

          <div class="mt-3 flex items-center justify-between text-sm text-gray-600">
            <div><span id="file-count">0</span> ファイル / 合計 <span id="file-size">0</span></div>
            <div class="flex items-center gap-3">
              <button type="button" id="add-more"
                      class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                追加する
              </button>
              <button type="button" id="clear-all" class="text-gray-500 hover:text-gray-700 underline hidden">全てクリア</button>
            </div>
          </div>
        </div>
      </div>

      {{-- 送信（これだけ） --}}
      <div class="flex flex-wrap gap-3">
        <button type="submit" id="submit-btn"
                class="px-5 py-2.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
          圧縮する
        </button>
        <span id="hint" class="text-sm text-gray-500">ファイルを追加すると有効になります</span>
      </div>
    </form>

    <section class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 space-y-3">
      <h2 class="font-semibold text-gray-800">このツールについて</h2>
      <p>
        画像（JPEG / PNG / WebP）をまとめて圧縮するツールです。<br>
        アップロード → 「圧縮する」 → ダウンロードのシンプルな流れで、複数ファイルにも対応しています。
      </p>

      <h3 class="font-semibold mt-2">使い方</h3>
      <ol class="list-decimal ml-5 space-y-1">
        <li>「画像を追加する」ボタン、または枠内にドラッグ＆ドロップ（<kbd>⌘/Ctrl</kbd>+<kbd>V</kbd> で貼り付け可）。</li>
        <li>サムネイル一覧で内容を確認し、不要なものは右上「×」で削除。</li>
        <li>「圧縮する」をクリック。</li>
        <li>結果一覧に「ダウンロード」ボタンが出るので必要なものを保存。</li>
      </ol>

      <h3 class="font-semibold mt-2">対応・制限</h3>
      <ul class="list-disc ml-5 space-y-1">
        <li>対応形式：JPEG / PNG / WebP</li>
        <li>1ファイル最大サイズ：10MB</li>
        <li>同時アップロード数：最大 <strong>20ファイル</strong></li>
        <li>圧縮品質：標準（固定）／EXIF等のメタデータは再エンコード時に基本削除</li>
      </ul>

      <h3 class="font-semibold mt-2">ダウンロードについて</h3>
      <ul class="list-disc ml-5 space-y-1">
        <li>圧縮後は各ファイルに <strong>個別の「ダウンロード」ボタン</strong> が表示されます。</li>
        <li><strong>1回の処理で最大 20 ファイル</strong>までダウンロード可能（アップロード上限と同じ）。</li>
        <li>一括ダウンロード（ZIP）は未対応です。必要に応じて順番にクリックしてください。</li>
        <li>ダウンロード完了時は画面右下に「完了」トーストが表示されます。ブラウザのCookie制限で表示されない場合があります。</li>
      </ul>

      <h3 class="font-semibold mt-2">保存と削除</h3>
      <ul class="list-disc ml-5 space-y-1">
        <li>圧縮後のファイルはダウンロード用に公開URLが発行されます。</li>
        <li>不要な結果は各カード右上の「×」で削除できます。</li>
      </ul>

      <h3 class="font-semibold mt-2">よくある質問</h3>
      <dl class="space-y-2 ml-5">
        <div>
          <dt class="font-semibold">ダウンロード完了のメッセージが出ません</dt>
          <dd class="ml-6">ブラウザのCookie制限や拡張機能の影響で検知できない場合があります。別のブラウザ/シークレットウィンドウでお試しください。</dd>
        </div>
        <div>
          <dt class="font-semibold">圧縮後の画質が気になる</dt>
          <dd class="ml-6">元画像の圧縮状態や大幅な縮小で劣化が目立つことがあります。元画像をご確認ください。</dd>
        </div>
      </dl>
    </section>


    {{-- 結果：ダウンロード一覧 --}}
    @isset($results)
      @if(!empty($results))
        <section id="results" class="space-y-3">
          <h2 class="text-lg font-semibold">ダウンロード</h2>

          {{-- 固定トースト（右下） --}}
          <div id="dl-toast"
              class="hidden fixed right-4 bottom-4 z-50 rounded-lg bg-emerald-600 text-white text-sm px-4 py-2 shadow-lg">
            ダウンロードが完了しました。
          </div>

          <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4" id="results-list">
            @foreach($results as $item)
              @php
                $token  = \Illuminate\Support\Str::random(18); // 完了検知用トークン
                $signed = URL::signedRoute('tools.image.compressor.download', [
                  'p' => encrypt($item['path']),
                  't' => $token,
                ]);
              @endphp
              <li class="p-3 rounded-xl border border-emerald-200/50 bg-white relative" data-item>
                {{-- 右上の×ボタン（圧縮結果の削除） --}}
                <form method="POST"
                      action="{{ route('tools.image.compressor.delete') }}"
                      class="result-delete-form absolute top-2 right-2">
                  @csrf
                  <input type="hidden" name="p" value="{{ encrypt($item['path']) }}">
                  <button type="submit"
                          class="w-8 h-8 inline-flex items-center justify-center rounded-full bg-white/90 shadow ring-1 ring-gray-200 text-gray-700 hover:bg-white"
                          aria-label="この画像を削除" title="削除">
                    &times;
                  </button>
                </form>

                {{-- サムネ（公開URL推奨。無ければ asset('storage/...') でもOK） --}}
                <div class="mb-2 overflow-hidden rounded-lg aspect-[4/3] bg-gray-50">
                  <img src="{{ asset('storage/' . ltrim($item['path'], '/')) }}"
                       alt="{{ $item['name'] }}"
                       class="w-full h-full object-cover">
                </div>

                <p class="font-medium text-gray-900 truncate" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
                <p class="text-sm text-gray-600 mt-1">
                  圧縮率 {{ $item['ratio'] }}% / {{ number_format($item['bytes']) }} bytes
                </p>

                <a href="{{ $signed }}"
                   data-dltoken="{{ $token }}"
                   class="dl-link inline-block mt-3 px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                  ダウンロード
                </a>
              </li>
            @endforeach
          </ul>
        </section>
      @endif
    @endisset

  </div>
</div>
@endsection

{{-- 動作用スクリプト（アップロードUI＋ダウンロード検知＋結果削除） --}}
<script type="module">
(() => {
  const compact     = document.getElementById('dz-compact');
  const compactBtn  = document.getElementById('compact-btn');
  const compactOv   = document.getElementById('compact-overlay');
  const expanded    = document.getElementById('dz-expanded');
  const overlay     = document.getElementById('dz-overlay');
  const placeholder = document.getElementById('dz-placeholder');
  const preview     = document.getElementById('preview');

  const fileInput   = document.getElementById('file-input');
  const addMoreBtn  = document.getElementById('add-more');
  const clearAllBtn = document.getElementById('clear-all');

  const fileCount   = document.getElementById('file-count');
  const fileSize    = document.getElementById('file-size');
  const submitBtn   = document.getElementById('submit-btn');

  /** @type {File[]} */ let files = [];
  const BYTES_LIMIT = 10 * 1024 * 1024; // 10MB
  const MAX_FILES   = 20;

  const fmtSize = (n) => n>=1024*1024 ? (n/1024/1024).toFixed(1)+' MB' : n>=1024 ? (n/1024).toFixed(1)+' KB' : n+' B';
  const showCompact  = () => { compact.classList.remove('hidden'); expanded.classList.add('hidden'); };
  const showExpanded = () => { compact.classList.add('hidden');   expanded.classList.remove('hidden'); };

  const updateSummary = () => {
    const total = files.reduce((s,f)=>s+f.size,0);
    fileCount.textContent = String(files.length);
    fileSize.textContent  = fmtSize(total);
    submitBtn.disabled    = files.length === 0;
    clearAllBtn.classList.toggle('hidden', files.length === 0);
    placeholder.classList.toggle('hidden', files.length > 0);
    preview.classList.toggle('hidden', files.length === 0);
    if (files.length === 0) showCompact();
  };

  const fileItemHTML = (idx, url, name, size) => `
    <div class="group relative rounded-xl border border-gray-200 overflow-hidden bg-white">
      <img src="${url}" alt="${name}" class="aspect-square w-full object-cover">
      <div class="p-2">
        <p class="truncate text-sm font-medium text-gray-900" title="${name}">${name}</p>
        <p class="text-xs text-gray-500">${fmtSize(size)}</p>
      </div>
      <button type="button" data-remove="${idx}"
        class="absolute top-2 right-2 inline-flex items-center justify-center w-8 h-8 rounded-full
               bg-white/90 shadow ring-1 ring-gray-200 text-gray-700 opacity-0 group-hover:opacity-100
               hover:bg-white transition"
        aria-label="この画像を削除">&times;</button>
    </div>`;

  const render = () => {
    for (const img of preview.querySelectorAll('img')) {
      const u = img.getAttribute('src'); if (u?.startsWith('blob:')) URL.revokeObjectURL(u);
    }
    preview.innerHTML = files.map((f,i)=>fileItemHTML(i, URL.createObjectURL(f), f.name, f.size)).join('');
    updateSummary();
  };

  const addFiles = (list) => {
    const arr = Array.from(list || []);
    if (!arr.length) return;
    const take   = Math.max(0, MAX_FILES - files.length);
    const slice  = arr.slice(0, take);
    const accept = slice.filter(f => ['image/jpeg','image/png','image/webp'].includes(f.type) && f.size <= BYTES_LIMIT);
    if (accept.length < slice.length) alert('対応形式: JPEG/PNG/WebP、1ファイル最大10MB、合計20ファイルまでです。');
    files = files.concat(accept);
    if (files.length > 0) showExpanded();
    render();
  };

  // compact: クリック/キー/DnD
  compactBtn.addEventListener('click', () => fileInput.click());
  compactBtn.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
  });

  const prevent = (e) => { e.preventDefault(); e.stopPropagation(); };
  ['dragenter','dragover','dragleave','drop'].forEach(ev => compact.addEventListener(ev, prevent));
  compact.addEventListener('dragenter', () => compactOv.classList.remove('hidden'));
  compact.addEventListener('dragover',  () => compactOv.classList.remove('hidden'));
  compact.addEventListener('dragleave', () => compactOv.classList.add('hidden'));
  compact.addEventListener('drop',      (e) => { compactOv.classList.add('hidden'); showExpanded(); addFiles(e.dataTransfer?.files); });

  // expanded: DnD/追加
  ['dragenter','dragover','dragleave','drop'].forEach(ev => expanded.addEventListener(ev, prevent));
  expanded.addEventListener('dragenter', () => overlay.classList.remove('hidden'));
  expanded.addEventListener('dragover',  () => overlay.classList.remove('hidden'));
  expanded.addEventListener('dragleave', () => overlay.classList.add('hidden'));
  expanded.addEventListener('drop',      (e) => { overlay.classList.add('hidden'); addFiles(e.dataTransfer?.files); });

  addMoreBtn.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', (e) => addFiles(e.target.files));

  // 貼り付け
  window.addEventListener('paste', (e) => {
    const items = e.clipboardData?.files;
    if (items && items.length) { showExpanded(); addFiles(items); }
  });

  // サムネ削除・全クリア（未送信側）
  preview.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-remove]'); if (!btn) return;
    const idx = Number(btn.getAttribute('data-remove'));
    files.splice(idx, 1); render();
  });
  clearAllBtn.addEventListener('click', () => { files = []; render(); });

  // 送信（FileList を差し替えて通常POST）
  document.getElementById('compress-form').addEventListener('submit', (e) => {
    const dt = new DataTransfer(); files.forEach(f => dt.items.add(f)); fileInput.files = dt.files;
    if (files.length === 0) { e.preventDefault(); alert('ファイルを追加してください。'); return; }
    submitBtn.disabled = true;
    submitBtn.dataset._label = submitBtn.textContent;
    submitBtn.textContent = '圧縮中…';
  });

  // ===== ダウンロード完了検知（Cookieポーリング） =====
  (() => {
    const resultsList = document.getElementById('results-list');
    const toast = document.getElementById('dl-toast');
    const hasCookie = (name) => document.cookie.split('; ').some(c => c.startsWith(name + '='));
    const clearCookie = (name) => document.cookie = name + '=; Max-Age=0; path=/';

    if (!resultsList) return;

    resultsList.addEventListener('click', (e) => {
      const a = e.target.closest('a.dl-link[data-dltoken]');
      if (!a) return;
      const token = a.dataset.dltoken;
      const cookieName = 'dl_' + token;

      let tries = 0;
      const maxTries = 60; // 30秒
      const iv = setInterval(() => {
        tries++;
        if (hasCookie(cookieName)) {
          clearInterval(iv);
          clearCookie(cookieName);
          if (toast) {
            toast.textContent = 'ダウンロードが完了しました。';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4000);
          }
        }
        if (tries >= maxTries) clearInterval(iv);
      }, 500);
    });
  })();

  // ===== 圧縮後カードの削除（AJAX） =====
  (() => {
    const resultsList = document.getElementById('results-list');
    if (!resultsList) return;

    const csrf = document.querySelector('#compress-form input[name=_token]')?.value;
    const toast = document.getElementById('dl-toast'); // 流用

    resultsList.addEventListener('submit', async (e) => {
      const form = e.target.closest('form.result-delete-form');
      if (!form) return;
      e.preventDefault();

      if (!confirm('この圧縮画像を削除します。よろしいですか？')) return;

      const li = form.closest('[data-item]');
      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          body: new FormData(form),
        });
        if (!res.ok) throw new Error('削除に失敗しました');

        if (li) li.remove();
        if (toast) {
          toast.textContent = '削除しました。';
          toast.classList.remove('hidden');
          setTimeout(() => toast.classList.add('hidden'), 3000);
        }
      } catch (err) {
        alert(err.message || '削除に失敗しました');
      }
    });
  })();

  // 初期
  showCompact(); updateSummary();
})();
</script>
