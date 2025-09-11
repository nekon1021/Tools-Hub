@if (config('services.ga.enabled') && config('services.ga.measurement_id'))
    <!-- GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga.measurement_id') }}"></script>
    <script>
      // 基本初期化
      window.dataLayer = window.dataLayer || [];
      function gtag(){ dataLayer.push(arguments); }
      gtag('js', new Date());
      gtag('config', '{{ config('services.ga.measurement_id') }}');

      // DebugView: URLに ?debug_mode=1 があればイベントへ自動付与
      (function () {
        const isDebug = new URLSearchParams(location.search).has('debug_mode');

        // どこからでも呼べるイベント送信用の薄いヘルパ
        window.gaEvent = function(name, params) {
          if (typeof gtag !== 'function') return;
          params = params || {};
          if (isDebug) params.debug_mode = true; // DebugViewで見やすく
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
@endif
