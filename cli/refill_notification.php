#!/usr/bin/env php
<?php

require 'base.php';


$now = time();

$settings = (array) Lab::get('notification.refill') + (array) Config::get('notification.refill');
$enable_notification = $settings['enable_notification'];
if ($enable_notification) {
	$balance = $settings['balance'];
	$period = $settings['period'] * 86400; // 24 * 60 * 60;
	
	$labs = Q('lab');
	$departments = Q('billing_department');
	foreach ($labs as $lab) {
		if (!$lab->owner->id) continue;

		$sent_date = (int) $lab->last_refill_notification_date;
		if ($sent_date && $sent_date + $period >= $now) continue;

		$found = FALSE;	
		foreach ($departments as $dept) {
			$account = O('billing_account', ['lab'=>$lab, 'department'=>$dept]);
			if (!$account->id) continue;
			
			if ((float)$account->balance < (float)$balance) {
				$found = TRUE;

                if ($GLOBALS['preload']['billing.single_department']) {
                    $notif_key = 'billing.refill.unique';
                }
                else {
                    $notif_key = 'billing.refill';
                }
                Notification::send($notif_key, $lab->owner, [
                	'%lab'=>Markup::encode_Q($lab),
                    '%department'=>Markup::encode_Q($dept),
                    '%balance'=>$account->balance,
                    '%alert_line'=>Number::currency($balance)
                ]);

			}

		}

		if ($found) {	
			$lab->last_refill_notification_date = $now;
			$lab->save();
		}

	}

}


