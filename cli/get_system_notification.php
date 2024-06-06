#!/usr/bin/env php
<?php
require 'base.php';

$noti_keys = [
			'notification.activate',
			'notification.activate.notification', 
			'notification.add',
			'notification.add.notification',
			'notification.be_reserved',
			'notification.be_reserved.notification',
			'notification.charge_need_approve',
			'notification.charge_need_approve.notification',
			'notification.confirm_reserv',
			'notification.confirm_reserv.notification',
			'notification.default.send_by',
			'notification.edit_record',
			'notification.envmon.sensor.nodata',
			'notification.envmon.sensor.warning',
			'notification.eq_banned',
			'notification.eq_banned.item',
			'notification.eq_banned.item.notification',
			'notification.eq_banned.notification',
			'notification.eq_charge.charge_need_approve',
			'notification.eq_reserv.be_reserved',
			'notification.eq_reserv.confirm_reserv',
			'notification.eq_reserv.misstime',
			'notification.eq_reserv.misstime.self',
			'notification.eq_reserv.overtime',
			'notification.eq_reserv.overtime.self',
			'notification.eq_sample.eq_sample_applied',
			'notification.eq_sample.eq_sample_approved',
			'notification.eq_sample.eq_sample_canceled',
			'notification.eq_sample.eq_sample_over_min_notification_fee',
			'notification.eq_sample.eq_sample_refused',
			'notification.eq_sample.eq_sample_tested',
			'notification.eq_sample_applied',
			'notification.eq_sample_applied.notification',
			'notification.eq_sample_approved',
			'notification.eq_sample_approved.notification',
			'notification.eq_sample_canceled',
			'notification.eq_sample_canceled.notification',
			'notification.eq_sample_over_min_notification_fee',
			'notification.eq_sample_over_min_notification_fee.notification',
			'notification.eq_sample_refused',
			'notification.eq_sample_refused.notification',
			'notification.eq_sample_tested',
			'notification.eq_sample_tested.notification',
			'notification.equipment.eq_banned',
			'notification.equipments.edit_record',
			'notification.equipments.eq_banned',
			'notification.equipments.eq_banned.eq',
			'notification.equipments.in_service',
			'notification.equipments.nofeedback',
			'notification.equipments.out_of_service',
			'notification.equipments.report_problem',
			'notification.equipments.training_apply',
			'notification.equipments.training_approved',
			'notification.equipments.training_deleted',
			'notification.equipments.training_rejected',
			'notification.billing.refill',
			'notification.handlers',
			'notification.in_service',
			'notification.in_service.notification',
			'notification.labs.register',
			'notification.misstime',
			'notification.misstime.notification',
			'notification.misstime.notification.self',
			'notification.misstime.self',
			'notification.nofeedback',
			'notification.nofeedback.notification',
			'notification.notification.refill',
			'notification.order_canceled',
			'notification.order_canceled.notification',
			'notification.order_confirmed',
			'notification.order_confirmed.notification',
			'notification.order_received',
			'notification.order_received.notification',
			'notification.orders.order_canceled',
			'notification.orders.order_confirmed',
			'notification.orders.order_received',
			'notification.out_of_service',
			'notification.out_of_service.notification',
			'notification.overtime',
			'notification.overtime.notification',
			'notification.overtime.notification.self',
			'notification.overtime.self',
			'notification.people.activate',
			'notification.people.add',
			'notification.people.signup',
			'notification.record_modified_content',
			'notification.record_modified_content_dtend',
			'notification.record_modified_content_dtstart',
			'notification.record_modified_content_eq_charge',
			'notification.record_modified_content_samples',
			'notification.refill',
			'notification.register',
			'notification.register.notification',
			'notification.report_problem',
			'notification.report_problem.notification',
			'notification.sensor.nodata',
			'notification.sensor.warning',
			'notification.signup',
			'notification.signup.notification',
			'notification.sms.test',
			'notification.training_apply',
			'notification.training_apply.notification',
			'notification.training_approved',
			'notification.training_approved.notification',
			'notification.training_deleted',
			'notification.training_deleted.notification',
			'notification.training_rejected',
			'notification.training_rejected.notification',
        ];
$transactions = [];
foreach ($noti_keys as $k) {
    $transaction = [];
    $conf_keys = explode('.',$k);
    $module_name = $conf_keys['1'];

    if (!Module::is_installed($module_name) && isset($conf_keys['2']) && $conf_keys['2'] != 'notification') {
        unset($conf_keys);
        //如果没安装module_name,unset
    }
    elseif (!Module::is_installed($module_name) && $conf_keys['2'] == 'notification') {
    	$upgrade = implode('.', $conf_keys);
    	echo $upgrade."出现在注释或者升级脚本中";
    	echo "\n";
    	unset($conf_keys);
    }
    elseif (count($conf_keys) == 2) {
    	$conf_key = implode('.', $conf_keys);
    	$notification = Config::get($conf_key);
	    $transaction['tag'] = $k;
	    $transaction['title'] = $notification['title'];
	    $transaction['description'] = $notification['description'];
	    $transaction['body'] = $notification['body'];
	    if($notification['strtr']) {
	    	$array = $notification['strtr'];
			foreach ($array as $key => $value) {
				$array[$key] = $key.'=>'.$value; 
			}
			$transaction['strtr'] = implode(',', $array); 		
		}
		else{
			$transaction['strtr'] = '';
		}	    
	    $transactions[$k] = $transaction;
    }
    else {
        //安装module后，获取config获取数据
        unset($conf_keys['1']);
        $conf_key = implode('.', $conf_keys);
        $notification = Config::get($conf_key);
	    $transaction['tag'] = $k;
	    $transaction['title'] = $notification['title'];
	    $transaction['description'] = $notification['description'];
	    $transaction['body'] = $notification['body'];

	    if($notification['strtr']) {
	    	$array = $notification['strtr'];
			foreach ($array as $key => $value) {
				$array[$key] = $key.'=>'.$value; 
			}

			$transaction['strtr'] = implode(',', $array); 		
		}
		else{
			$transaction['strtr'] = '';
		}	
	    $transactions[$k] = $transaction;
    }
}
$csv = new CSV( 'export/notification.csv', 'w');
$csv_error = new CSV('export/notification_error.csv','w');
foreach ($transactions as $t) {

	if(!isset($t[description])) {
		$csv_error->write(['tag' => $t['tag'],]);
		unset($t);
	}
	else{
		$csv->write([
		'tag' => $t['tag'],
		'description' => $t['description'],
		'title' => $t['title'],
		'body' => $t['body'],
		'strtr' => $t['strtr'],
		]);
	}
	
	
}
$csv->close();
echo '已经导出notification配置CSV在export/notification.csv';
echo "\n";
echo '已经导出其他notification的CSV在export/notification_error.csv';
echo "\n";
