{{-- resources/views/public/pages/contact.blade.php --}}
@extends('layouts.app')

@section('title', 'お問い合わせ｜' . config('app.name'))

@section('meta')
  <meta name="description" content="Toolshub へのお問い合わせページです。ご意見・ご要望・不具合報告などは、こちらのフォームからお気軽にお送りください。">
  <link rel="canonical" href="{{ route('contact') }}">
@endsection

@section('content')
  <h1 class="text-2xl font-bold mb-4">お問い合わせ</h1>

  @if (session('status'))
    <div class="mb-4 rounded border border-green-300 bg-green-50 p-3 text-green-800">
      {{ session('status') }}
    </div>
  @endif

  @error('message')
    <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      {{ $message }}
    </div>
  @enderror

  <form method="POST" action="{{ route('contact.send') }}" class="space-y-4">
    @csrf
    {{-- ハニーポット（フォーム内・CSRF直後が推奨） --}}
    <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
      <label for="website">website</label>
      <input type="text" id="website" name="website"
            tabindex="-1" autocomplete="off" autocapitalize="off" spellcheck="false">
    </div>
    
    {{-- 開始時刻 --}}
    <input type="hidden" name="_started_at" value="{{ $ts }}">

    <div>
      <label class="block text-sm font-medium" for="name">お名前</label>
      <input id="name" name="name" value="{{ old('name') }}"
            class="w-full rounded border px-3 py-2"
            required maxlength="80" autocomplete="name">
      @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium" for="email">メールアドレス</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}"
            class="w-full rounded border px-3 py-2"
            required maxlength="190" autocomplete="email" inputmode="email">
      @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium" for="subject">件名（任意）</label>
      <input id="subject" name="subject" value="{{ old('subject') }}"
            class="w-full rounded border px-3 py-2"
            maxlength="120" autocomplete="off">
      @error('subject')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium" for="message">本文</label>
      <textarea id="message" name="message" rows="6"
                class="w-full rounded border px-3 py-2"
                required maxlength="2000">{{ old('message') }}</textarea>
      @error('message')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <button id="contactSubmit" type="submit" class="rounded-xl border px-4 py-2 hover:bg-gray-50">
      送信
    </button>
  </form>

  @push('scripts')
    <script>
      (function(){
        const btn = document.getElementById('contactSubmit');
        const form = btn && btn.closest('form');
        if (!form || !btn) return;
        form.addEventListener('submit', function(){
          btn.disabled = true;
          btn.classList.add('opacity-60','cursor-not-allowed');
        });
      })();
    </script>
  @endpush

@endsection
