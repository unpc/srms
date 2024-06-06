#!/usr/bin/env php
<?php
    /*
     * file expire.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-11-04
     *
     * useage SITE_ID=lab LAB_ID=admin php expire.php
     * brief 用来对过期的进行关闭, 用来对即将过期的进行提醒
     */

require 'base.php';

$today = Date::get_day_start();

$expired_accounts = Q("lims_account[etime>0][etime<{$today}]");

$just_closed = [];

foreach ($expired_accounts as $ea) {
	if (Accounts_Sync::site_is_ready($ea) && Accounts_Sync::site_is_open($ea)) {
		$content = Accounts_Sync::site_close($ea);
		$just_closed[] = $ea->lab_name;
	}
}

if ($just_closed) {

	$email = new Email;
	$recipients = Config::get('accounts_sync.observer_emails');

	$email->to($recipients);
	$email->subject(HT('LIMS2 试用站点关闭通知'));

    $body = T("以下客户由于试用过期, 已关闭: %accounts", [
        '%accounts' => join("\n", $just_closed)
    ]);

	$email->body($body);

	$email->send();
}

//获取将来过期的账号
$to_expire_accounts = [];

$status_normal = Lims_Account_Model::STATUS_NORMAL;
//仅对状态为正常的进行修正
foreach(Q("lims_account[etime>{$time}][status={$status_normal}]") as $a) {
    //获取过期时间对应的那一天的0点, 0点表示过期
    $etime = Date::get_day_start($a->etime);
    $nday = $a->nday;
    if ($today + ($nday -1) * 86400 < $etime && $today + $nday * 86400 >= $etime) {
        $to_expire_accounts[] = $a;
    }
}

if (count($to_expire_accounts)) {

    $email = new Email;
    $recipients = Config::get('accounts_sync.observer_emails');

    $email->to($recipients);
    $email->subject(T('LIMS2 试用站点即将到期关闭!'));

    $body = T('<p>以下客户试用站点即将到期关闭: </p>');

    foreach($to_expire_accounts as $a) {
        $body .= T("<p>%accounts 过期时间 %time, 请及时处理!</p>", [
            '%accounts' => URI::anchor($a->url, $a->lab_name),
            '%time'=> Date::format('Y/m/d', $a->etime),
        ]);
    }

    $email->body(null, $body);

    $email->send();
}
