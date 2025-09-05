@php
  $fullTitle   = $component->fullTitle();
  $desc        = $component->description160();
  $canonical   = $component->resolvedCanonical();
  $siteName    = config('seo.site_name', config('app.name'));
  $twitterSite = config('seo.twitter_site'); // 例: '@your_account'
  $defaultOg   = config('seo.default_og_image'); // 例: '/img/ogp.png'
  $ogImageUrl  = $ogImage ?? ($defaultOg ? asset($defaultOg) : null);
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ $canonical }}"/>

@if($noindex)
  <meta name="robots" content="noindex, nofollow, noarchive">
@endif

{{-- Pagination hints（任意） --}}
@if(!empty($prev)) <link rel="prev" href="{{ $prev }}"> @endif
@if(!empty($next)) <link rel="next" href="{{ $next }}"> @endif

{{-- Open Graph --}}
<meta property="og:title" content="{{ $title ?: $siteName }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $siteName }}">
@if($ogImageUrl)
  <meta property="og:image" content="{{ $ogImageUrl }}">
  @if($ogImageAlt)<meta property="og:image:alt" content="{{ $ogImageAlt }}">@endif
@endif

{{-- Article 拡張（記事詳細のとき） --}}
@if($ogType === 'article')
  @if($publishedTime)<meta property="article:published_time" content="{{ $publishedTime }}">@endif
  @if($modifiedTime)<meta property="article:modified_time" content="{{ $modifiedTime }}">@endif
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
@if($twitterSite)<meta name="twitter:site" content="{{ $twitterSite }}">@endif
