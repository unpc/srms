#!/usr/bin/env php
<?php

require 'base.php';

//2014年04月18日 00:36 进行升级
//升级中出现lua计费脚本错误
//需要重新进行lua计费脚本的修正后, 重新进行错误使用记录、使用预约、使用收费的修正
$start_time = mktime(0, 36, 0, 4, 18, 2014);


foreach(Q("eq_record[dtstart>={$start_time}]") as $record) {
    $record->samples = max($record->samples, 1);
    $record->save();
    echo '.';
}

foreach(Q("eq_reserv[ctime>={$start_time}]") as $reserv) {
    $reserv->save();
    echo '.';
}

foreach(Q("eq_sample[ctime>={$start_time}]") as $sample) {
    $sample->save();
    echo '.';
}

//删除错误的Equipment
Q('equipment[!name]')->delete_all();
