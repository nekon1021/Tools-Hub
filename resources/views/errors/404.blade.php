@extends('layouts.app')

@section('title', 'ページが見つかりません（404） | ' . config('app.name'))

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<main class="min-h-[60vh] flex items-center justify-center bg-stone-50 dark:bg-stone-900">
  <section class="w-full max-w-2xl px-6 py-16 text-center">
    <p class="text-xs font-medium tracking-widest text-stone-500 dark:text-stone-400">404 NOT FOUND</p>
    <h1 class="mt-2 text-2xl sm:text-3xl font-semibold text-stone-900 dark:text-white">
      ページが見つかりません（404）
    </h1>
    <p class="mt-3 text-stone-600 dark:text-stone-300">
      お探しのページは移動または削除された可能性があります。
    </p>

    <div class="mt-8 flex items-center justify-center gap-3">
      <a href="{{ route('tools.index') }}"
         class="inline-flex items-center rounded-xl border border-stone-300 dark:border-stone-700 px-4 py-2
                text-stone-700 dark:text-stone-100 hover:bg-stone-100 dark:hover:bg-stone-800
                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
        トップへ戻る
      </a>
    </div>
  </section>
</main>
@endsection
