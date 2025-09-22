@php
  $enabled = (bool) config('ads.enabled', false);
  $network = config('ads.network', 'adsense');
@endphp

@if(app()->environment('production') && $enabled)
  {{-- AdSense --}}
  @if($network === 'adsense' && ($client = config('ads.adsense.client')))
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $client }}" crossorigin="anonymous"></script>
  @endif

  {{-- Google Ad Manager (GPT) --}}
  @if($network === 'gam')
    <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
    <script>window.__gptQueue = window.__gptQueue || [];</script>
  @endif
@endif
