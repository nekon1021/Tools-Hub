@php
  /** @var array<int,array{name:string,url?:string,current?:bool}> $items */
  $items = $items ?? [];
  // 最後の要素をカレント扱いに（current未指定なら）
  if ($items) {
    $last = array_key_last($items);
    if (!isset($items[$last]['current'])) $items[$last]['current'] = true;
  }
@endphp

<nav aria-label="パンくず" class="text-sm text-gray-500">
  <ol class="flex flex-wrap items-center gap-1" itemscope itemtype="https://schema.org/BreadcrumbList">
    @foreach ($items as $idx => $it)
      @if ($idx > 0)
        <li aria-hidden="true" class="px-1 select-none">›</li>
      @endif

      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"
          @if(!empty($it['current'])) aria-current="page" @endif>
        @if(!empty($it['url']) && empty($it['current']))
          <a href="{{ $it['url'] }}" itemprop="item"
             class="inline-flex items-center px-1 -mx-1 rounded-sm
                    text-gray-600 hover:text-gray-900 hover:underline underline-offset-2
                    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition">
            <span itemprop="name">{{ $it['name'] }}</span>
          </a>
        @else
          <span class="px-1 -mx-1 rounded-sm text-gray-700" itemprop="name">{{ $it['name'] }}</span>
        @endif
        <meta itemprop="position" content="{{ $idx + 1 }}" />
      </li>
    @endforeach
  </ol>
</nav>
