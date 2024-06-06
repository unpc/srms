#!/usr/bin/env php
<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    //存在equipment表，进行升级
    $db = Database::factory();

    //存在equipment表就尝试进行升级矫正
    return !$db->value("SHOW TABLES LIKE '_r_role_perm'");
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {

    foreach (Q("role") as $role) {
        $ps = $role->perms;
        if ($ps) foreach ($ps as $p_name => $on) {
            if ($on != 'on') continue;
            $perm = O('perm', ['name' => $p_name]);
            if (!$perm->id) continue;

            if ($role->id < 0) {
                $role_new = O('role', ['weight' => $role->id]);
                // echo $perm->name,  " => " , $role_new->name, "\n";
                $role_new->connect($perm);
                $role_new->set('perms', NULL)->save();
                $role->delete();
                $role_name = $role_new->name;
            }
            else {
                // echo $perm->name,  " => " , $role->name, "\n";
                $role->connect($perm);
                $role->set('perms', NULL)->save();
                $role_name = $role->name;
            }
            Upgrader::echo_success("{$role_name} 中 {$perm->name} 权限关联成功!");
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
