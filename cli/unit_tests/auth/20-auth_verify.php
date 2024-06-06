#!/usr/bin/env php
<?php
    /*
     * file  10-auth_verify_test.php
     * author yu.li<yu.li@geneegroup.com>
     * date 2013-05-17
     *
     * useage SITE_ID=cf LAB_ID=ly php 20-auth_verify_test.php'
     * brief
     * 注意 请去空数据库运行00-init.php创建数据，测试时需在auth.php 配置$config['hostname'] = 'ut';
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

$auth = new auth('genee|database');
$c1 = $auth->verify(123456);

echo_hl('本地用户测试验证: genee|database');
if($c1){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}

$auth2 = new auth('genee|database%ut');

$c2 = $auth2->verify(123456);
echo_hl('远程用户测试验证: genee|database%ut');
if($c2){
    echo_green('测试通过');
}
else{
    echo_error('返回信息错误');
}



