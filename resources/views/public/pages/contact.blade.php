{{-- resources/views/public/pages/contact.blade.php --}}
@extends('layouts.app')

@section('title', 'お問い合わせ｜' . config('app.name'))

@section('content')
<div class="max-w-xl mx-auto py-8 px-4">
  <h1 class="text-2xl font-bold mb-6">お問い合わせ</h1>

  @if(session('status'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-green-700">
      {{ session('status') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-red-700">
      入力内容をご確認ください。
    </div>
  @endif

  <form method="POST" action="{{ route('contact.send') }}" class="space-y-4" novalidate>
    @csrf
    {{-- ハニーポット（botが入れる） --}}
    <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
      <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>
    {{-- 表示時刻（time-trap用） --}}
    <input type="hidden" name="_started_at" id="_started_at" value="">

    <div>
      <label class="block text-sm font-medium">お名前 <span class="text-red-500">*</span></label>
      <input name="name" value="{{ old('name') }}" required maxlength="80"
             class="mt-1 w-full border rounded px-3 py-2">
      @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">メールアドレス <span class="text-red-500">*</span></label>
      <input type="email" name="email" value="{{ old('email') }}" required maxlength="190"
             class="mt-1 w-full border rounded px-3 py-2">
      @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">件名</label>
      <input name="subject" value="{{ old('subject') }}" maxlength="120"
             class="mt-1 w-full border rounded px-3 py-2">
      @error('subject') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium">お問い合わせ内容 <span class="text-red-500">*</span></label>
      <textarea name="message" rows="6" required maxlength="5000"
                class="mt-1 w-full border rounded px-3 py-2">{{ old('message') }}</textarea>
      @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="pt-2">
      <button class="px-4 py-2 rounded border hover:bg-gray-50">送信する</button>
    </div>
  </form>
</div>

<script>
  // 表示から送信までの最短時間をチェック
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('_started_at').value = Math.floor(Date.now() / 1000);
  });
</script>
@endsection
