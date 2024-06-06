#!/usr/bin/env php

<?php

/**
  * @file upgrade_equipment_phone_and_email.php
  * @auther RUI MA <rui.ma@geneegroupc.com>
  * @date 2011-12-17
  
  * @brief 为所有仪器增加email和phone	
  * usage: SITE_LAB=cf LAB_ID=test ./upgrade_equipment_phone_and_email.php
  */

require '../base.php';

$u = new Upgrader();

//备份数据
$u->backup = function() {

	$dbfile = LAB_PATH.'private/backup/equipment_no_email_and_phone.sql';
	File::check_path($dbfile);

	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");
 
	$db = Database::factory();
	return $db->snapshot($dbfile);

};

// 检查是否升级
$u->check = function() {
	$db = Database::factory();

	$email_col_existed = $db->value('SHOW COLUMNS FROM equipment WHERE field LIKE "email"');
    $phone_col_existed = $db->value('SHOW COLUMNS FROM equipment WHERE field LIKE "phone"');

	if ($email_col_existed && $phone_col_existed) {
        //同时存在email和phone行才可进行升级
		return TRUE;
	}
 
	return FALSE;
};

//升级数据
$u->upgrade = function() {
	$db = Database::factory();

    $equipments = Q('equipment[status!='. EQ_Status_Model::NO_LONGER_IN_SERVICE. ']');
	
	$upgraded_equipment_count = 0;
	
	foreach($equipments as $equipment) {
        $contacts =  Q("$equipment user.contact");
        if (!$equipment->phone) {
            foreach($contacts as $c) {

                if($c->phone) {
                    $equipment_phone = $c->phone; 
                    break;
                }

            }
            $equipment->phone = $equipment_phone;
        }

        if (!$equipment->email) {
            foreach($contacts as $c) {
                if($c->email) {
                    $equipment_email = $c->email;
                    break; 
                } 
            }
            $equipment->email = $equipment_email;
        }

		if ($equipment->save()) {
			echo T("%equipment[%equipment_id] 的联系邮箱设置为：%email, 联系电话设置为: %phone \n", ['%equipment'=>$equipment->name, '%equipment_id'=>$equipment->id, '%email'=>$equipment->email, '%phone'=>$equipment->phone]);
			$upgraded_equipment_count ++;
		}
	}
    Upgrader::echo_success(T("共更新 %upgraded_equipment_count 台仪器的联系邮箱和联系电话\n", ['%upgraded_equipment_count'=>$upgraded_equipment_count]));
};

//恢复数据
$u->restore = function() {

	$dbfile = LAB_PATH.'backup/contact_multi_user.sql';
	File::check_path($dbfile);
	
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
	$db = Database::factory();
	$db->restore($dbfile);	
};

$u->run();
?>
