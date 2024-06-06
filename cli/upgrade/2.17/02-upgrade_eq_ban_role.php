#!/usr/bin/env php
<?php
/*
 * useage SITE_ID=xx LAB_ID=xx php 02-upgrade_eq_ban_role.php
 * brief eq_ban模块perm改名
 * brief eq_ban模块单台仪器黑名单处理
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $query = "SELECT `equipment_id`, `id` FROM `eq_banned` ORDER BY `id`";
    $results = $db->query($query);

    if(!Module::is_installed('eq_ban') || !$results) {
        return FALSE;
    }
    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};


$u->upgrade = function() {
    foreach (Q("role") as $role) {
        $perms = $role->perms;
        if ($perms['将用户加入下属机构的仪器使用黑名单'] == 'on') {
            unset($perms['将用户加入下属机构的仪器使用黑名单']);
            $perms['管理下属机构的黑名单'] = 'on';
            $changed = TRUE;
        }
        if ($perms['将用户加入仪器使用黑名单'] == 'on') {
            unset($perms['将用户加入仪器使用黑名单']);
            $perms['管理黑名单'] = 'on';
            $changed = TRUE;
        }

        if ($changed) {
            $role->perms = $perms;
            $role->save();
        }
    }

    $db = Database::factory();
    $query = "SELECT `equipment_id`, `id` FROM `eq_banned` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        if ($res->equipment_id) {
            $query = "UPDATE `eq_banned` SET `object_name` = 'equipment', `object_id` = {$res->equipment_id} WHERE `id` = {$res->id}";
            $db->query($query);
        }
    }
    // DROP COLUMNs
    $query = "ALTER TABLE eq_banned DROP COLUMN equipment_id;";
    $db->query($query);
    // Update Indexs
    $query = "ALTER TABLE eq_banned DROP INDEX `unique`;";
    $db->query($query);
    $query = "ALTER TABLE eq_banned ADD UNIQUE KEY `unique` (`object_name`,`object_id`,`user_id`,`lab_id`);";
    $db->query($query);

    Upgrader::echo_success("升级完成");
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
