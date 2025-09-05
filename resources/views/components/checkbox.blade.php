@props([
  'name' => null,        // フォーム送信したいときに指定。UIだけなら null のままでOK
  'id' => null,          // 未指定なら name から生成 / nameが無ければユニークID
  'label' => null,       // ラベル文字
  'value' => '1',        // チェック時に送信する値（bool想定なら '1'）
  'checked' => false,    // 既定のチェック状態（true/false）
  'help' => null,        // 補足テキスト（任意）
])

@php
    $fieldId = $id
      ?? ($name
            ? (string) \Illuminate\Support\Str::of($name)->replace(['[',']'], ['-',''])
            : 'cb-'.uniqid());

    $hasError = $name ? $errors->has($name) : false;
    $isChecked = $name !== null ? (bool) old($name, $checked) : (bool) $checked;
@endphp

{{-- name があるときだけ、未チェック時に 0 を送る hidden を先に置く（Laravelの定石） --}}
@if($name)
  <input type="hidden" name="{{ $name }}" value="0">
@endif

<label for="{{ $fieldId }}" class="inline-flex items-center gap-2 cursor-pointer select-none">
  <input
    id="{{ $fieldId }}"
    type="checkbox"
    @if($name) name="{{ $name }}" @endif
    value="{{ $value }}"
    @checked($isChecked)
    {{ $attributes->merge([
      // form="count_form" などは呼び出し側から $attributes 経由で渡せます
      'class' =>
        'h-4 w-4 rounded border '.($hasError ? 'border-red-500' : 'border-gray-300').
        ' text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition'
    ]) }}
  >
  @if($label)
    <span class="text-sm text-gray-700">{{ $label }}</span>
  @endif
</label>

@if($help)
  <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
@endif
