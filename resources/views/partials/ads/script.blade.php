@php $network = config('ads.network', 'adsense'); @endphp

{{-- AdSense --}}
@if($network === 'adsense' && config('ads.adsense.client'))
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('ads.adsense.client') }}" crossorigin="anonymous"></script>
@endif

{{-- Google Ad Manager (GPT) --}}
@if($network === 'gam')
  <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
  <script>window.__gptQueue = window.__gptQueue || [];</script>
@endif
