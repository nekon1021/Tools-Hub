<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Seo extends Component
{
    public string $title;
    public string $description;
    public ?string $canonical;
    public string $ogType;
    public ?string $ogImage;
    public ?string $ogImageAlt;
    public ?string $publishedTime; // ISO8601（記事用）
    public ?string $modifiedTime;  // ISO8601（記事用）
    public bool $noindex;
    public ?string $prev;
    public ?string $next;

    public function __construct(
        string $title = '',
        string $description = '',
        ?string $canonical = null,
        string $ogType = 'website',
        ?string $ogImage = null,
        ?string $ogImageAlt = null,
        ?string $publishedTime = null,
        ?string $modifiedTime = null,
        bool $noindex = false,
        ?string $prev = null,
        ?string $next = null,
    ) {
        $this->title         = $title;
        $this->description   = $description;
        $this->canonical     = $canonical;
        $this->ogType        = $ogType;
        $this->ogImage       = $ogImage;
        $this->ogImageAlt    = $ogImageAlt;
        $this->publishedTime = $publishedTime;
        $this->modifiedTime  = $modifiedTime;
        $this->noindex       = $noindex;
        $this->prev          = $prev;
        $this->next          = $next;
    }

    public function fullTitle(): string
    {
        $site = config('seo.site_name', config('app.name'));
        return $this->title ? "{$this->title} | {$site}" : $site;
    }

    public function description160(): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($this->description)));
        return Str::limit($text, 160, '…');
    }

    public function resolvedCanonical(): string
    {
        // 未指定なら現在URL + ページングをcanonicalに反映
        if ($this->canonical) return $this->canonical;

        $page = (int)request()->integer('page', 1);
        $base = url()->current();
        return $page > 1 ? "{$base}?page={$page}" : $base;
    }

    public function render()
    {
        return view('components.seo');
    }
}
