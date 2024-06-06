#!/usr/bin/env php
<?php
/*
 * file upgrade_equipment_overtime.php
 * author Clh lianhui.cao@geneegroup.com
 * date 2021-12-22
 *
 * useage SITE_ID=cf LAB_ID=geneegroup php upgrade_equipment_overtime.php
 * brief 之前仪器超时默认开启，现在版本升级后默认为不开启，平滑过渡，升级时使用该脚本默认开启超时设置
 */

$base = dirname(dirname(dirname(__FILE__))) . '/cli/base.php';
error_log($base);
require $base;

$u = new Upgrader();

//备份数据
$u->backup = function () {
    return true;
};

// 检查是否升级
$u->check = function () {
    return true;
};

//升级
$u->upgrade = function () {
    foreach (Q("equipment") as $equipment) {
        if ($equipment->accept_reserv && !isset($equipment->accept_overtime)) {
            $equipment->accept_overtime = true;
            $equipment->allow_over_time = 0;
            $equipment->save();
        }
    }
    Upgrader::echo_success('升级lims版本后更新预约仪器默认超时设置成功！');
};

//恢复数据
$u->restore = function () {
    return true;
};

$u->run();
