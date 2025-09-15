<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- タイトル（子で @section('title', '...') を推奨） --}}
    <title>@yield('title', config('app.name'))</title>

    {{-- ▼ ここで一度だけ本文を決定して全メタに使い回す（DRY） --}}
    @php
      $desc  = trim($__env->yieldContent('meta_description', 'Tools Hubは、文字数カウントなどの無料ツールを提供します。'));
      $title = trim($__env->yieldContent('title', config('app.name')));
      $url   = request()->has('page') ? url()->full() : url()->current();
      $ogImg = url(asset('tools_hub_logo.png'));
    @endphp

    {{-- HTML 用 meta --}}
    <meta name="description" content="{{ $desc }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- robots は通常 index。必要ページは子で @section('robots') を定義 --}}
    @hasSection('robots')
      @yield('robots')
    @endif

    {{-- OGP/Twitter（デフォルト。子で上書き可） --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name', 'Tools Hub') }}">
    <meta property="og:title" content="@yield('og_title', $title)">
    <meta property="og:description" content="@yield('og_description', $desc)">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:image" content="@yield('og_image', $ogImg)">
    <meta name="twitter:image" content="@yield('twitter_image', View::getSections()['og_image'] ?? $ogImg)">
    <meta name="twitter:card" content="summary_large_image">

    {{-- GSC: 所有権メタ（設定があれば出す） --}}
    @if($gsc = config('services.gsc.verification'))
      <meta name="google-site-verification" content="{{ $gsc }}">
    @endif

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- 公開側のみ：広告スクリプト --}}
    @unless (request()->routeIs('admin.*'))
      @include('partials.ads.script')
    @endunless

    {{-- 解析：本番＆公開ページ＆no_analytics未指定のとき --}}
    @php
      $isPublic = !request()->routeIs('admin.*') && !request()->is('admin/*');
      $analyticsEnabled = app()->environment('production') && env('ANALYTICS_ENABLED', false);
    @endphp
    @if ($analyticsEnabled && $isPublic && !View::hasSection('no_analytics'))
      @include('partials.ads.ga')
      @if($clarityId = config('services.clarity.project_id'))
        <script>
          (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
          })(window, document, "clarity", "script", "{{ $clarityId }}");
        </script>
      @endif
    @endif

    {{-- Favicon / Apple Touch Icon --}}
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('tools_hub_logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('tools_hub_logo.png') }}"/>

    {{-- canonical：?page= だけ許容、他クエリは正規化で除外 --}}
    <link rel="canonical" href="{{ $url }}">

    {{-- ページ毎に<head>へ追記したい時用 --}}
    @stack('head')

    {{-- ページ毎のJSON-LD差し込み口（script application/ld+json） --}}
    @stack('jsonld')

    {{-- 既存メタ挿入口（任意のメタを子で追加したい場合） --}}
    @yield('meta')
</head>
<body class="min-h-dvh bg-white text-gray-900">
    {{-- ヘッダー --}}
    @include('layouts.navigation')

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
