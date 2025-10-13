<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Front\CategoryController as FrontCategoryController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\PostController as FrontPostController;
use App\Http\Controllers\HtmlSitemapController;
use App\Http\Controllers\SitemapController;

// Tools
use App\Http\Controllers\Tools\Count\CountController;
use App\Http\Controllers\Tools\IndexController;
use App\Http\Controllers\Tools\ImageCompressor\CompressorController;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Admin\EditorUploadController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider and assigned to
| the "web" middleware group.
*/

// 設定のログインは常時404 + noindex（URL直打ち封鎖）
Route::middleware('robots.noindex')->group(function () {
    Route::any('/login', fn () => abort(404));
    Route::any('/register', fn () => abort(404));
});

// 管理者 /admin 配下に集約（noindexを常時付与）
Route::prefix('admin')->as('admin.')->middleware('robots.noindex')->group(function () {
    // 未ログインだけ許可（ゲスト向け）
    Route::middleware('guest')->group(function () {
        Route::get('/sign_in', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/sign_in', [AuthController::class, 'login'])->middleware('throttle:6,1');
    });

    // 認証 + 管理者のみ
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::post('/editor/upload', [EditorUploadController::class, 'store'])
            ->name('editor.upload')
            ->middleware('throttle:12,1');

        // 記事の投稿
        Route::resource('posts', AdminPostController::class)
            ->parameters(['posts' => 'post']);

        // プレビュー
        Route::get('/posts/{post}/preview', [AdminPostController::class, 'previewWithTrashed'])
            ->withTrashed()
            ->name('posts.preview');

        // 一括操作（任意）
        Route::post('posts/bulk', [AdminPostController::class, 'bulk'])->name('posts.bulk');

        // 単体：復元・完全削除（ソフト削除済みもバインドできるよう withTrashed を付与）
        Route::post('posts/{post}/restore', [AdminPostController::class, 'restore'])
            ->withTrashed()
            ->name('posts.restore');

        Route::delete('posts/{post}/force', [AdminPostController::class, 'forceDestroy'])
            ->withTrashed()
            ->name('posts.force-destroy');
    });
});

// トップページ（ツール一覧）
Route::get('/', [IndexController::class, 'index'])->name('tools.index');

// Tools
Route::prefix('tools')->name('tools.')->group(function () {
    // 文字数カウント
    Route::get('/character-count', [CountController::class, 'count'])->name('charcount');
    Route::post('/character-count/run', [CountController::class, 'countrun'])->name('charcount.run');
    Route::post('/character-count/reset', [CountController::class, 'countreset'])->name('charcount.reset');

    // 画像圧縮
    Route::get('/image-compressor',  [CompressorController::class, 'index'])->name('image.compressor');
    Route::post('/image-compressor', [CompressorController::class, 'compress'])->name('image.compressor.run');
    // 画像圧縮ダウンロード
    Route::get('/image-compressor/download', [CompressorController::class, 'download'])
         ->name('image.compressor.download')
         ->middleware('signed');
    // 画像圧縮キャンセル
    Route::post('/image-compressor/delete', [CompressorController::class, 'delete'])
        ->name('image.compressor.delete'); // AJAX/POST削除

    // 将来追加するツール（例）
    // Route::get('/worddensity', [WordDensityController::class, 'index'])->name('worddensity');
    // Route::get('/seo-check', [SeoCheckController::class, 'index'])->name('seo');
});

// 公開側・記事一覧・詳細（誰でも閲覧可）
Route::prefix('posts')->name('public.posts.')->group(function () {
    Route::get('/', [FrontPostController::class, 'index'])->name('index');
    Route::get('{slug}', [FrontPostController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('show');
});

// カテゴリー別一覧（誰でも閲覧可）
Route::prefix('categories')->name('public.categories.')->group(function () {
    Route::get('{category:slug}/posts', [FrontCategoryController::class, 'index'])
        ->where('category', '[a-z0-9-]+')
        ->name('posts.index');
});

// 公開用の固定ページ
Route::view('/privacy-policy', 'public.pages.privacy')->name('privacy');
Route::view('/about', 'public.pages.about')->name('about');

// 問い合わせ（フォーム+送信）
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])
    ->name('contact.send')
    ->middleware('throttle:5,1'); // 1分5回に制限

/*
|--------------------------------------------------------------------------
| サイトマップ & robots.txt（軽量・キャッシュ最適化版）
|--------------------------------------------------------------------------
| - XML/robots はセッション・クッキー系ミドルウェアを外す
| - HTMLサイトマップはセッション有効のまま
*/

// XML / robots.txt（軽量配信：セッション＆クッキー系を外す）
Route::middleware('cache.headers:public;max_age=3600')
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        VerifyCsrfToken::class,
    ])
    ->group(function () {
        Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
        Route::get('/sitemaps/{name}.xml', [SitemapController::class, 'show'])->name('sitemap.show');

        Route::get('/robots.txt', function () {
            $isProd  = app()->isProduction();
            $baseUrl = rtrim(config('app.url'), '/');

            $content = $isProd
                ? "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n"
                : "User-agent: *\nDisallow: /\n";

            return response($content, 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8')
                ->setEtag(sha1($content));
        });
    });

// HTMLサイトマップ（セッション有効のまま）
Route::middleware('cache.headers:public;max_age=3600;etag')
    ->get('/site-map', [HtmlSitemapController::class, 'show'])
    ->name('sitemap.html');

// require __DIR__.'/auth.php';
