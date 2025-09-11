<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', config('app.name'))</title>

    {{-- ▼ 各ページで @section('meta_description') などを定義すると上書きできます --}}
    <meta name="description" content="@yield('meta_description', 'Tools Hubは、文字数カウントなどの無料ツールを提供します。')">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- robots は通常 index。必要なページは子ビューで @section('robots') を定義して上書き --}}
    @hasSection('robots')
      @yield('robots')
    @endif

    {{-- OGP/Twitter（デフォルト値。ページ側で上書き可） --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Tools Hub">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', 'Tools Hub')))">
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', 'Tools Hubは、文字数カウントなどの無料ツールを提供します。')))">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:image" content="{{ url(asset('tools_hub_logo.png')) }}">
    <meta name="twitter:card" content="summary_large_image">
    
    {{-- GSC: 所有権メタ（常時出す） --}}
    @if($gsc = config('services.gsc.verification'))
      <meta name="google-site-verification" content="{{ $gsc }}">
    @endif

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- 公開側のみ：広告スクリプト --}}
    @unless (request()->routeIs('admin.*'))
      @include('partials.ads.script')
    @endunless

    {{-- 解析・本番のみ＆公開ページのみ＆ページ側でno_analytics未指定のとき --}}
    @php
      $isPublic = !request()->routeIs('admin.*') && !request()->is('admin/*');
      $enabled = app()->environment('production') && env('ANALYTICS_ENABLED', false);
    @endphp
    @if ($enabled && $isPublic && !View::hasSection('no_analytics'))
      {{-- GA4：パーシャルに集約 --}}
      @include('partials.ads.ga')

      {{-- Microsoft Clarity（必要な場合のみ） --}}
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

    {{-- canonical：?page= だけは保持、それ以外のクエリは正規化で落とす方針 --}}
    <link rel="canonical" href="{{ request()->has('page') ? url()->full() : url()->current() }}">

    {{-- ページ毎に<head>へ追記したい時用 --}}
    @stack('head')

    {{-- ページ毎のJSON-LD差し込み口（script application/ld+json） --}}
    @stack('jsonld')

    {{-- 既存のメタ挿入口も残す場合 --}}
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
