#!/usr/bin/env php
<?php
   /*
	* @file	   nankai_sample_time_change.php
	* @author  Rui Ma<rui.ma@geneegroup.com>
	* @date	   2012.08.13
    *
	* @brief   修改南开送样预约时间修正
	* @usage: SITE_ID=cf LAB_ID=nankai ./nankai_sample_time_change.php
	*/

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

//检查是否需要进行系统升级
$u->check = function() {
	if (LAB_ID != 'nankai') {
		return FALSE;
	}

	$db = Database::factory();
	$ftime_column_existed = $db->value('SHOW COLUMNS FROM `eq_sample` WHERE field LIKE "ftime"');
	if (!$ftime_column_existed) return TRUE;
	return FALSE;
};

//数据库备份
$u->backup = function() {
	$dbfile = LAB_PATH . 'private/backup/before_change_nankai_sample_time.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

//升级脚本
$u->upgrade = function() {
    $db = Database::factory();
    //增加数据库column
    try {
        if ($db->query('ALTER TABLE  `eq_sample` ADD  `ftime` INT NOT NULL DEFAULT "0"')) {
            Upgrader::echo_success('数据库中送样记录增加取样时间列成功!');
        }
        else {
            Upgrader::echo_fail('数据库中送样记录增加取样时间列失败!');
            throw new Exception;
        }

        if ($db->query('UPDATE `eq_sample` as `es` SET `es`.`ftime` = `es`.`dtend`')) {
            Upgrader::echo_success('修改送样记录取样时间成功! ');
        }
        else {
            Upgrader::echo_fail('修改送样记录取样时间失败! ');
            throw new Exception;
        }

        if ($db->query('UPDATE `eq_sample` as `es` SET `es`.`dtend` = `es`.`dtstart`')) {
            Upgrader::echo_success('修改送样记录结束时间成功! ');
        }
        else {
            Upgrader::echo_fail('修改送样记录结束时间失败! ');
            throw new Exception;
        }
    }
    catch(Exception $e) {
        return FALSE;
    }

};

//恢复数据
$u->restore = function() {
	$dbfile = LAB_PATH . 'backup/before_change_nankai_sample_time.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();
