@php
  $enabled   = (bool) config('ads.enabled', false);
  $network   = config('ads.network', 'adsense'); // 'adsense' or 'gam'
  $isPublic  = !request()->routeIs('admin.*') && !request()->is('admin/*');

  // 審査中は true でOK。運用開始後は ads.require_consent と cookie で制御
  $hasConsent = config('ads.require_consent', false)
      ? request()->cookie('ad_consent') === '1'
      : true;

  $client = trim((string) config('ads.adsense.client')); // config/ads.php と統一
@endphp

@once
  {{-- 審査中は environment 条件を外す/緩める --}}
  @if ($isPublic && $enabled && $hasConsent)
    {{-- AdSense --}}
    @if ($network === 'adsense' && $client)
      <script async
        src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ urlencode($client) }}"
        crossorigin="anonymous"></script>
      <script>window.adsbygoogle = window.adsbygoogle || [];</script>
    @endif

    {{-- Google Ad Manager (GPT) --}}
    @if ($network === 'gam')
      <script>window.googletag = window.googletag || {cmd: []};</script>
      <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
    @endif
  @endif
@endonce
