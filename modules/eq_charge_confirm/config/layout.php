<?php

$config['sidebar.menu']['eq_charge_confirm'] = [
	'desktop' => [
		'title' => '收费确认',
		'icon' => '!eq_charge_confirm/icons/48/eq_charge_confirm.png',
		'url' => '!eq_charge_confirm/confirm',
	],
	'icon' => [
		'title' => '收费确认',
		'icon' => '!billing/icons/32/billing.png',
		'url' => '!eq_charge_confirm/confirm',
	],
	'list'=>[
        'title' => '收费确认',
        'class' => 'icon-paymentconfirmation',
		'icon' => '!eq_charge_confirm/icons/16/eq_charge_confirm.png',
		'url' => '!eq_charge_confirm/confirm',
	],
    'category' => "财务管理",
    'category_weight' => 80
];
