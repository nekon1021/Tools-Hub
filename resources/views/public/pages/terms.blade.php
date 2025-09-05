@extends('layouts.app')

@section('title', '利用規約｜' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">
  <h1 class="text-2xl font-bold mb-6">利用規約</h1>
  <p class="mb-4">
    本規約は、{{ config('app.name') }}（以下「当サイト」）の提供するサービスの利用条件を定めるものです。
  </p>

  <h2 class="text-xl font-semibold mt-6 mb-2">免責事項</h2>
  <p class="mb-4">
    当サイトに掲載する情報は正確性に努めておりますが、その内容の正確性・安全性を保証するものではありません。
    当サイトを利用して生じた損害について、運営者は一切の責任を負いません。
  </p>
</div>
@endsection
