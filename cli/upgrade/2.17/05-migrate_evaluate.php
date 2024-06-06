#!/usr/bin/env php
<?php
    /*
     * file 01-migrate_evaluate.php
     * author Yusheng Wang <yusheng.wang@geneegroup.com>
     * date 2018-04-02
     *
     * useage SITE_ID=cf LAB_ID=test php 01-migrate_evaluate.php
     * brief 重新计算机主评价score，由百分制变为5分制
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    $count = $db->value("SELECT COUNT(`id`) FROM `eq_evaluate` WHERE `score` > 5");
    return (bool)$count;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {

    $db = Database::factory();

    // update score
    $eq_evaluates = Q('eq_evaluate');

    foreach ($eq_evaluates as $eq_evaluate) {
        $score = ceil($eq_evaluate->score / 20);

        $query = "UPDATE `eq_evaluate` SET
        `score` = '{$score}'
        WHERE `id` = {$eq_evaluate->id}";
        $db->query($query);
    }

    Upgrader::echo_success("\nevaluate 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
