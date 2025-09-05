<?php

namespace App\Http\Controllers\Tools;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        // 一覧に出すツールの情報（リンク・説明）
        $tools = [
            [
                'name' => '文字数カウント',
                'route' => route('tools.charcount'),
                'description' => 'テキストの文字数や行数を即座にカウントできます。',
            ],
            // 今後追加予定のツール
            // ['name' => 'SEOチェック', 'route' => route('tools.seo'), 'description' => 'ページのSEO要素を簡単に確認できます。'],
        ];
        
        return view('tools.index',compact('tools'));
    }
}
