#!/usr/bin/env php
<?php
    /*
     * 支持一条送样关联关联多条使用记录
     * 增加了_r_eq_sample_re_record关系表
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {
    // 查询到关联了使用记录的送样
    Upgrader::echo_title("\n 正在升级送样关联关联使用记录关系表 \n");
    $samples = Q("eq_sample[record]");
    foreach ($samples as $sample) {
        $record = $sample->record;
        if ($record->id) $sample->connect($record);
    }
    Upgrader::echo_success("\nuser 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
