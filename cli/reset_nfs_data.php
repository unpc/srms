#!/usr/bin/env php
<?php
    /*
     * file reset_nfs_data.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-08-27
     *
     * useage SITE_ID=cf LAB_ID=xx php reset_nfs_data.php
     * brief 用于对nfs相关数据清空(只清空数据库数据, 不清空实际nfs文件内容)
     */

require 'base.php';

$db = Database::factory();

//清空所有用户的使用数据
$db->query('UPDATE `user` SET `nfs_used` = 0');

//清空所有实验室的使用数据
$db->query('UPDATE `lab` SET `nfs_used` = 0');
