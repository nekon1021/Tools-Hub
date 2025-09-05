{{-- layouts/app.blade.php のフッターなど --}}
<footer class="mt-12 border-t py-6 text-sm text-center text-gray-600">
  <div class="space-x-4">
    <a href="{{ route('privacy') }}" class="hover:underline">プライバシーポリシー</a>
    <a href="{{ route('contact') }}" class="hover:underline">お問い合わせ</a>
    <a href="{{ route('about') }}" class="hover:underline">運営者情報</a>
  </div>
  <p class="mt-2">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
</footer>
