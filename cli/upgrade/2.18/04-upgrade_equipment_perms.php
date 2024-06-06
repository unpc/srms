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
    if (in_array(LAB_ID, ['csu', 'jiangnan'])) {
        Upgrader::echo_success("该学校不需要进行该升级.");
        return TRUE;
    }

    foreach (Q("role") as $role) {
        $edit = O('perm', ['name' => '修改负责仪器的使用设置']);
        $delete = O('perm', ['name' => '删除负责仪器']);

        if ($edit->id && Q("{$role} {$edit}")->total_count() > 0) {
            if ($delete->id) {
                $role->connect($delete);
                Upgrader::echo_success("{$role->name} 中 {$delete->name} 权限关联成功!");
            }
        }
    }
    Upgrader::echo_success("Done.");

    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
