{{-- resources/views/tool/index.blade.php --}}
@extends('layouts.app')

@section('title', '文字数カウント｜' . config('app.name'))
@section('meta')
  <meta name="description" content="改行やスペースを含む文字数をカウントできる無料ツール。小説・ブログ・SNS に便利です。">
@endsection

@section('content')
  {{-- 単一カラム：中央にツール本体だけ --}}
  <div class="mx-auto max-w-5xl px-4">
    <div class="max-w-2xl mx-auto w-full space-y-6">
      <h1 class="text-2xl font-bold">文字数カウント</h1>

      {{-- 上部広告（?dummy=1 でダミー出し） --}}
      {{-- <x-ad.slot id="tool-top" class="my-4" /> --}}
      {{-- @includeIf('partials.ads.tool-top') --}}

      @if ($errors->any())
        <div class="p-3 bg-red-100 text-red-700 rounded text-sm">
          <ul class="list-disc ml-5">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      @php($r = session('count_result'))
      @php($hasResult = $r !== null)

      <form id="count_form" method="POST" action="{{ route('tools.charcount.run') }}" class="space-y-3">
        @csrf
        <x-textarea
          name="text"
          maxlength="20000"
          placeholder="文字を入力または貼り付けて下さい。"
          class="relative z-10 bg-transparent p-3 font-mono text-base leading-6"
          autofocus
        />
      </form>

      <div class="flex justify-center gap-4">
        <x-primary-button form="count_form" type="submit">カウント</x-primary-button>
        <form method="POST" action="{{ route('tools.charcount.reset') }}">
          @csrf
          <x-secondary-button type="submit" confirm>リセット</x-secondary-button>
        </form>
        <x-checkbox id="live-toggle" checked="true" label="リアルタイムでカウント" />
      </div>

      <section
        id="liveCount"
        class="grid gap-2 rounded p-4 md:grid-cols-2 place-items-center text-center"
        data-server-chars="{{ $r['chars'] ?? 0 }}"
        data-server-chars-no-ln="{{ $r['chars_no_ln'] ?? 0 }}"
        data-server-chars-no-ws="{{ $r['chars_no_ws'] ?? 0 }}"
        data-server-lines="{{ $r['lines'] ?? 0 }}"
        data-server-whitespace="{{ $r['whitespace'] ?? 0 }}"
      >
        <div>
          <div class="text-gray-500 text-sm">文字数</div>
          <div id="live-chars" class="text-2xl font-bold" aria-live="polite">{{ $r['chars'] ?? 0 }}</div>
        </div>
        <div>
          <div class="text-gray-500 text-sm">文字数（改行抜き）</div>
          <div id="live-chars_no_ln" class="text-2xl font-bold" aria-live="polite">{{ $r['chars_no_ln'] ?? 0 }}</div>
        </div>
        <div>
          <div class="text-gray-500 text-sm">文字数（スペースと改行抜き）</div>
          <div id="live-chars_no_ws" class="text-2xl font-bold" aria-live="polite">{{ $r['chars_no_ws'] ?? 0 }}</div>
        </div>
        <div>
          <div class="text-gray-500 text-sm">行数</div>
          <div id="live-lines" class="text-2xl font-bold" aria-live="polite">{{ $r['lines'] ?? 0 }}</div>
        </div>
        <div>
          <div class="text-gray-500 text-sm">スペース数（全角・半角を含む）</div>
          <div id="live-ws-total" class="text-2xl font-bold" aria-live="polite">{{ $r['whitespace'] ?? 0 }}</div>
        </div>
      </section>

      {{-- ▼ 追加：ツール中段広告（?dummy=1 でダミー可） --}}
      {{-- <x-ad.slot id="tool-mid" class="my-8" /> --}}
      {{-- パーシャル派: @includeIf('partials.ads.tool-mid') --}}

      <section class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 space-y-3">
        <h2 class="font-semibold text-gray-800">このツールについて</h2>
        <p>小説・ブログ・SNS 投稿などの文字数チェックにご利用いただけます。<br>入力内容は保存されず、すべてブラウザ上で処理されるため安心してお使いください。</p>
       <dl class="space-y-3 ml-5">
        <div>
          <dt class="font-semibold">文字数</dt>
          <dd class="ml-6">すべての文字を1文字として数えます。空白（全角・半角スペースやタブ）や改行も含めてカウントします。</dd>
        </div>
        <div>
          <dt class="font-semibold">文字数（改行抜き）</dt>
          <dd class="ml-6">改行を除外してカウントします。空白（全角・半角スペースやタブ）は含めます。</dd>
        </div>
        <div>
          <dt class="font-semibold">文字数（スペースと改行抜き）</dt>
          <dd class="ml-6">空白（全角・半角スペースやタブ）と改行を除外し、文字だけをカウントします。</dd>
        </div>
        <div>
          <dt class="font-semibold">行数</dt>
          <dd class="ml-6">テキストを改行で区切り、行の数をカウントします。</dd>
        </div>
      </dl>
      </section>

      {{-- 下部広告 --}}
      {{-- <x-ad.slot id="tool-bottom" class="my-10" /> --}}
      {{-- @includeIf('partials.ads.tool-bottom') --}}
    </div>
  </div>

  <script>
  (() => {
    const ta      = document.querySelector('#count_form textarea[name="text"]');
    const toggle  = document.getElementById('live-toggle');
    const elChars = document.getElementById('live-chars');
    const elCharsNoLn = document.getElementById('live-chars_no_ln');
    const elCharsNoWs = document.getElementById('live-chars_no_ws');
    const elLines = document.getElementById('live-lines');
    const box     = document.getElementById('liveCount');
    const elWsTotal = document.getElementById('live-ws-total');
    if (!ta || !toggle || !elChars || !elCharsNoLn || !elCharsNoWs || !elLines || !box) return;

    const seg = globalThis.Intl?.Segmenter ? new Intl.Segmenter('ja', { granularity: 'grapheme' }) : null;
    const gLength = (s) => seg ? [...seg.segment(s)].length : [...s].length;

    const server = {
      chars: Number(box.dataset.serverChars ?? 0),
      charsNoLn: Number(box.dataset.serverCharsNoLn ?? 0),
      charsNoWs: Number(box.dataset.serverCharsNoWs ?? 0),
      lines: Number(box.dataset.serverLines ?? 0),
      whitespace: Number(box.dataset.serverWhitespace ?? 0),
    };

    const normalize = (s) => s.replace(/\r\n?/g, '\n');
    const stripWhitespace = (s) => s.replace(/\s/gu, '');
    const count = (s) => {
      const t = normalize(s);
      const chars = gLength(t);
      const noWs = stripWhitespace(t);
      const charsNoLn = gLength(t.replace(/\n/g, ''));
      const charsNoWs = gLength(noWs);
      const lines = t.length === 0 ? 0 : (t.match(/\n/g)?.length ?? 0) + 1;
      const wsTotal = (t.match(/[ \u3000]/g)?.length ?? 0);
      return { chars, charsNoLn, charsNoWs, lines, wsTotal };
    };

    const update = () => {
      if (!toggle.checked) return;
      const { chars, charsNoLn, charsNoWs, lines, wsTotal } = count(ta.value);
      elChars.textContent = chars;
      elCharsNoLn.textContent = charsNoLn;
      elCharsNoWs.textContent = charsNoWs;
      elLines.textContent = lines;
      if (elWsTotal) elWsTotal.textContent = wsTotal;
    };

    if (toggle.checked) update();
    ta.addEventListener('input', update);
    toggle.addEventListener('change', () => {
      if (toggle.checked) update();
      else {
        elChars.textContent = server.chars;
        elCharsNoLn.textContent = server.charsNoLn;
        elCharsNoWs.textContent = server.charsNoWs;
        elLines.textContent = server.lines;
        if (elWsTotal) elWsTotal.textContent = server.whitespace;
      }
    });
  })();
  </script>
@endsection
