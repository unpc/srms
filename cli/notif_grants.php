#!/usr/bin/env php
<?php
    /*
     * file notif_grants.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-12-10
     *
     * useage SITE_ID=lab LAB_ID=demo notif_grants.php
     * brief 对系统中的grant进行遍历，对到提醒时间的grant进行提醒，过期的也进行提醒
     */

require dirname(__FILE__). '/base.php';

//今天0点
$today = mktime(0, 0, 0);
$now = Date::time();
$grants = Q("grant[dtend>{$today}]");

$perpage = 10;
$start = 0;

//无可过期的grant
if (!$grants->total_count()) die;

while(count($gs = $grants->limit($start, $perpage))) {

    foreach($gs as $g) {
        $receiver = $g->user; //负责人

        //今天过期
        if ($g->dtend - $tody < 86400) {
            Notification::send('grants.over_remind_time', $receiver, [
                '%user' => Markup::encode_Q($u),
                '%grant_project'=> $g->project
            ]);
        }
        elseif ((($g->dtend - $today) / 86400) == $g->remind_time) {
            //马上过期，提醒一次
            Notification::send('grants.near_remind_time', $receiver, [
                '%user'=> Markup::encode_Q($u),
                '%grant_project'=> $g->project,
                '%date'=> Date::format($g->dtend - $tody)
            ]);
        }
    }

    $start += $perpage;
}
