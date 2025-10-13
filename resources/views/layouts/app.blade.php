<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  {{-- タイトル（子で @section('title', '...') を推奨） --}}
  <title>@yield('title', config('app.name'))</title>

  {{-- ▼ 一度だけ本文を決定して全メタに使い回す（DRY） --}}
  @php
    $desc  = trim($__env->yieldContent('meta_description', 'Tools Hubは、文字数カウントなどの無料ツールを提供します。'));
    $title = trim($__env->yieldContent('title', config('app.name')));

    // 正規URL：nullでも落ちないフォールバック
    $base = rtrim((string) (config('app.canonical_url') ?? config('app.url') ?? 'http://localhost'), '/');
    $path = request()->getPathInfo();   // /about 等
    $allowQuery = ['page'];             // 許可クエリ（必要に応じて追加）
    $qs = collect(request()->query())
          ->filter(fn($v,$k)=>in_array($k,$allowQuery, true) && $v!=='')
          ->map(fn($v,$k)=>$k.'='.urlencode($v))
          ->implode('&');
    $canonical = $base . $path . ($qs ? ('?'.$qs) : '');

    // 画像と OGP
    $ogImg = url(asset('tools_hub_logo.png'));
  @endphp

  {{-- HTML 用 meta --}}
  <meta name="description" content="{{ $desc }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- robots は通常 index。必要ページは子で @section('robots') を定義 --}}
  @hasSection('robots')
    @yield('robots')
  @else
    <meta name="robots" content="index,follow">
  @endif

  {{-- OGP/Twitter（デフォルト。子で上書き可） --}}
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:site_name" content="{{ config('app.name', 'Tools Hub') }}">
  <meta property="og:title" content="@yield('og_title', $title)">
  <meta property="og:description" content="@yield('og_description', $desc)">
  <meta property="og:url" content="{{ $canonical }}">
  <meta property="og:image" content="@yield('og_image', $ogImg)">
  @php $sections = View::getSections(); @endphp
  <meta name="twitter:image" content="@yield('twitter_image', $sections['og_image'] ?? $ogImg)">
  <meta name="twitter:card" content="summary_large_image">

  {{-- canonical（1 本に統一） --}}
  <link rel="canonical" href="{{ $canonical }}">

  {{-- GSC: 所有権メタ（設定があれば出す） --}}
  @if($gsc = config('services.gsc.verification'))
    <meta name="google-site-verification" content="{{ $gsc }}">
  @endif

  {{-- ★ ここで先に条件用のフラグを定義（この下の preconnect で使う） --}}
  @php
    $isPublic = !request()->routeIs('admin.*') && !request()->is('admin/*');
    $analyticsEnabled = app()->environment('production') && env('ANALYTICS_ENABLED', false);
    $clarityId = config('services.clarity.project_id');

    $adsEnabled  = (bool) config('ads.enabled', false);
    $adsNetwork  = config('ads.network', 'adsense'); // 'adsense' or 'gam'
    $hasConsent  = request()->cookie('ad_consent') === '1';
  @endphp

  {{-- ★ 外部ドメインの接続を先に温める（最初の外部ロードより前に置く） --}}
  @unless (request()->routeIs('admin.*'))
    {{-- 広告ネットワーク（導入予定があるなら温めてOK） --}}
    @if ($isPublic && $adsEnabled && $hasConsent)
      @if ($adsNetwork === 'adsense')
        <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
        <link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
        <link rel="preconnect" href="https://tpc.googlesyndication.com" crossorigin>
        <link rel="dns-prefetch" href="//tpc.googlesyndication.com">
      @elseif ($adsNetwork === 'gam')
        <link rel="preconnect" href="https://securepubads.g.doubleclick.net" crossorigin>
        <link rel="dns-prefetch" href="//securepubads.g.doubleclick.net">
        <link rel="preconnect" href="https://tpc.googlesyndication.com" crossorigin>
        <link rel="dns-prefetch" href="//tpc.googlesyndication.com">
      @endif
    @endif

    {{-- Analytics/Tag Manager/Clarity は有効時のみ温める --}}
    @if ($analyticsEnabled && $isPublic && !View::hasSection('no_analytics'))
      <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
      <link rel="dns-prefetch" href="//www.googletagmanager.com">
      <link rel="preconnect" href="https://www.google-analytics.com" crossorigin>
      <link rel="dns-prefetch" href="//www.google-analytics.com">
      @if ($clarityId)
        <link rel="preconnect" href="https://www.clarity.ms" crossorigin>
        <link rel="dns-prefetch" href="//www.clarity.ms">
      @endif
    @endif
  @endunless

  {{-- Favicon / Apple Touch Icon --}}
  <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('tools_hub_logo.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('tools_hub_logo.png') }}"/>

  {{-- Vite（外部接続の温めの後に読み込む） --}}
  @vite(entrypoints: ['resources/css/app.css', 'resources/js/app.js'])

  {{-- 公開側のみ：広告スクリプト --}}
  @unless (request()->routeIs('admin.*'))
    @includeIf('partials.ads.consent')
    @include('partials.ads.script')
  @endunless

  {{-- 解析：本番＆公開ページ＆no_analytics未指定のとき --}}
  @if ($analyticsEnabled && $isPublic && !View::hasSection('no_analytics'))
    @include('partials.ads.ga')
    @if ($clarityId)
      <script>
        (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
          t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
          y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "{{ $clarityId }}");
      </script>
    @endif
  @endif

  {{-- ページ毎に<head>へ追記したい時用 --}}
  @stack('head')

  {{-- ページ毎のJSON-LD差し込み口（script application/ld+json） --}}
  @stack('jsonld')

  {{-- 既存メタ挿入口（任意のメタを子で追加したい場合） --}}
  @yield('meta')
</head>
<body class="min-h-dvh bg-white text-gray-900">
  @php
    // 子ビューから明示指定があれば最優先（例: @section('theme','image')）
    $themeFromChild = trim($__env->yieldContent('theme', ''));

    // ルート名で自動判定（Laravelの routeIs を使用）
    $themeAuto = match (true) {
      request()->routeIs('tools.image.*')     => 'image',
      request()->routeIs('tools.charcount*')  => 'text',
      default                                 => 'sepia',
    };

    $raw = $themeFromChild !== '' ? $themeFromChild : $themeAuto;

    // 予期しない値を弾く（XSS/typo対策）
    $allowed = ['text','image','dev','biz','sepia'];
    $theme   = in_array($raw, $allowed, true) ? $raw : 'sepia';
  @endphp


  {{-- ヘッダー --}}
  @include('layouts.navigation', ['theme' => $theme])

  {{-- メインコンテンツ --}}
  <main class="border">
    <div class="mx-auto max-w-5xl p-4">
      <x-flash />
      @yield('content')
    </div>
  </main>

  {{-- フッター --}}
  @include('layouts.footer')

  @stack('scripts')
</body>
</html>
