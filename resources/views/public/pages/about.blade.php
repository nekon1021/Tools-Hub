{{-- resources/views/public/pages/about.blade.php --}}
@extends('layouts.app')

@section('title', '運営者情報｜' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">
  <h1 class="text-2xl font-bold mb-6">運営者情報</h1>
  <dl class="space-y-2">
    <div><dt class="font-medium">サイト名</dt><dd>{{ config('app.name') }}</dd></div>
    <div><dt class="font-medium">運営者</dt><dd>ToolsHub管理人</dd></div>
  </dl>
  <p class="text-sm text-gray-500 mt-4">※個人情報は公開していません。連絡はお問い合わせフォームからお願いします。</p>
</div>
@endsection
