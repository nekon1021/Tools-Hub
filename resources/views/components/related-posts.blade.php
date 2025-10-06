@props([
  'posts' => collect(),
  // 'card'（画像あり） or 'list'（テキストのみ）
  'variant' => 'card',
  // 3以上でLG時3列。それ以外は1→2列（SM）固定
  'cols' => 3,
  // GAイベント名（GTM経由でもOK）
  'gaEvent' => 'related_post_click',
])

@if($posts->isNotEmpty())
<section {{ $attributes->merge(['class' => 'mt-10']) }} aria-labelledby="related-heading">
  <h2 id="related-heading" class="text-xl font-bold mb-3">関連記事</h2>

  @php

    // グリッド列数
    $grid = 'grid grid-cols-1 gap-4 sm:grid-cols-2';
    if ((int)$cols >= 3) $grid .= ' lg:grid-cols-3';

    // 小さめのダミー（16:9）
    $dummyImg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode(
      "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1600 900'>
        <rect width='1600' height='900' fill='#f3f4f6'/>
        <text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle'
              font-family='system-ui, -apple-system, Segoe UI, Roboto'
              font-size='44' fill='#9ca3af'>NO IMAGE</text>
      </svg>"
    );

    // サムネURL解決（http/https/data: そのまま、storage相対→公開URL、無ければダミー）
    $resolveImg = function ($raw) use ($dummyImg) {
      $raw = trim((string)($raw ?? ''));
      if ($raw === '') return $dummyImg;
      if (Str::startsWith($raw, ['http://','https://','data:'])) return $raw;

      $p = ltrim($raw, '/');
      if (Str::startsWith($p, 'storage/')) $p = Str::after($p, 'storage/');
      if (Str::startsWith($p, 'public/'))  $p = Str::after($p, 'public/');
      return Storage::disk('public')->exists($p) ? Storage::disk('public')->url($p) : $dummyImg;
    };
  @endphp

  @if($variant === 'card')
    <ul role="list" class="{{ $grid }}">
      @foreach($posts as $p)
        @php
          $img = $resolveImg($p->og_image_path ?? null);
        @endphp
        <li>
          <a
            href="{{ route('public.posts.show', $p->slug) }}"
            class="group block overflow-hidden rounded-lg border bg-white hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            data-gtag-event="{{ $gaEvent }}"
            data-gtag-label="{{ $p->slug }}"
          >
            <figure class="relative">
              <img
                src="{{ $img }}"
                alt="{{ $p->title }}"
                class="w-full aspect-[16/9] object-cover"
                loading="lazy" decoding="async"
              />
              <div class="absolute inset-0 ring-1 ring-inset ring-black/5" aria-hidden="true"></div>
            </figure>
            <div class="p-3">
              <h3 class="text-base font-semibold leading-snug group-hover:underline line-clamp-2">
                {{ $p->title }}
              </h3>
              <div class="mt-1 text-xs text-gray-500">
                {{ optional($p->published_at)->format('Y-m-d') }}
                @if($p->category) ・{{ $p->category->name }} @endif
              </div>
              @if(!empty($p->lead))
                <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                  {{ Str::limit(strip_tags($p->lead), 80) }}
                </p>
              @endif
            </div>
          </a>
        </li>
      @endforeach
    </ul>
  @else
    {{-- シンプル（画像なし・コンパクト） --}}
    <ul role="list" class="space-y-2">
      @foreach($posts as $p)
        <li class="border rounded px-3 py-2">
          <a
            href="{{ route('public.posts.show', $p->slug) }}"
            class="group inline-flex items-baseline gap-2 text-blue-800 font-semibold underline"
            data-gtag-event="{{ $gaEvent }}"
            data-gtag-label="{{ $p->slug }}"
          >
            <span class="line-clamp-1 group-hover:no-underline">{{ $p->title }}</span>
            @if($p->published_at)
              <time datetime="{{ $p->published_at->toDateString() }}" class="text-xs text-gray-500">
                {{ $p->published_at->format('Y-m-d') }}
              </time>
            @endif
          </a>
        </li>
      @endforeach
    </ul>
  @endif

  {{-- gtagハンドラを重複挿入しない --}}
  @once
    @push('scripts')
      <script>
        document.addEventListener('click', function (e) {
          const a = e.target.closest('a[data-gtag-event]');
          if (!a) return;
          const fn = window.gtag;
          if (typeof fn === 'function') {
            fn('event', a.dataset.gtagEvent || 'related_post_click', {
              label: a.dataset.gtagLabel || a.href
            });
          }
        }, { capture: true });
      </script>
    @endpush
  @endonce
</section>
@endif
