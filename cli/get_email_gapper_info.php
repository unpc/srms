#!/usr/bin/env php
<?php
    /*
     * file get_email_gapper_info.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015-03-26
     *
     * useage SITE_ID=cf LAB_ID=nankai php get_email_gapper_info.php email
     * brief 获取 email 地址对应的 Gapper 信息
     */

require 'base.php';

$email = $argv[1];

if (!$email) {
    die("SITE_ID=xx LAB_ID=xx php get_email_gapper_info.php support@geneegroup.com\n");
}

$rpc = new RPC('http://gapper.in/api');

$data = $rpc->Gapper->User->GetInfo($email);

if ($data) {
    foreach($data as $k => $v) {
        echo "$k => $v \n";
    }
}
else {
    echo "无该用户数据\n";
}
