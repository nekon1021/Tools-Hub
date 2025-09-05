<?php

namespace App\Services\Tool;

final class TextCounter
{
    public function countCharsAndLines(string $raw): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $raw);

        // 書記素ベースで文字数を計算（intl拡張が無ければmb_strlen）
        $len = function (string $s): int {
            if (function_exists('grapheme_strlen')) {
                $n = grapheme_strlen($s);
                return $n === false ? mb_strlen($s, 'UTF-8') : $n;
            }
            return mb_strlen($s, 'UTF-8');
        };

        // 文字数(空白込み)
        $chars = $len($text);

        // 行数
        $lines = ($text === '') ? 0 : (substr_count($text, "\n") + 1);

        // 空白の合計（\s に全角スペース U+3000 を追加して網羅）
        $whitespaceTotal = (int) preg_match_all('/(?:\s|\x{3000})/u', $text);

        // 空白のみ除去
        $noNlText = str_replace("\n",'', $text);
        $cahrsNonl = $len($noNlText);

        // 空白、改行除去
        $noWsText   = preg_replace('/\s+/u', '', $text);
        $charsNoWs  = $len($noWsText);

        return [
            'chars'        => $chars,        // 空白込み
            'chars_no_ln' => $cahrsNonl,
            'chars_no_ws'  => $charsNoWs,    // 空白と改行抜き
            'lines'        => $lines,
            'whitespace'   => $whitespaceTotal, // 合計だけ
        ];
    }
}
