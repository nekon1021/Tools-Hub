@props([
  'name',
  'id' => null,
  'label' => null,
  'placeholder' => null,
  'rows' => 10,
  'required' => false,
])

@php
    // idが指定されていなければ name から生成（[] は id に使えないので置換）
    $fieldId = $id ?? (string) \Illuminate\Support\Str::of($name)->replace(['[',']'], ['-','']);
    $hasError = $errors->has($name);
@endphp

@if ($label)
  <label for="{{ $fieldId }}" class="block text-sm text-gray-700 mb-1">
    {{ $label }}
  </label>
@endif

<textarea
  id="{{ $fieldId }}"
  name="{{ $name }}"
  rows="{{ $rows }}"
  @if(!is_null($placeholder)) placeholder="{{ $placeholder }}" @endif
  {{ $attributes->merge([
    'class' =>
      'w-full rounded-md border '.($hasError ? 'border-red-500' : 'border-gray-300').
      ' focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition'
  ]) }}
>{{ old($name, $slot) }}</textarea>
