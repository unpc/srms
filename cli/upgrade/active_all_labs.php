#!/usr/bin/env php

<?php
  /*
   * @file 		active_all_labs.php
   * @author 	Rui Ma <rui.ma@geneegroup.com>
   * @date		2011.10.13
   *
   * @brief	    active all labs	
   * 
   * usage: SITE_LAB=cf LAB_ID=test ./active_all_labs.php
   */

require '../base.php';

$u = new Upgrader();

$u->check = function() {
//检测是否需要升级

    $db = Database::factory();
    $atime_col_existed = $db->value('SHOW COLUMNS FROM `lab` WHERE field LIKE "atime"');

    if (!$atime_col_existed) {
        return TRUE;//不存在说明是老版本，进行升级
    }
    
    return FALSE;

};

$u->backup = function() {
//数据库备份

    $dbfile = LAB_PATH. 'private/backup/before_active_labs.sql';
    File::check_path($dbfile);

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
    
    $db = Database::factory();
    return $db->snapshot($dbfile);

};


$u->upgrade = function() {
//升级

    $db = Database::factory();
    $db->query("ALTER TABLE `lab` add `atime` INT( 11 ) NOT NULL DEFAULT  '0'");

    $now = time();

    $labs = Q('lab');

    $db = Database::factory();

    foreach($labs as $lab) {
        $query = "update lab set lab.atime = {$now} where id = {$lab->id}";
        if ($db->query($query)) {
            echo T("实验室%lab_name激活成功\n", ['%lab_name'=>$lab->name]);
        }
    }

};

$u->verify = function() {
//检测是否升级成功
    if (Q('lab[!atime]')->total_count()) {
        return FALSE;  
    }
    return TRUE;

};

$u->restore = function() {
//恢复数据库

    $dbfile = LAB_PATH. 'private/backup/before_active_labs.sql';
    File::check_path($dbfile);
    
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
    $db = Database::factory();
    $db->restore($dbfile);

};

$u->run();
