#!/usr/bin/env php
<?php
/*
 * useage SITE_ID=xx LAB_ID=xx php 03-upgrade_eq_sample_abbr
 * brief eq_sample记录写入abbr
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $query = "SHOW TABLES LIKE 'eq_sample'";
    $results = $db->query($query);
    if (!$results) {
        return FALSE;
    }

    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};


$u->upgrade = function() {
    $db = Database::factory();
    $samples = Q('eq_sample[!sender_abbr|!equipment_abbr|!operator_abbr]');

    foreach ($samples as $sample) {
        $sender_abbr = PinYin::code($sample->sender->name);
        $operator_abbr = PinYin::code($sample->operator->name);
        $equipment_abbr = PinYin::code($sample->equipment->name);

        $query = "UPDATE `eq_sample` SET
        `sender_abbr` = '{$sender_abbr}',
        `operator_abbr` = '{$operator_abbr}',
        `equipment_abbr` = '{$equipment_abbr}'
        WHERE `id` = {$sample->id}";
        $db->query($query);
    }
    Upgrader::echo_success("\neq_sample 数据升级成功! \n");
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
