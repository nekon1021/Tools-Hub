@props(['id', 'class' => ''])

@php
  $enabled    = (bool) config('ads.enabled', false);
  $allowDummy = (bool) config('ads.allow_dummy_query', true);
  $forceDummy = $allowDummy && request()->boolean('dummy');
  $network    = config('ads.network', 'adsense');

  // AdSense
  $client     = config('ads.adsense.client');
  $adSlot     = config('ads.slots.' . $id, $id);

  // GAM
  $gamUnit    = config('ads.gam.units.' . $id);
  $gamSizes   = config('ads.gam.sizes.' . $id, [[336,280],[300,250]]);
  $domId      = 'gpt-' . $id . '-' . uniqid();
@endphp

<div class="{{ $class }}" role="complementary" aria-label="ad">
  @if($enabled && !$forceDummy)
    @if($network === 'adsense')
      <ins class="adsbygoogle"
           style="display:block"
           data-ad-client="{{ $client }}"
           data-ad-slot="{{ $adSlot }}"
           data-ad-format="auto"
           data-full-width-responsive="true"></ins>
      <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>

    @elseif($network === 'gam' && $gamUnit)
      <div id="{{ $domId }}">
        {{-- サイズ確保（CLS対策の簡易高さ。必要に応じてCSSで調整） --}}
        <div style="min-height:250px"></div>
      </div>
      <script>
        window.googletag = window.googletag || {cmd: []};
        googletag.cmd.push(function() {
          var slot = googletag.defineSlot(
            {!! json_encode($gamUnit) !!},
            {!! json_encode($gamSizes) !!},
            {!! json_encode($domId) !!}
          ).addService(googletag.pubads());
          googletag.display({!! json_encode($domId) !!});
        });
      </script>
    @else
      {{-- ネットワーク未設定/不足時はダミー --}}
      <div class="h-[250px] w-full rounded border border-gray-300 bg-gray-100
                  flex items-center justify-center text-gray-500">
        AD: {{ $id }}
      </div>
    @endif
  @else
    {{-- ダミー（?dummy=1 でも表示） --}}
    <div class="h-[250px] w-full rounded border border-gray-300 bg-gray-100
                flex items-center justify-center text-gray-500">
      AD: {{ $id }}
    </div>
  @endif
</div>
