<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Str;

class PostHtmlProcessor
{
    public function process(string $html): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$html, LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // 危険タグ除去（必要に応じて拡張）
        foreach (['script','iframe','object','embed'] as $tag) {
            while (($nodes = $doc->getElementsByTagName($tag))->length) {
                $node = $nodes->item(0);
                $node->parentNode->removeChild($node);
            }
        }

        // h2/h3 に id 付与 & TOC 収集
        $toc = [];
        $used = [];
        foreach (['h2','h3'] as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            for ($i=0; $i<$nodes->length; $i++) {
                $h = $nodes->item($i);
                $text = trim($h->textContent) ?: ($tag==='h2'?'見出し':'小見出し');
                $base = Str::slug(mb_substr($text,0,80));
                $slug = $base ?: 'section';
                $n = ($used[$slug] ?? 0) + 1; $used[$slug] = $n;
                if ($n > 1) $slug .= "-{$n}";
                $h->setAttribute('id', $slug);
                $toc[] = ['level' => $tag==='h2'?2:3, 'id'=>$slug, 'text'=>$text];
            }
        }

        $body = $doc->saveHTML();
        return ['body' => $body, 'toc' => $toc];
    }
}
