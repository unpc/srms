#!/usr/bin/env php
<?php

/**
 * 将仪器的属性location1更新至tag_location中
 */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    return true;
};

// 数据库备份
$u->backup = function () {
    return true;
};

// 数据升级
$u->upgrade = function () {
    $equipments = Q("equipment");

    $db = Database::factory();

    $tag_location_root = Tag_Model::root('location','');

    foreach ($equipments as $equipment) {
        $sql = "SELECT `location` FROM `equipment` WHERE `id` = {$equipment->id}";

        $location = $db->query($sql)->value();

        if ($location) {
            $tag_location = O('tag_location', ['name' => $location]);
            if (!$tag_location->id) {
                $tag_location->name = $location;
                $tag_location->root = $tag_location_root;
                $tag_location->parent = $tag_location_root;
                $tag_location->save();
            }
            $arr = [];
            $arr[$tag_location->id] = $tag_location->name;

            $equipment->location_id = $tag_location->id;
            $equipment->save();
            $equipment->connect($tag_location);
        }
    }
};

// 恢复数据
$u->restore = function () {
    return true;
};

$u->run();
