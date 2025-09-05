{{-- resources/views/components/authentication-card-logo.blade.php --}}
@props(['class' => 'h-12 w-auto'])

<img
  src="{{ asset('tools_hub_logo.png') }}"
  alt="{{ \Illuminate\Support\Str::headline(config('app.name', 'Tools Hub')) }}"
  {{ $attributes->merge(['class' => $class]) }}
  width="96" height="96"
  decoding="async" fetchpriority="high"
/>
