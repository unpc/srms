#!/usr/bin/env php
<?php
    /*
     * file empty_unactive_users_lab.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-03-28
     *
     * useage SITE_ID=cf-lite LAB_ID=hvri php  empty_unactive_users_lab.php
     * brief 清空未激活用户的团队（课题组）
     */

require dirname(__FILE__). '/base.php';

$u = new Upgrader;

$u->check = function() {
    //哈兽研可升级
    if ($_SERVER['SITE_ID'] == 'cf-lite' && $_SERVER['LAB_ID'] == 'hvri')  return TRUE;
};

//数据库备份
$u->backup = function() {
    $dbfile = LAB_PATH . 'private/backup/before_empty_unactive_users_lab.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile, 'user');
};

$u->upgrade = function() {
    $db = Database::factory();

    $db->query("UPDATE `user` SET `lab_id` = 0 WHERE `atime` = 0");

    echo Upgrader::ANSI_GREEN;
    echo "数据升级成功! \n";
    echo Upgrader::ANSI_RESET;

    return TRUE;
};

//恢复数据
$u->restore = function() {
    $dbfile = LAB_PATH . 'private/backup/before_empty_unactive_users_lab.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile, 'user');
};

$u->run();
