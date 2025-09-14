@extends('layouts.app')

@section('title',  '無料便利ツール一覧 - ' . config('app.name'))

@section('meta_description', '無料で使える便利ツールを一覧で紹介。文字数カウントなど実務・学習に役立つWebツールを随時追加。ブラウザ完結・登録不要で安心です。')

@section('content')
  <div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold mb-6">無料便利ツール一覧</h1>

    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
      @foreach ($tools as $tool)
        <a href="{{ $tool['route'] }}" class="block p-4 border rounded-lg shadow-sm hover:shadow-md transition">
          <h2 class="text-lg font-semibold">{{ $tool['name'] }}</h2>
          <p class="text-sm text-gray-600 mt-2">{{ $tool['description'] }}</p>
        </a>
      @endforeach
    </div>

    {{-- ★ 一覧ボトム広告（ページ末尾） --}}
    {{-- <x-ad.slot id="tools-bottom" class="my-8 min-h-[250px]" /> --}}
    {{-- @includeIf('partials.ads.tools-bottom') --}}

  </div>
@endsection
