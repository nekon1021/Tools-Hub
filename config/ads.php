<?php

return [
    // 本番で実広告を出すとき true（開発や検証では false に）
    'enabled' => (bool) env('ADS_ENABLED', false),

    // ?dummy=1 でダミー枠を強制表示（レイアウト確認用）
    'allow_dummy_query' => true,

    // 'adsense' または 'gam'（Google Ad Manager）
    'network' => env('ADS_NETWORK', 'adsense'),

    // AdSense 用
    'adsense' => [
        'client' => env('ADSENSE_CLIENT'), // 例: ca-pub-xxxxxxxxxxxxxxxx
    ],

    // ★ 追加：AdSense の data-ad-slot を論理IDにマッピング
    'slots' => [
        'under-lead' => env('AD_SLOT_UNDER_LEAD', '0000000000'),
        'in-body'    => env('AD_SLOT_IN_BODY',    '0000000001'),
        'below'      => env('AD_SLOT_BELOW',      '0000000002'),
        // 一覧やツールでも使うならここに追加
        'list-top'   => env('AD_SLOT_LIST_TOP',   '0000000003'),
        'list-grid'  => env('AD_SLOT_LIST_GRID',  '0000000004'),
        'tool-top'   => env('AD_SLOT_TOOL_TOP',   '0000000005'),
        'tool-side'  => env('AD_SLOT_TOOL_SIDE',  '0000000006'),
        'tool-bottom'=> env('AD_SLOT_TOOL_BOTTOM','0000000007'),
        // ★ 互換エイリアス（既存テンプレが article_under_lead 等でも動くように）
        'article_under_lead' => env('AD_SLOT_UNDER_LEAD', '0000000000'),
        'article_in_body'    => env('AD_SLOT_IN_BODY',    '0000000001'),
        'article_below'      => env('AD_SLOT_BELOW',      '0000000002'),
    ],

    // GAM 用（ユニットパスを論理IDにマッピング）
    'gam' => [
        'units' => [
            'under-lead'   => env('GAM_UNIT_UNDER_LEAD',   '/1234567/site/under-lead'),
        'in-body'      => env('GAM_UNIT_IN_BODY',      '/1234567/site/in-body'),
        'below'        => env('GAM_UNIT_BELOW',        '/1234567/site/below'),
        'list-top'     => env('GAM_UNIT_LIST_TOP',     '/1234567/site/list-top'),
        'list-grid'    => env('GAM_UNIT_LIST_GRID',    '/1234567/site/list-grid'),
        'tool-top'     => env('GAM_UNIT_TOOL_TOP',     '/1234567/site/tool-top'),
        'tool-side'    => env('GAM_UNIT_TOOL_SIDE',    '/1234567/site/tool-side'),
        'tool-mid'    => env('GAM_UNIT_TOOL_MID',    '/1234567/site/tool-mid'),
        'tool-bottom'  => env('GAM_UNIT_TOOL_BOTTOM',  '/1234567/site/tool-bottom'),
        'side-rail'    => env('GAM_UNIT_SIDE_RAIL',    '/1234567/site/side-rail'),
        'sticky-bottom'=> env('GAM_UNIT_STICKY_BOTTOM','/1234567/site/sticky-bottom'),
        'side-rail-left'  => env('GAM_UNIT_SIDE_LEFT',  '/1234567/site/side-rail-left'),
        'side-rail-right' => env('GAM_UNIT_SIDE_RIGHT', '/1234567/site/side-rail-right'),
        'tool-sticky' => env('GAM_UNIT_TOOL_STICKY', '/1234567/site/tool-sticky'),
        ],
        // （任意）サイズの簡易デフォルト
        'sizes' => [
            'under-lead' => [[336, 280], [300, 250]],
            'in-body'    => [[336, 280], [300, 250]],
            'below'      => [[728, 90], [336, 280], [300, 250]],
        ],
    ],
];
