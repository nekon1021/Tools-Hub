@once
  @php
    $gaId = config('services.ga.measurement_id');
  @endphp
  @if(!empty($gaId))
    <!-- GA4 base -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
      // ---- Consent Mode v2: まず既定値を宣言（EEA/UK/CHに限定）----
      // EEA(27) + UK + CH のISOコード
      (function() {
        var eeaUkCh = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB','CH'];
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }

        // 既定: 広告関連は "denied"、分析のみ "granted"（※好みで変更）
        // region 指定により、EEA/UK/CH にのみ既定を適用し、日本などには影響しない
        gtag('consent', 'default', {
          'ad_storage': 'denied',
          'ad_user_data': 'denied',
          'ad_personalization': 'denied',
          'analytics_storage': 'granted',   // 分析は既定で許可（必要なら 'denied' に）
          'region': eeaUkCh
        });

        // ---- 基本初期化 ----
        gtag('js', new Date());

        // DebugView: URLに ?debug_mode=1 があれば config にも付与
        var isDebug = new URLSearchParams(location.search).has('debug_mode');

        // ページビュー（必要なら send_page_view: false にして手動送信も可）
        gtag('config', '{{ $gaId }}', { 'debug_mode': isDebug });

        // どこからでも呼べるイベント送信用の薄いヘルパ
        window.gaEvent = function(name, params) {
          params = params || {};
          if (isDebug) params.debug_mode = true;
          gtag('event', name, params);
        };

        // data属性での汎用トラッキング（ID不要・置くだけ）
        document.addEventListener('click', function(e) {
          var el = e.target.closest('[data-gtag-event]');
          if (!el) return;
          var evName = el.getAttribute('data-gtag-event');
          var params = {};
          var raw = el.getAttribute('data-gtag-params');
          if (raw && raw.trim().length) {
            try { params = JSON.parse(raw); } catch(_) {}
          }
          window.gaEvent(evName, params);
        }, { passive: true });
      })();
    </script>
  @endif
@endonce
