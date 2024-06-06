<?php
$config['billing_fund'] = [
    'fields'  => [
        'name'             => ['type' => 'varchar(50)', 'null' => false, 'default' => ''],
        'remote_id'        => ['type' => 'int', 'null' => false, 'default' => 0]
    ],
    'indexes' => [
        'name'   => ['fields' => ['name']],
        'remote_id'   => ['fields' => ['remote_id']],
    ],
];


$config['eq_sample']['fields']['billing_fund'] = ['type' => 'object', 'oname' => 'billing_fund'];
$config['eq_sample']['indexes']['billing_fund'] = ['fields' => 'billing_fund'];

$config['eq_charge']['fields']['billing_fund'] = ['type' => 'object', 'oname' => 'billing_fund'];
$config['eq_charge']['indexes']['billing_fund'] = ['fields' => 'billing_fund'];