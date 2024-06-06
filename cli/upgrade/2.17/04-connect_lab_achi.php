#!/usr/bin/env php
<?php
    /*
     * useage SITE_ID=cf LAB_ID=test php connect_lab_achi.php
     * brief 删除achi中lab_id字段，采用_r_表关联成果和课题组
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    return $db->value('DESC `publication` lab_id');
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {

    $db = Database::factory();

    // create table _r_publication_lab
    $query = "CREATE TABLE IF NOT EXISTS `_r_publication_lab` ( `id1` bigint NOT NULL,`id2` bigint NOT NULL,`type` varchar(20) NOT NULL,`approved` int NOT NULL DEFAULT 0,PRIMARY KEY (`id1`,`id2`,`type`),KEY `id1` (`id1`,`type`),KEY `id2` (`id2`,`type`),KEY `approved` (`approved`) ) ENGINE = MyISAM DEFAULT CHARSET = utf8";
    $db->query($query);

    // connect publication lab
    $query = "SELECT `lab_id`, `id` FROM `publication` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $query = "INSERT INTO `_r_publication_lab` (`id1`, `id2`, `type`, `approved`) VALUES ({$res->id}, {$res->lab_id}, '', 0) ON DUPLICATE KEY UPDATE `approved`=0";
        $db->query($query);
    }

    // DROP COLUMNs
    $query = "ALTER TABLE publication DROP COLUMN lab_id;";
    $db->query($query);


    Upgrader::echo_success("\npublication 数据升级成功! \n");

    // create table _r_lab_award
    $query = "CREATE TABLE IF NOT EXISTS `_r_lab_award` ( `id1` bigint NOT NULL,`id2` bigint NOT NULL,`type` varchar(20) NOT NULL,`approved` int NOT NULL DEFAULT 0,PRIMARY KEY (`id1`,`id2`,`type`),KEY `id1` (`id1`,`type`),KEY `id2` (`id2`,`type`),KEY `approved` (`approved`) ) ENGINE = MyISAM DEFAULT CHARSET = utf8";
    $db->query($query);

    // connect lab award
    $query = "SELECT `lab_id`, `id` FROM `award` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $query = "INSERT INTO `_r_lab_award` (`id1`, `id2`, `type`, `approved`) VALUES ({$res->lab_id}, {$res->id}, '', 0) ON DUPLICATE KEY UPDATE `approved`=0";
        $db->query($query);
    }

    // DROP COLUMNs
    $query = "ALTER TABLE award DROP COLUMN lab_id;";
    $db->query($query);


    Upgrader::echo_success("\naward 数据升级成功! \n");

    // create table _r_patent_lab
    $query = "CREATE TABLE IF NOT EXISTS `_r_patent_lab` ( `id1` bigint NOT NULL,`id2` bigint NOT NULL,`type` varchar(20) NOT NULL,`approved` int NOT NULL DEFAULT 0,PRIMARY KEY (`id1`,`id2`,`type`),KEY `id1` (`id1`,`type`),KEY `id2` (`id2`,`type`),KEY `approved` (`approved`) ) ENGINE = MyISAM DEFAULT CHARSET = utf8";
    $db->query($query);

    // connect patent lab
    $query = "SELECT `lab_id`, `id` FROM `patent` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $query = "INSERT INTO `_r_patent_lab` (`id1`, `id2`, `type`, `approved`) VALUES ({$res->id}, {$res->lab_id}, '', 0) ON DUPLICATE KEY UPDATE `approved`=0";
        $db->query($query);
    }

    // DROP COLUMNs
    $query = "ALTER TABLE patent DROP COLUMN lab_id;";
    $db->query($query);


    Upgrader::echo_success("\npatent 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
