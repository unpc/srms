<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['fund_report'] = [
    'desktop' => [
        'title' => '基金申报',
        'icon' => '!fund_report/icons/48/fund_report.png',
        'url' => $host . '/fundreport/?oauth-sso=fundreport.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '基金申报',
        'icon' => '!fund_report/icons/32/fund_report.png',
        'url' => $host . '/fundreport/?oauth-sso=fundreport.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '基金申报',
        'icon' => 'icon-funddeclaration',
        'url' => $host . '/fundreport/?oauth-sso=fundreport.' . LAB_ID,
        'target' => '_blank',
    ],
];
