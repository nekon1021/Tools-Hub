@props([
    'post' => null,
    'fallback' => null,
    'fallbackUrl' => null,
    'label' => null,
])

@php
    $target = $post ?? $fallback;
    $url = $target
        ? route('public.posts.show', $target->slug)
        : ($fallbackUrl ?? route('public.posts.index'));

    // 表示文言は label > 記事タイトル > 既定文言 の優先度
    $linkTitle = $label ?: ($target->title ?? '人気記事をもっと見る');
@endphp

<section class="mt-10 p-4 bg-blue-50 border rounded-lg">
    <p class="text-sm text-blue-700 mb-1">次に読むおすすめ</p>
    <a href="{{ $url }}" class="text-blue-800 font-semibold underline"
       data-gtag-event="next_read_click"
       data-gtag-label="{{ $target->slug ?? parse_url($url, PHP_URL_PATH) }}">
        {{ $linkTitle }}
    </a>
</section>