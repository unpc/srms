<?php
// 【临时】18年全部系统对外开放机时
// 对外开放机时：（2018.1.1—2.18.12.31）统计时段内，校外用户的测样时间（测样结束时间—测样起始时间）+ 校外用户使用记录使用时长总和
require 'base.php';

$start = strtotime('2018/01/01 00:00:00');
$end = strtotime('2018/12/31 23:59:59');
$root = Tag_Model::root('group');
$group_selector = "{$root}<parent tag[name*=校外]";

if (!Q("{$group_selector} user")->total_count()) {
    $data = 0;
} else {
    $samples = Q("{$group_selector} user<sender eq_sample[dtend>{$start}][dtstart={$start}~{$end}]");
    $records = Q("{$group_selector} user eq_record[dtend>{$start}][dtstart={$start}~{$end}]");
    $data = round(($samples->SUM('dtend') - $samples->SUM('dtstart')) / 3600, 2) + round(($records->SUM('dtend') - $records->SUM('dtstart')) / 3600, 2);
}
echo Config::get('page.title_default') , ",", $data, "\n";
