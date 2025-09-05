<style>
  /* 高解像度紙背景 */
  .sepia-photo-hd {
    background-color: #f4ecd8; /* ベース色 */
    background-image: url('https://www.toptal.com/designers/subtlepatterns/patterns/aged-paper.jpg');
    /* ↑ 無料で使える古紙写真（高解像度） */
    background-size: cover;    /* 全面をカバー */
    background-position: center;
    background-repeat: no-repeat;
    color: #704214;            /* セピア文字色 */
  }

  /* ホバー時の色変化（透明レイヤー） */
  .sepia-hover:hover {
    background-color: rgba(230, 215, 179, 0.85) !important;
  }
</style>

<nav class="border-gray-300 sepia-photo-hd">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
    <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
      <span class="self-center text-2xl font-semibold whitespace-nowrap">Tools Hub</span>
    </a>
    <div class="flex items-center md:order-2 space-x-1 md:space-x-0 rtl:space-x-reverse">

      <!-- メニュー -->
      <button type="button" id="menuBtn"
        data-dropdown-toggle="language-dropdown-menu"
        class="hidden md:inline-flex items-center font-medium justify-center px-4 py-2 text-sm rounded-lg cursor-pointer sepia-hover"
        style="border: 1px solid rgba(112, 66, 20, 0.4);">
        メニュー
      </button>

      <!-- Dropdown -->
      <div id="language-dropdown-menu"
           class="z-50 hidden my-4 text-base list-none rounded-lg shadow-sm sepia-photo-hd"
           style="border:1px solid rgba(112, 66, 20, 0.4);">
        <ul class="py-2 font-medium" role="none">
          <li>
            <a href="#"
               class="block px-4 py-2 text-sm rounded sepia-hover"
               role="menuitem">
              ホーム
            </a>
          </li>
        </ul>
      </div>

      <!-- ハンバーガーボタン -->
      <button data-collapse-toggle="navbar-language" id="burgerBtn" type="button"
        class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm rounded-lg md:hidden sepia-hover"
        style="border:1px solid rgba(112, 66, 20, 0.4);">
        <span class="sr-only">Open main menu</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
        </svg>
      </button>
    </div>
  </div>

  @php
  $to = auth()->check() && auth()->user()->is_admin
      ? route('admin.posts.index')
      : route('public.posts.index');
@endphp

  <!-- ドロップダウン（全幅） -->
  <div id="mega-menu-full-dropdown"
       class="hidden mt-1 border-gray-300 shadow-xs sepia-photo-hd"
       style="border-top:1px solid rgba(112, 66, 20, 0.4); border-bottom:1px solid rgba(112, 66, 20, 0.4);">
    <div class="grid max-w-screen-xl px-4 py-5 mx-auto sm:grid md:px-6">
      <ul>
        <li>
          <a href="{{ $to }}" class="block p-3 rounded-lg font-semibold sepia-hover">
            記事
          </a>
        </li>

        <li>
          <a href="{{ route('sitemap.html') }}" class="block p-3 rounded-lg font-semibold sepia-hover">
            サイトマップ
          </a>
        </li>

        @auth('web')
          <li>
            <form action="{{ route('admin.logout')}}" method="POST" class="contents">
              @csrf
              <button
                  type="submit"
                  class="px-3 py-2 rounded hover:bg-gray-100"
                  style="border:1px solid rgba(112, 66, 20, 0.4);"
                  >
                  ログアウト
              </button>
            </form>
          </li>
        @endauth
      </ul>
    </div>
  </div>
</nav>

<script>
  document.getElementById('menuBtn').addEventListener('click', function () {
    document.getElementById('mega-menu-full-dropdown').classList.toggle('hidden');
  });

  document.getElementById('burgerBtn').addEventListener('click', function() {
    document.getElementById('mega-menu-full-dropdown').classList.toggle('hidden');
  });
</script>