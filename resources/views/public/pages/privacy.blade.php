@extends('layouts.app')

@section('title', 'プライバシーポリシー｜' . config('app.name'))

@section('meta_description', 'Tools Hubのプライバシーポリシー。アクセス解析（Googleアナリティクス）と広告配信におけるCookieの利用、取得データ、免責事項、問い合わせ窓口を明記しています。')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-6">プライバシーポリシー</h1>

  <h2 class="text-xl font-semibold mt-6 mb-2">アクセス解析について</h2>
  <p>
    当サイトでは、Googleによるアクセス解析ツール「Googleアナリティクス」を利用しています。
    Googleアナリティクスはデータ収集のためにCookieを使用しています。
    このデータは匿名で収集され、個人を特定するものではありません。
  </p>
  <p>
    Cookieの無効化によりデータ収集を拒否できます。設定方法はブラウザのマニュアルをご確認ください。
    詳細は
    <a href="https://marketingplatform.google.com/about/analytics/terms/jp/" target="_blank" class="text-blue-600 underline">Googleアナリティクス利用規約</a>、
    <a href="https://policies.google.com/privacy?hl=ja" target="_blank" class="text-blue-600 underline">Googleポリシーと規約</a>
    をご参照ください。
  </p>

  <h2 class="text-xl font-semibold mt-6 mb-2">広告配信について</h2>
  <p>
    当サイトはGoogle及びそのパートナー（第三者配信事業者）の提供する広告サービスを利用しています。
    これらの事業者はCookieを使用し、ユーザーの過去アクセス情報に基づいて広告を配信します。
  </p>
  <p>
    ユーザーは
    <a href="https://adssettings.google.com/authenticated" target="_blank" class="text-blue-600 underline">Google広告設定</a>
    でパーソナライズ広告を無効化できます。
    また
    <a href="https://www.google.com/policies/technologies/ads/" target="_blank" class="text-blue-600 underline">Googleの広告におけるCookieの取り扱い</a>
    もご確認ください。
  </p>

  <h2 class="text-xl font-semibold mt-6 mb-2">免責事項</h2>
  <p>
    当サイトに掲載する情報は正確性に努めていますが、内容の正確性・安全性を保証するものではありません。
    利用により生じた損害について、当サイトは一切の責任を負いません。
  </p>

  <h2 class="text-xl font-semibold mt-6 mb-2">お問い合わせ</h2>
  <p>
    当サイトに関するお問い合わせは
    <a href="{{ route('contact') }}" class="text-blue-600 underline">お問い合わせフォーム</a>
    よりお願いいたします。
  </p>
</div>
@endsection
