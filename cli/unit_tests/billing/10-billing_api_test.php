#!/usr/bin/env php
<?php
    /*
     * file  10-billing_api_test.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-05-16
     *
     * useage SITE_ID=cf LAB_ID=test php 10-billing_api_test.php --localname=xx --remote_url='http://rui.ma.cf.gin.genee.cn/may' --local_private_key=local.private --remote_public_key=remote.pub
     * brief
     */

require dirname(dirname(dirname(__FILE__))). '/base.php';

function echo_error($msg){
    echo "\033[31m".$msg."\033[0m \n";
}

function echo_hl($msg){
    echo "\033[1m".$msg."\033[0m \n";
}

function echo_green($msg){
    echo "\033[32m".$msg."\033[0m \n";
}

function billing_assert($title, $return_value, $assert_value) {
    echo_hl($title);
    if ($return_value != $assert_value) {
        echo_error('测试失败，实际测试结果如下：');
        var_dump($return_value);
    }
    else {
        echo_green('测试通过！');
    }
}


function Usage() {
    die("Usage: SITE_ID=xx LAB_ID=xx php 10-billing_api_test.php --localname=xx --remote_url='http://xxxxx --local_private_key=xxxx.private --remote_public_key=xxxx.pub \n");
}

$options = getopt(NULL, ['remote_url:', 'local_private_key:', 'remote_public_key:', 'localname:']);

$remote_url = $options['remote_url'];

$local_private_key_file  = $options['local_private_key'];

$remote_public_key_file =  $options['remote_public_key'];

$localname = $options['localname'];

//判断是否正常传值
if (!$remote_url || !$local_private_key_file || !$remote_public_key_file || !$localname) {
    Usage();
}

//判定local_private_key_file是否存在，或者文件内容为空提示
if (!File::exists($local_private_key_file) || !file_get_contents($local_private_key_file)) {
    die("本地私钥文件读取失败\n");
}

//判定remote_public_key_file是否可读
if (!File::exists($remote_public_key_file) || !file_get_contents($remote_public_key_file)) {
    die("远程公钥文件读取失败\n");
}

$local_privkey = file_get_contents($local_private_key_file);

$remote_pubkey = file_get_contents($remote_public_key_file);

$SSL = new OpenSSL();

//获取随机数
$random = @openssl_random_pseudo_bytes('20');

//随机数用远程服务器公钥加密
$encrypted_by_remote_pubkey = $SSL->encrypt($random, $remote_pubkey, 'public');

//随机数使用本地私钥签名
$signed_by_local_prikey = $SSL->sign($random, $local_privkey);

$rpc = new RPC($remote_url);

echo_hl('进行远程验证');

if (!$rpc->server->auth($localname, base64_encode($signed_by_local_prikey), base64_encode($encrypted_by_remote_pubkey))) {
    echo_error('远程验证失败');
    die;
}
else {
    echo_green('远程验证成功');
}



$rpc->billing->bind('department', 'genee|database', 1);
//去到刚才创建的account
$department = O('billing_department', ['nickname'=> 'department']);
$user = O('user', ['token' => 'genee|database']);
$lab = O('lab', ['owner'=>$user]);
$account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);

billing_assert(
    'billing_bind 绑定远程账号',
    [$account->source, $account->voucher],
    [$localname, 1]
);

billing_assert(
    'get_account_info 获取账号信息测试',
    $rpc->billing->get_account_info('department', 'genee|database'),
    [
        'name'=> 'department',
        'debit'=> 0,
        'credit'=> '50',
        'balance'=> '950',
        'source'=> 'test',
        'voucher'=> 1
    ]
);


$transaction_data = [
    'department'=>'department',
    'username'=>'genee|database',
    'amount'=>20,
    'status'=>0,
    'note'=>'测试明细创建',
];

billing_assert(
    'create_transaction 创建财务明细测试',
    $rpc->billing->create_transaction(1, $transaction_data),
    [
        'department'=> 'department',
        'username'=> 'genee|database',
        'voucher' => 1,
        'amount'=> 20,
        'status'=> 0
    ]
);


billing_assert(
    'read_transaction 获取财务明细测试',
    $rpc->billing->read_transaction('3'),
    [
        'department'=> 'department',
        'username'=>  'genee|database',
        'voucher' => 1,
        'amount'=> 20,
        'status'=> 0
    ]
);

$update_data = [
    'amount'=> 50,
    'status'=> 0,
    'note'=>'just modify transaction',
];
billing_assert(
    'update_transaction 更新财务明细测试',
    $rpc->billing->update_transaction('3', $update_data),
    [
        'department' => 'department',
        'username'=> 'genee|database',
        'voucher' => 1,
        'amount'=> 50,
        'status'=> 0
    ]
);

billing_assert(
    'delete_transaction 删除财务明细测试',
    $rpc->billing->delete_transaction('3'),
    1
);
