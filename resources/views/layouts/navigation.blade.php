<nav class="tool-nav theme-{{ $theme ?? 'sepia' }}">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
    <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
      <span class="self-center text-2xl font-semibold whitespace-nowrap">Tools Hub</span>
    </a>

    <div class="flex items-center md:order-2 space-x-1 md:space-x-0 rtl:space-x-reverse">
      <!-- メニュー -->
      <button type="button" id="menuBtn"
        class="hidden md:inline-flex items-center font-medium justify-center px-4 py-2 text-sm rounded-lg cursor-pointer
               text-var border-var hover-bg">
        メニュー
      </button>

      <!-- Dropdown（必要ならJSでtoggle） -->
      <div id="language-dropdown-menu"
           class="z-50 hidden my-4 text-base list-none rounded-lg shadow-sm tool-nav border-var">
        <ul class="py-2 font-medium" role="none">
          <li>
            <a href="#"
               class="block px-4 py-2 text-sm rounded text-var hover-bg"
               role="menuitem">
              ホーム
            </a>
          </li>
        </ul>
      </div>

      <!-- ハンバーガー -->
      <button id="burgerBtn" type="button"
        class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm rounded-lg md:hidden
               text-var border-var hover-bg">
        <span class="sr-only">Open main menu</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M1 1h15M1 7h15M1 13h15"/>
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
       class="hidden mt-1 shadow-sm tool-nav border-y-var">
    <div class="grid max-w-screen-xl px-4 py-5 mx-auto sm:grid md:px-6">
      <ul>
        <li>
          <a href="{{ $to }}" class="block p-3 rounded-lg font-semibold text-var hover-bg">
            記事
          </a>
        </li>
        <li>
          <a href="{{ route('sitemap.html') }}" class="block p-3 rounded-lg font-semibold text-var hover-bg">
            サイトマップ
          </a>
        </li>
        @auth('web')
          <li>
            <form action="{{ route('admin.logout')}}" method="POST" class="contents">
              @csrf
              <button type="submit" class="px-3 py-2 rounded text-var border-var hover-bg">
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
  const menuBtn   = document.getElementById('menuBtn');
  const burgerBtn = document.getElementById('burgerBtn');
  const panel     = document.getElementById('mega-menu-full-dropdown');
  if (menuBtn && panel)   menuBtn.addEventListener('click', () => panel.classList.toggle('hidden'));
  if (burgerBtn && panel) burgerBtn.addEventListener('click', () => panel.classList.toggle('hidden'));
</script>
