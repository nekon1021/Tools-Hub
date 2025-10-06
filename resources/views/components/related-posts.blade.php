{{-- resources/views/components/related-posts.blade.php --}}
@props([
  'posts' => collect(),
  'gaEvent' => 'related_post_click',
  'showDate' => true,
  'showCategory' => true,
  'limit' => null,     // 最大表示数（nullなら全件）
])

@php
  $items = $limit ? $posts->take((int)$limit) : $posts;
@endphp

@if($items->isNotEmpty())
<section {{ $attributes->merge(['class' => 'mt-10']) }} aria-labelledby="related-heading">
  <h2 id="related-heading" class="text-xl font-bold mb-3">関連記事</h2>

  <ul role="list" class="border rounded divide-y divide-gray-200">
    @foreach($items as $p)
      <li class="px-3 py-2">
        <a
          href="{{ route('public.posts.show', $p->slug) }}"
          class="font-semibold text-blue-800 underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          data-gtag-event="{{ $gaEvent }}"
          data-gtag-label="{{ $p->slug }}"
        >
          {{ $p->title }}
        </a>

        <div class="mt-0.5 text-xs text-gray-500">
          @if($showDate && $p->published_at)
            <time datetime="{{ $p->published_at->toDateString() }}">{{ $p->published_at->format('Y-m-d') }}</time>
          @endif
          @if($showCategory && $p->category)
            <span class="mx-1">・</span>
            <a href="{{ route('public.categories.posts.index', $p->category->slug) }}" class="underline hover:no-underline">
              {{ $p->category->name }}
            </a>
          @endif
        </div>
      </li>
    @endforeach
  </ul>

  @once
    @push('scripts')
      <script>
        document.addEventListener('click', function (e) {
          const a = e.target.closest('a[data-gtag-event]');
          if (!a) return;
          if (typeof window.gtag === 'function') {
            window.gtag('event', a.dataset.gtagEvent || 'related_post_click', {
              label: a.dataset.gtagLabel || a.href
            });
          }
        }, { capture: true });
      </script>
    @endpush
  @endonce
</section>
@endif
