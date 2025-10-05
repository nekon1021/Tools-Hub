@props(['posts' => collect()])

@if($posts->count())
<section class="mt-10">
  <h2 class="text-xl font-bold mb-3">関連記事</h2>
  <ul class="grid sm:grid-cols-2 gap-3">
    @foreach($posts as $p)
      <li class="border rounded p-3 hover:bg-gray-50">
        <a href="{{ route('public.posts.show', $p->slug) }}" class="font-semibold hover:underline">
          {{ $p->title }}
        </a>
        @if($p->published_at)
          <div class="text-xs text-gray-500 mt-1">{{ $p->published_at->format('Y-m-d') }}</div>
        @endif
      </li>
    @endforeach
  </ul>

  @push('scripts')
    <script>
        document.addEventListener('click', function(e){
        const a = e.target.closest('a[data-gtag-event]');
        if(!a || typeof gtag !== 'function') return;
        gtag('event', a.dataset.gtagEvent, { label: a.dataset.gtagLabel || a.href });
        }, {capture:true});
    </script>
  @endpush
  
</section>
@endif
