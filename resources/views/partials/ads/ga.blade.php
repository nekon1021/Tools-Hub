@once
  <!-- GA4 base -->
  <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga.measurement_id') }}"></script>
  <script>
    // 同意モード（必要なら有効化して値を調整）
    // gtag('consent', 'default', {
    //   ad_storage: 'denied',
    //   ad_user_data: 'denied',
    //   ad_personalization: 'denied',
    //   analytics_storage: 'granted'
    // });

    // 基本初期化
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }
    gtag('js', new Date());

    // ページビュー
    gtag('config', '{{ config('services.ga.measurement_id') }}');

    // DebugView: URLに ?debug_mode=1 があればイベントへ自動付与
    (function () {
      const isDebug = new URLSearchParams(location.search).has('debug_mode');

      // どこからでも呼べるイベント送信用の薄いヘルパ
      window.gaEvent = function(name, params) {
        if (typeof gtag !== 'function') return;
        params = params || {};
        if (isDebug) params.debug_mode = true;
        gtag('event', name, params);
      };

      // data属性での汎用トラッキング（ID不要・置くだけ）
      document.addEventListener('click', function(e) {
        const el = e.target.closest('[data-gtag-event]');
        if (!el) return;

        const evName = el.getAttribute('data-gtag-event');
        let params = {};
        const raw = el.getAttribute('data-gtag-params');

        if (raw && raw.trim().length) {
          try { params = JSON.parse(raw); } catch (_) { /* 壊れてたら空で送る */ }
        }
        window.gaEvent(evName, params);
      }, { passive: true });
    })();
  </script>
@endonce
