#!/usr/bin/env php
<?php
    /*
     * file 06-sort_old_data.php
     * author Yusheng Wang <yusheng.wang@geneegroup.com>
     * date 2018-05-30
     *
     * useage SITE_ID=cf LAB_ID=test php 06-sort_old_data.php
     * brief RQ163734中使用记录历史数据abbr升级脚本
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $query = "SHOW TABLES LIKE 'eq_record'";
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

    // update equipment abbr
    foreach(Q('equipment') as $equipment) {
        // $using_abbr = PinYin::code($equipment->user_using->name);
        $c = [];
        $contacts = Q("{$equipment}<contact user");
        foreach ($contacts as $contact) {
            $c[] = PinYin::code($contact->name);
        }
        $contacts_abbr = join(', ', $c);
        $location_abbr = PinYin::code($equipment->location . $equipment->location2);

        $db = Database::factory();
        $query = "UPDATE `equipment` SET
            `contacts_abbr` = '{$contacts_abbr}',
            `location_abbr` = '{$location_abbr}'
            WHERE `id` = {$equipment->id}";
        $db->query($query);
    }

    Upgrader::echo_success("\nequipment abbr 数据升级成功! \n");

    // update eq_record abbr
    foreach(Q('eq_record') as $record) {
        $eq_abbr = PinYin::code($record->equipment->name);
        $user_abbr = PinYin::code($record->user->name);
        $agent_abbr = PinYin::code($record->agent->name);

        $db = Database::factory();
        $query = "UPDATE `eq_record` SET
            `eq_abbr` = '{$eq_abbr}',
            `user_abbr` = '{$user_abbr}',
            `agent_abbr` = '{$agent_abbr}'
            WHERE `id` = {$record->id}";
        $db->query($query);
    }

    Upgrader::echo_success("\neq_record abbr 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
