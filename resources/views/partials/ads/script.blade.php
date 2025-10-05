@php
  $enabled    = (bool) config('ads.enabled', false);
  $network    = config('ads.network', 'adsense'); // 'adsense' or 'gam'
  $isPublic   = !request()->routeIs('admin.*') && !request()->is('admin/*');

  // 同意管理をまだ入れていないなら true にしておいてOK
  $hasConsent = request()->cookie('ad_consent') === '1' ?: true;
@endphp

@once
  @if (app()->environment('production') && $isPublic && $enabled && $hasConsent)
    {{-- AdSense --}}
    @if ($network === 'adsense' && ($client = config('ads.adsense.client')))
      <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ urlencode($client) }}" crossorigin="anonymous"></script>
      <script>window.adsbygoogle = window.adsbygoogle || [];</script>
    @endif

    {{-- Google Ad Manager (GPT) --}}
    @if ($network === 'gam')
      <script>window.googletag = window.googletag || {cmd: []};</script>
      <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
    @endif
  @endif
@endonce
