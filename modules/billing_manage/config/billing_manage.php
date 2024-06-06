<?php


$config['server'] = [
    "url" => "http://172.17.42.1/billing-manage/api/v1/"
];

$billing_url = "http://10.2.11.135/cf/billing";

$config['entries'] = [
    'fund' => [
        'redirect' => "$billing_url/#/funds?lims=1",
        'title' => '经费管理'
    ],
    'transaction_fund' => [
        'redirect' => "$billing_url/#/transactions?lims=1",
        'title' => '财务明细'
    ],
    'stat_platform' => [
        'redirect' => "$billing_url/#/platformstat?lims=1",
        'title' => '财务汇总'
    ]
];