#!/usr/bin/env php
<?php
    /*
     * file  10-auth_api_test.php
     * author yu.li<yu.li@geneegroup.com>
     * date 2013-05-17
     *
     * useage SITE_ID=cf LAB_ID=ly php 10-billing_api_test.php --localname=xx --remote_url='http://yu.li.cf.gin.genee.cn/ly/index.php/api'
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



function Usage() {
    die("Usage: SITE_ID=xx LAB_ID=xx php 10-billing_api_test.php --remote_url='http://xxxxx --local_private_key=xxxx.private --remote_public_key=xxxx.pub \n");
}

$options = getopt(NULL, ['remote_url:']);

$remote_url = $options['remote_url'];


//判断是否正常传值
if (!$remote_url) {
    Usage();
}


$rpc = new RPC($remote_url, 'people');
$user_info = $rpc->get_user('genee|database');

$target_user = [
    'token' => 'genee|database',
    'email' => 'genee@geneegroup.com',
    'name' => '技术支持',
    'gender' => '女',
    'card_no' => 123456789,
    'card_no_s' => 12345,
    'ctime' => 111111111,
    'phone' => '83719730',
    'address' => 'address',
    'group' => [
           '0' => '组织机构1',
           '1' => '组织机构2',
           ],
    'member_type' => '科研助理',
    'creator' => '技术支持',
    'auditor' => '技术支持',
    'ref_no' => 54321,
    'major' => 'major',
    'organization' => 'organization',
];

$c = array_diff($user_info, $target_user);


echo_hl('测试获得user所有信息');
if(empty($c)){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}


$user_info2 = $rpc->get_user('genee|database', ['name', 'gender']);
$target_user2 = [
    'name' => '技术支持',
    'gender' => '女',
    ];

echo_hl('测试获得部分user信息');
if(empty($c2)){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}





$rpc = new RPC($remote_url, 'lab');
$lab_info = $rpc->get_lab('genee|database');


$target_lab = [
    'creator' => '技术支持',
    'auditor' => '技术支持',
    'owner' => '技术支持',
    'name' => 'genee',
    'description' => 'description',
    'ctime' => 22222222,
    'contact' => 'contact',
    'group' => [
           '0' => '组织机构1',
       ],
    'owner_token' => 'genee|database',
    'owner_email' => 'genee@geneegroup.com',
    'owner_phone' => '83719730',
];

$cl = array_diff($lab_info, $target_lab);

echo_hl('测试获得所有lab信息');
if(empty($cl)){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}



$lab_info2 = $rpc->get_lab('genee|database', ['name', 'description']);
$target_lab2 = [
    'name' => 'genee',
    'gender' => 'description',
    ];
$cl2 = array_diff($lab_info2, $target_lab2);

echo_hl('测试获得部分lab信息');
if(empty($cl2)){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}

