#!/usr/bin/env php
<?php

require dirname(dirname(dirname(__FILE__))) . '/base.php';

$u = new Upgrader;

$u->check = function() {
    //检测是否需要进行升级
    //为lims站点时, 进行升级
    if (!Module::is_installed('orders')) {
        return FALSE;
    }
    return $_SERVER['SITE_ID'] == 'lab';
};

//数据库备份
$u->backup = function() {
    $dbfile = LAB_PATH . 'private/backup/before_upgrade_orde_status.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    $db = Database::factory();
    return $db->snapshot($dbfile);
};


//升级
$u->upgrade = function() {

    Upgrader::echo_title('开始升级');
	$name = 'order';
    $db = Database::factory();
	$schema = ORM_Model::schema($name);
	if ($schema) {
		$ret = $db->prepare_table($name, $schema);
        if (!$ret) {
            echo $name."表更新失败\n";
        }
	}

	/*
	以前的申购中(1)变为现在的 待确认(1) REQUESTING
	以前的已订出(2)变为现在的 待付款(4) READY_TO_TRANSFER
	以前的已到货(3)变为现在的 待付款(4) READY_TO_TRANSFER  deliver_status 为RECEIVED(1)
	以前的已取消(4)变为现在的 已取消(10) CANCELED
	*/

	$ret = $db->query('update `order` set status=10 where status=4');
	if ($ret) {
		$ret = $db->query('update `order` set status=4  where status=2');
		if ($ret) {
			$ret = $db->query('update `order` set status=4,deliver_status=1  where status=3');
		}
	}

    Upgrader::echo_success('升级完成');
};


//恢复数据
$u->restore = function() {
    $dbfile = LAB_PATH . 'private/backup/before_upgrade_orde_status.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();