<?php

$config['billing_department'] = [
    'fields'  => [
        'name'        => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'group'       => ['type' => 'object', 'oname' => 'tag_group'],
        'mtime'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'nickname'    => ['type' => 'varchar(50)', 'null' => true, 'default' => ''],
        'description' => ['type' => 'varchar(250)', 'null' => true],
        'ctime'       => ['type' => 'int', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'name' => ['type' => 'unique', 'fields' => ['name']],
        //'nickname'=> array('type'=>'unique', 'fields'=> array('nickname'))
    ],
];

$config['billing_account'] = [
    'fields'  => [
        'department'              => ['type' => 'object', 'oname' => 'billing_department'],
        'lab'                     => ['type' => 'object', 'oname' => 'lab'],
        'income_remote'           => ['type' => 'double', 'null' => false, 'default' => 0], //远程充值
        'income_remote_confirmed' => ['type' => 'double', 'null' => false, 'default' => 0], //远程充值已确认金额
        'income_local'            => ['type' => 'double', 'null' => false, 'default' => 0], //本地充值(本地充值无需确认)
        'income_transfer'         => ['type' => 'double', 'null' => false, 'default' => 0], //调账转入
        'outcome_remote'          => ['type' => 'double', 'null' => false, 'default' => 0], //远程扣费 扣费无需confirm
        'outcome_local'           => ['type' => 'double', 'null' => false, 'default' => 0], //本地扣费
        'outcome_transfer'        => ['type' => 'double', 'null' => false, 'default' => 0], //调账转出
        'outcome_use'             => ['type' => 'double', 'null' => false, 'default' => 0], //使用扣费
        //'amount' => array('type'=>'double', 'null'=>FALSE, 'default'=>0), //总收入
        'balance'                 => ['type' => 'double', 'null' => false, 'default' => 0], //余额
        'credit_line'             => ['type' => 'double', 'null' => false, 'default' => 0],
        'source'                  => ['type' => 'varchar(100)', 'null' => false, 'default' => 'local'],
        'voucher'                 => ['type' => 'varchar(40)', 'null' => false, 'default' => ''], // 远程账号id (yu.li@2013.06.11)
    ],
    'indexes' => [
        'unique'                  => ['type' => 'unique', 'fields' => ['department', 'lab']],
        'department'              => ['fields' => ['department']],
        'lab'                     => ['fields' => ['lab']],
        //'amount' => array('fields'=>array('amount')),
        'income_remote'           => ['fields' => ['income_remote']],
        'income_remote_confirmed' => ['fields' => ['income_remote_confirmed']],
        'income_local'            => ['fields' => ['income_local']],
        'income_transfer'         => ['fields' => ['income_transfer']],
        'outcome_remote'          => ['fields' => ['outcome_remote']],
        'outcome_local'           => ['fields' => ['outcome_local']],
        'outcome_transfer'        => ['fields' => ['outcome_transfer']],
        'outcome_use'             => ['fields' => ['outcome_use']],
        'balance'                 => ['fields' => ['balance']],
    ],
];

$config['billing_transaction']['fields']['account']     = ['type' => 'object', 'oname' => 'billing_account'];
$config['billing_transaction']['fields']['user']        = ['type' => 'object', 'oname' => 'user'];
$config['billing_transaction']['fields']['reference']   = ['type' => 'object', 'oname' => 'billing_transaction'];
$config['billing_transaction']['fields']['status']      = ['type' => 'int(1)', 'null' => false, 'default' => 0];
$config['billing_transaction']['fields']['income']      = ['type' => 'double', 'null' => false, 'default' => 0];
$config['billing_transaction']['fields']['outcome']     = ['type' => 'double', 'null' => false, 'default' => 0];
$config['billing_transaction']['fields']['ctime']       = ['type' => 'int', 'null' => false, 'default' => 0];
$config['billing_transaction']['fields']['mtime']       = ['type' => 'int', 'null' => false, 'default' => 0];
$config['billing_transaction']['fields']['certificate'] = ['type' => 'varchar(40)', 'null' => false, 'default' => '']; // TODO 1. 凭证号用certificate不合适, evidence更好; 2. 凭证号应可为空; (xiaopei.li@2011.11.09)
$config['billing_transaction']['fields']['source']      = ['type' => 'varchar(100)', 'null' => false, 'default' => 'local'];
$config['billing_transaction']['fields']['voucher']     = ['type' => 'varchar(40)', 'null' => false, 'default' => '']; // 远程凭证号 (jia.huang@2013.05.26)
$config['billing_transaction']['fields']['manual']      = ['type' => 'tinyint', 'null' => false, 'default' => 0]; // 是否手动操作
$config['billing_transaction']['fields']['transfer']    = ['type' => 'tinyint', 'null' => false, 'default' => 0]; // 是否为转账记录

$config['billing_transaction']['indexes']['status']      = ['fields' => ['status']];
$config['billing_transaction']['indexes']['ctime']       = ['fields' => ['ctime']];
$config['billing_transaction']['indexes']['mtime']       = ['fields' => ['mtime']];
$config['billing_transaction']['indexes']['certificate'] = ['fields' => ['certificate']];
$config['billing_transaction']['indexes']['source']      = ['fields' => ['source']];
$config['billing_transaction']['indexes']['manual']      = ['fields' => ['manual']];
$config['billing_transaction']['indexes']['transfer']    = ['fields' => ['transfer']];
$config['billing_transaction']['indexes']['income']      = ['fields' => ['income']];
$config['billing_transaction']['indexes']['outcome']     = ['fields' => ['outcome']];
$config['billing_transaction']['indexes']['account']     = ['fields' => ['account']];
$config['billing_transaction']['indexes']['user']        = ['fields' => ['user']];
$config['billing_transaction']['indexes']['reference']   = ['fields' => ['reference']];

$config['billing_transaction']['engine'] = 'InnoDB';
