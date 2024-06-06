#!/usr/bin/env php
<?php
/*
 * file 01-eq_glogon.php
 * author Lianhui.Cao <lianhui.cao@geneegroup.com>
 * date 2021-03-22
 *
 * useage SITE_ID=xx LAB_ID=xxxx php 01-eq_glogon.php
 * brief 刷新历史glogon_password, 如果升级2.22之前上过eq_glogon模块，就跑这个，否则不需要跑
 */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    if (Module::is_installed('eq_glogon')) {
        return true;
    } else {
        return false;
    }
};

// 数据库备份
$u->backup = function () {
    return true;
};

$u->upgrade = function () {
    Upgrader::echo_title('正在刷新glogon_password');

    $db    = Database::factory();
    $users = Q("user[atime]");
    foreach ($users as $user) {
        $extra       = $db->value('SELECT `_extra` FROM `user` WHERE `token`="%s"', $user->token);
        $glogon_pass = json_decode($extra)->glogon_pass;
        if ($glogon_pass) {
            $user->glogon_pass = $glogon_pass;
            $user->save();
        }
    }

    Upgrader::echo_title('glogon_password刷新完成');
};

// 恢复数据
$u->restore = function () {
    return true;
};

$u->run();
