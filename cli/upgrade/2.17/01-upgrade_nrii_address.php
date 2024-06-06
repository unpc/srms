#!/usr/bin/env php
<?php
/*
 * useage SITE_ID=xx LAB_ID=xx php 01-upgrade_nrii_address.php
 * brief 将nrii地址信息的数据源换为高德API
 * brief 处理nrii地址信息历史数据
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $query1 = "SELECT COUNT(*) FROM `nrii_equipment` WHERE `address` LIKE 'n%'";
    $query2 = "SELECT COUNT(*) FROM `nrii_device` WHERE `address` LIKE 'n%'";
    $query3 = "SELECT COUNT(*) FROM `nrii_unit` WHERE `address` LIKE 'n%'";
    $query4 = "SELECT COUNT(*) FROM `nrii_center` WHERE `address` LIKE 'n%'";
    $results = $db->value($query1) + $db->value($query2) + $db->value($query3) + $db->value($query4);

    if(!Module::is_installed('nrii') || !$results) {
        return FALSE;
    }
    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};


$u->upgrade = function() {
    Nrii_Address::sync_address();

    foreach (Q("nrii_equipment") as $equipment) {
        $address = explode('n', $equipment->address);

        $area = Config::get('address.n'.$address[2])['n'.$address[3]];
        if (!$area) continue;

        $address = O('address', ['name' => $area]);
        if (!$address->id) continue;
        $equipment->address = $address->adcode;
        $equipment->save();
    }

    foreach (Q("nrii_device") as $device) {
        $address = explode('n', $device->address);

        $area = Config::get('address.n'.$address[2])['n'.$address[3]];
        if (!$area) continue;

        $address = O('address', ['name' => $area]);
        if (!$address->id) continue;
        $device->address = $address->adcode;
        $device->save();
    }

    foreach (Q("nrii_unit") as $unit) {
        $address = explode('n', $unit->address);

        $area = Config::get('address.n'.$address[2])['n'.$address[3]];
        if (!$area) continue;

        $address = O('address', ['name' => $area]);
        if (!$address->id) continue;
        $unit->address = $address->adcode;
        $unit->save();
    }

    foreach (Q("nrii_center") as $center) {
        $address = explode('n', $center->contact_address);

        $area = Config::get('address.n'.$address[2])['n'.$address[3]];
        if (!$area) continue;

        $address = O('address', ['name' => $area]);
        if (!$address->id) continue;
        $center->contact_address = $address->adcode;
        $center->save();
    }
    Upgrader::echo_success("升级完成");
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
