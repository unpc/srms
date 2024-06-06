#!/usr/bin/env php
<?php

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
    foreach (Q("equipment[control_mode=bluetooth]") as $equipment) {
        $equipment->bluetooth_serial_address = $equipment->control_address;
        $equipment->save();
    }

    Upgrader::echo_success("Done.");

    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
