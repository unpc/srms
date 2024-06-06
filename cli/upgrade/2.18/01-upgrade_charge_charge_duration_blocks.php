#!/usr/bin/env php
<?php
/*
 * useage SITE_ID=xx LAB_ID=xx php 01-up*
 * brief ORM:eq_charge 写入 charge_duration_blocks， 即计费时长
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    return false; // 暂不处理历史数据，如有反馈计费列表导出中，计费时长有误（与备注对不上）再进行此数据处理
};

//数据库备份
$u->backup = function () {
    return true;
};


$u->upgrade = function () {
    $db = Database::factory();

    $query = "SELECT `_extra`, `id` FROM `eq_charge` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $charge = O('eq_charge', $res->id);
        $charge_lua = new EQ_Charge_LUA($charge);
        $result = $charge_lua->run(['charge_duration_blocks']);
        $extra = json_decode($res->_extra, true);
        if ($result['charge_duration_blocks']) {
            $extra['charge_duration_blocks'] = strip_tags($result['charge_duration_blocks'] ? : '');
        }
        $extra = json_encode($extra, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $query = "UPDATE `eq_charge` SET
            `_extra` = '{$extra}'
            WHERE `id` = {$res->id}";
        $db->query($query);
    }

    Upgrader::echo_success("eq_charge记录写入charge_duration_blocks成功!");
};

//恢复数据
$u->restore = function () {
    return true;
};

$u->run();
