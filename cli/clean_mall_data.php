#!/usr/bin/env php
<?php
    /*
     * file 
     * author Yu Li <yu.li@geneegroup.com>
     * date 2014-06-16
     *
     * useage SITE_ID=lab LAB_ID=demo php clear_mall_data.php
     * brief
     */

require 'base.php';
$shorttopts = 'n:';
$opts = getopt($shorttopts);
$key = $opts['n'] ?: 'nankai';

$db = Database::factory();

$db->query("DELETE FROM `mall_user` where `source`='{$key}'");
$db->query("DELETE FROM `mall_order` where `source`='{$key}'");
$db->query("DELETE FROM `order` where `source`='{$key}'");

Mall::unbind($key);
Lab::set('last_synced_mall_order_activity',NULL);

echo "done\n";
