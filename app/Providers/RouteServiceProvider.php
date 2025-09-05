<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // 追加分（）
        RateLimiter::for('admin-sign-in', function (Request $request) {
            // 同一IP × 同一メールの組み合わせを1分あたり6回まで
            $key = sprintf('admin-login:%s|%s',
                $request->ip(),
                mb_strtolower((string)$request->input('email'))
            );

            // 1分6回（固定窓）。超過時のレスポンスもカスタム可能
            return Limit::perMinute(6)->by($key)
                ->response(function (Request $request, array $headers) {
                    return response('ログイン試行が多すぎます。しばらく待ってから再試行してください。', 429, $headers);
                });
        });
        
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('tool', function(Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perDay(200)->by($request->ip()),
            ];
        });

        RateLimiter::for('login', fn($request) => [ Limit::perMinute(5)->by($request->ip()) ]);
        
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
        
    }
}
