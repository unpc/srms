#!/usr/bin/env php
<?php
require 'base.php';
//echo $argv[];
//fwrite(STDOUT, '请输入统计起始时间xxxx-xx-xx：');
//$start = strtotime(fgets(STDIN));
$date = date_create();
// 如果要追溯之前统计数据, 就把下面这行解除注释
// 例如2020Q1数据, 就随便写一个2020Q2的日期即可
// $date = date_create('2020-04-01');
$year = date_format($date, 'm') < 4 ? date_format($date, 'Y') - 1 : date_format($date, 'Y');
$month = floor((date_format($date, 'm') / 12) * 4) * 3 - 2;
$month = date_format($date, 'm') < 4 ? 10 : $month;
$start = strtotime("2009/1/1 00:00:00");
$new_start = strtotime("{$year}/{$month}/1 00:00:00");
$end = strtotime("{$year}/{$month}/1 00:00:00 + 3 month -1 second");
$db = Database::factory();
$data = [];
$data[] = Config::get('page.title_default');
$data[] = Q("user")->total_count();
$data[] = Q("user[ctime={$new_start}~{$end}]")->total_count();
$data[] = Q("user[atime={$new_start}~{$end}]")->total_count();
$data[] = Q('lab')->total_count();
$data[] = Q("lab[ctime={$new_start}~{$end}]")->total_count();
$data[] = Q("lab[atime={$new_start}~{$end}]")->total_count();
$data[] = Q('equipment')->total_count();
$data[] = Q("equipment[ctime={$new_start}~{$end}]")->total_count();
$data[] = Q("equipment[control_mode]")->total_count();
//人员活跃度
$query = "SELECT COUNT(DISTINCT `user_id`) FROM `eq_record` WHERE `dtend` BETWEEN %d AND %d;";
$data[] = $db->value($query, $new_start, $end);
//课题组活跃度
$query = "SELECT COUNT(DISTINCT(`r`.`id2`)) FROM `_r_user_lab` AS `r` LEFT JOIN `eq_record`  as `e` on `e`.`user_id`=`r`.`id1` where `e`.`dtend`>=%d and `e`.`dtend`<= %d";
$data[] = $db->value($query, $new_start, $end);
//总仪器活跃度
$query = "SELECT COUNT(DISTINCT `equipment_id`) FROM `eq_record` WHERE `dtend` BETWEEN %d AND %d;";
$data[] = $db->value($query, $new_start, $end);
$data[] = Q("eq_record[dtstart={$start}~{$end}]")->total_count();
$data[] = round((Q("eq_record[dtend>{$start}][dtstart={$start}~{$end}]")->SUM('dtend') - Q("eq_record[dtend>{$start}][dtstart={$start}~{$end}]")->SUM('dtstart')) / 3600, 2);
$data[] = Q("eq_reserv[dtstart={$start}~{$end}]")->total_count();
$data[] = round((Q("eq_reserv[dtend>{$start}][dtstart={$start}~{$end}]")->SUM('dtend') - Q("eq_reserv[dtend>{$start}][dtstart={$start}~{$end}]")->SUM('dtstart')) / 3600, 2);
$data[] = (float) Q("billing_transaction[outcome>0][ctime={$start}~{$end}]")->SUM('outcome');
$data[] = Config::get('system.base_url');
// 按月计算使用人数
$durings = [
    strtotime("{$year}/{$month}/1 00:00:00"),
    strtotime("{$year}/{$month}/1 00:00:00 + 1 month"),
    strtotime("{$year}/{$month}/1 00:00:00 + 2 month"),
    strtotime("{$year}/{$month}/1 00:00:00 + 3 month"),
];
foreach ($durings as $key => $during) {
    if ($key == 0){
        $start = $during;
        continue;
    }
    $end = $during;
    $query = "SELECT COUNT(DISTINCT `user_id`) FROM `eq_record` WHERE `dtend` BETWEEN %d AND %d;";
    $data[] = $db->value($query, $start, $end);
    $start = $end;
}
echo "客户名,成员总数 (人),添加成员总数 (人),激活成员数 (人),课题组总数 (个),添加课题组总数 (个),激活课题组数 (个),仪器总数 (台),新添加仪器数 (台),被控制的仪器总数 (台),人员活跃度,课题组活跃度,仪器活跃度,仪器使用总次数,仪器使用总时长,仪器预约次数,仪器预约时长,使用仪器总花费,系统地址," , $month , "月使用人数," , $month + 1 , "月使用人数," , $month + 2 , " 月使用人数\n";
echo join(',', $data)."\n";
// exec("echo " . join(',', $data) . " > /volumes/report_".SITE_ID."_".LAB_ID.".csv &");
// $output = new CSV('report_'.SITE_ID.'_'.LAB_ID.'.csv', 'w');
// $output->write($data);
// $output->close();
//$mail = new email();
//$mail->to(['2398594866@qq.com']);
//$subject = Config::get('page.title_default'). '年度总结报表'.'('.Date::format($start, 'Y-m-d').'~'.Date::format($end, 'Y-m-d').')';
//$mail->subject($subject);
//$mail->body(join(",", $data), new Markup(join(",", $data)));
//$mail->send();

