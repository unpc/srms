<?php
$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['eq_purchase'] = [
    'desktop' => [
        'title' => '仪器申购论证',
        'icon' => '!eq_purchase/icons/48/eq_purchase.png',
        'url' => $host . '/eqpurchase/?oauth-sso=eqpurchase.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '仪器申购论证',
        'icon' => '!eq_purchase/icons/32/eq_purchase.png',
        'url' => $host . '/eqpurchase/?oauth-sso=eqpurchase.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '仪器申购论证',
        'icon' => '!eq_purchase/icons/16/eq_purchase.png',
        'url' => $host . '/eqpurchase/?oauth-sso=eqpurchase.' . LAB_ID,
        'target' => '_blank',
    ]
];
