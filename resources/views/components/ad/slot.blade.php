@props([
  'id',                 // 必須: スロット識別子（config側のキー）
  'class' => '',
])

@php
  $enabled    = (bool) config('ads.enabled', false);
  $allowDummy = (bool) config('ads.allow_dummy_query', true);
  // 本番では ?dummy=1 が効かないように
  $forceDummy = $allowDummy && !app()->environment('production') && request()->boolean('dummy');

  $network    = config('ads.network', 'adsense');

  // AdSense
  $client     = (string) config('ads.adsense.client');          // 例: "ca-pub-XXXX"
  $adSlot     = (string) config("ads.slots.$id", $id);          // スロットID

  // GAM (Google Ad Manager / GPT)
  $gamUnit    = config("ads.gam.units.$id");                    // 例: "/1234567/example"
  $gamSizes   = (array)  config("ads.gam.sizes.$id", [[336,280],[300,250]]);
  $domId      = 'gpt-' . $id . '-' . uniqid();

  // CLS対策: 最大サイズの高さを確保
  $maxH = 0;
  foreach ($gamSizes as $sz) {
      if (is_array($sz) && count($sz) === 2) {
          $maxH = max($maxH, (int) $sz[1]);
      }
  }
  if ($maxH <= 0) $maxH = 250;

  $isProd = app()->environment('production');
@endphp

<div {{ $attributes->class($class)->merge(['role' => 'complementary', 'aria-label' => 'ad']) }}>
  @if($enabled && !$forceDummy)
    @if($network === 'adsense' && $client && $adSlot)
      <ins class="adsbygoogle"
           style="display:block;min-height:250px"
           data-ad-client="{{ $client }}"
           data-ad-slot="{{ $adSlot }}"
           data-ad-format="auto"
           data-full-width-responsive="true"
           @unless($isProd) data-adtest="on" @endunless></ins>
      <script>
        (function(){
          try { (window.adsbygoogle = window.adsbygoogle || []).push({}); }
          catch(e) { /* adblock等で失敗しても無視 */ }
        })();
      </script>

    @elseif($network === 'gam' && $gamUnit)
      <div id="{{ $domId }}">
        <div style="min-height: {{ $maxH }}px"></div>
      </div>
      <script>
        (function(){
          window.googletag = window.googletag || {cmd: []};
          googletag.cmd.push(function() {
            // 一度だけ初期化（多重 enableServices を防止）
            if (!window.__gptInitialized) {
              try {
                googletag.pubads().enableSingleRequest();
                googletag.pubads().collapseEmptyDivs();
                googletag.enableServices();
                window.__gptInitialized = true;
              } catch (e) {}
            }
            try {
              var slot = googletag.defineSlot(
                @json($gamUnit),
                @json($gamSizes),
                @json($domId)
              );
              if (slot) { slot.addService(googletag.pubads()); }
              googletag.display(@json($domId));
            } catch (e) {}
          });
        })();
      </script>

    @else
      {{-- ネットワーク未設定/情報不足 → ダミー枠でフォールバック --}}
      <div class="h-[250px] w-full rounded border border-gray-300 bg-gray-100
                  flex items-center justify-center text-gray-500">
        AD: {{ $id }}
      </div>
    @endif
  @else
    {{-- 明示ダミー（?dummy=1 か、広告無効時） --}}
    <div class="h-[250px] w-full rounded border border-gray-300 bg-gray-100
                flex items-center justify-center text-gray-500">
      AD: {{ $id }}
    </div>
  @endif
</div>
