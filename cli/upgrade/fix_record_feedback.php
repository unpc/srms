#!/usr/bin/env php
<?php
    /*
     * file fix_record_feedback.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2012-10-17
     *
     * useage SITE_ID=cf LAB_ID=nankai php fix_record_feedback.php
     * brief 修正之前由于Glogon反馈为故障时错误保存的使用记录
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader();

//备份数据
$u->backup = function() {

    $dbfile = LAB_PATH.'private/backup/before_fix_record_feedback.sql';
    File::check_path($dbfile);

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");

    $db = Database::factory();
    return $db->snapshot($dbfile);
};

// 检查是否升级
$u->check = function() {
    $db = Database::factory();
    $has_error_feedback_record = $db->value("SELECT COUNT(status) FROM eq_record WHERE status=2");

    if ($has_error_feedback_record) return TRUE;
    return FALSE;

};

//升级
$u->upgrade = function() {

    $db = Database::factory();

    $sql = 'UPDATE eq_record SET status=-1 WHERE status=2';
    if ($db->query($sql)) {
        Upgrader::echo_success('修正仪器使用记录错误反馈状态成功!');
    }
    else {
        Upgrader::echo_fail('修正仪器使用记录错误反馈状态失败!');
    }
};

//升级检测
$u->verify = function() {

    $db = Database::factory();

    $has_error_feedback_record = $db->value("SELECT COUNT(status) FROM eq_record WHERE status=2");

    if ($has_error_feedback_record) return FALSE;
    return TRUE;
};

//恢复数据
$u->restore = function() {

    $dbfile = LAB_PATH.'private/backup/before_fix_record_feedback.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
