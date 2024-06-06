#!/usr/bin/env php
<?php

//修改2.10在错误进行升级后导致的部分数据丢失问题

//查增量脚本
require 'base.php';

$old_db = $argv[1];
$wrong_db = $argv[2];
$right_db = $argv[3];
$increment_db = $argv[4];

//不存在旧数据、新数据、增量数据库名称
if (!$old_db || !$wrong_db || !$right_db || !$increment_db) {
    die("Usage:\nSITE_ID=xx LAB_ID=xxxx php check_2.10_increment.php old_db wrong_db right_db increment_db
old_db 为2.9.x的数据库
wrong_db 为2.10错误升级后的数据库
right_db 为2.10的正确升级后的数据库
increment_db 为增量备份数据库\n");
}

//前缀设定为空
Config::set('database.prefix', NULL);

$old = Database::factory($old_db);
$wrong = Database::factory($wrong_db);
$right = Database::factory($right_db);

//存储增量数据
$data = [];
$max = [];

$old_tables_query = $old->query("SHOW TABLES");
while (($table = current($old_tables_query->row('num'))) != NULL) {

    //针对_开头的表不予查询, 属于关系表或者_auth配置表
    //if ($table[0] == '_p') continue;

    $max_id_sql = strtr('SELECT MAX(`id`) FROM `%table`', [
        '%table'=> $table, 
    ]);

    $old_max_id = $old->value($max_id_sql);
    $wrong_max_id = $wrong->value($max_id_sql);

    //比对max_id
    if ($wrong_max_id > $old_max_id) {

        $max[$table] = $old_max_id;

        //获取增量数据
        $diff_sql = strtr('SELECT * FROM `%table` WHERE `id` > %max_id', [
            '%table'=> $table,
            '%max_id'=> $old_max_id,
        ]); 

        $diff_query = $wrong->query($diff_sql);
        while ($_diff = $diff_query->row('assoc')) {
            $data[$table][] = $_diff;
        }
    }
}

if (count($data)) {

    echo Upgrader::echo_fail('发现增量!');

    foreach($data as $table => $d) {
        $title = strtr('%table发现增量 (%count) 条', [
            '%table'=> $table,
            '%count'=> count($d),
        ]);

        echo Upgrader::echo_success($title);
    }

    $create_db_sql = strtr("CREATE DATABASE %db", [
        '%db'=> $increment_db, 
    ]);

    //创建增量
    $old->query($create_db_sql);

    $increment = Database::factory($increment_db);

    foreach($max as $table => $max_id) {

        //创建对应的table
        $create_table_sql = $wrong->query(strtr("SHOW CREATE TABLE `%table`", [
            '%table' => $table,
        ]))->row('num')[1];

        $increment->query($create_table_sql);

        //创建table
        $wrong->query($table_query);
        $insert_sql = strtr("INSERT INTO `%table` SELECT * FROM `%wrong_db`.`%table` WHERE id > %id", [
            '%wrong_db'=> $wrong_db,
            '%table'=> $table,
            '%id'=> $max_id,
        ]);

        //increment进行备份
        $increment->query($insert_sql);

        //right进行同步
        $right->query($insert_sql);
    }

    $success = strtr("\n已将增量导入至%increment_db中进行备份, 并正确合并到%right_db中", [
        '%increment_db'=> $increment_db,
        '%right_db'=> $right_db,
    ]);

    echo Upgrader::echo_success($success);

}
else {
    //无数据增量
    echo Upgrader::echo_success('无增量数据');
}
