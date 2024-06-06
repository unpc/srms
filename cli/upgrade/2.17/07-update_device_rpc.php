#!/usr/bin/env php
<?php
    /*
     * file 07-update_device_rpc.php
     * author Yusheng Wang <yusheng.wang@geneegroup.com>
     * date 2018-06-05
     *
     * useage SITE_ID=cf LAB_ID=test php 07-update_device_rpc.php
     * brief device ipc地址修正脚本，防止2.16以下的lims跨版本升级到2.17后，ipc地址错误导致远程操作失败
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $query = "SELECT * FROM `door` WHERE `server` like '%icco%ipc%'\G";
    $results = $db->query($query);
    if ($results) {
        return FALSE;
    }

    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};


$u->upgrade = function() {

    $db = Database::factory();

    // update door device ipc
    foreach(Q('door') as $door) {
        $device2 = $door->device2;
        $device2['ipc'] = $door->server;
        $door->device2 = $device2;
        $door->save();
    }

    Upgrader::echo_success("\nequipment door device ipc 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
