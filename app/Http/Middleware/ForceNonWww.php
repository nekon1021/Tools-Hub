<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceNonWww
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // www サブドメインなら非www へ恒久リダイレクト
        if (preg_match('/^www\./i', $host)) {
            // 例: www.toolshub.jp → toolshub.jp
            $apex = preg_replace('/^www\./i', '', $host);

            // 元のパス & クエリを維持
            $uri  = $request->getRequestUri(); // /about?x=1 など

            // スキームはアクセスされた通りを維持（ローカル http、本番 https）
            $scheme = $request->getScheme();

            $target = "{$scheme}://{$apex}{$uri}";
            return redirect()->to($target, 308);
        }

        return $next($request);
    }
}
