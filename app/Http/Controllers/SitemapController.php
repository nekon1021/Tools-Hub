<?php
// app/Http/Controllers/SitemapController.php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    // /sitemap.xml
    public function index(Request $request)
    {
        $items = [
            [
                'loc' => route('sitemap.show', ['name' => 'posts']),
                'lastmod' => optional(Post::published()->max('updated_at'), fn($d) => Carbon::parse($d)->toAtomString()),
            ],
            [
                'loc' => route('sitemap.show', ['name' => 'static']),
                // 固定ページ側は頻繁に変わらないので Last-Modified は付けない（null）
                // ※厳密に付けたい場合は config などの固定値に置換
                'lastmod' => null,
            ],
        ];

        $xml = Cache::remember('sitemap:index', now()->addHour(), function () use ($items) {
            $x  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $x .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($items as $it) {
                $x .= "  <sitemap>\n";
                $x .= '    <loc>'.e($it['loc'])."</loc>\n";
                if (!empty($it['lastmod'])) {
                    $x .= '    <lastmod>'.$it['lastmod']."</lastmod>\n";
                }
                $x .= "  </sitemap>\n";
            }
            $x .= '</sitemapindex>';
            return $x;
        });

        // 最終更新はitemsのlastmodの最大値を採用
        $lastModifiedIso = collect($items)->pluck('lastmod')->filter()->max();
        $lastModified     = $lastModifiedIso ? Carbon::parse($lastModifiedIso) : null;
        $etag             = sha1($xml);

        $response = Response::make($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ])->setEtag($etag);
        if ($lastModified) {
             $response->setLastModified($lastModified);
        }
        // If-None-Match / If-Modified-Since による 304 応答
        $response->isNotModified($request);
        return $response;
    }

    // /sitemaps/{name}.xml
    public function show(Request $request, string $name)
    {
        $key = "sitemap:{$name}";

        $xml = Cache::remember($key, now()->addHour(), function () use ($name) {
            if ($name === 'posts') {
                $out  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
                $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

                Post::published()
                    ->select('id','slug','updated_at')
                    ->orderBy('id')
                    ->chunkById(1000, function ($rows) use (&$out) {
                        foreach ($rows as $r) {
                            $loc = route('public.posts.show', ['slug' => $r->slug]);
                            $lm  = optional($r->updated_at, fn($d) => Carbon::parse($d)->toAtomString());
                            $out .= "  <url>\n";
                            $out .= '    <loc>'.e($loc)."</loc>\n";
                            if ($lm) $out .= "    <lastmod>{$lm}</lastmod>\n";
                            $out .= "  </url>\n";
                        }
                    });

                $out .= '</urlset>';
                return $out;
            }

            if ($name === 'static') {
                $static = [
                    route('tools.index'),
                    route('tools.charcount'),
                    route('about'),
                    route('privacy'),
                    route('contact'),
                ];

                $out  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
                $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
                foreach ($static as $loc) {
                    $out .= "  <url>\n";
                    $out .= '    <loc>'.e($loc)."</loc>\n";
                    $out .= "  </url>\n";
                }
                $out .= '</urlset>';
                return $out;
            }

            abort(404);
        });

         // name ごとに Last-Modified を決定
         $lastModified = null;
         if ($name === 'posts') {
            $latest = Post::published()->max('updated_at');
            if ($latest) {
                $lastModified = Carbon::parse($latest);
            }
        } elseif ($name === 'static') {
            // 固定ページ群は更新頻度が低いなら null でもOK。更新検知したいなら now() ではなく
            // 各ビュー/コンテンツの最終更新を採用するのが理想。暫定で now() を外すか軽い定数に。
            $lastModified = null;
        }
        $etag = sha1($xml);

        $response = Response::make($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ])->setEtag($etag);
        if ($lastModified) {
             $response->setLastModified($lastModified);
        }
        $response->isNotModified($request);
        return $response;
    }
}
