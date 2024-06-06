#!/usr/bin/env php
<?php
    /*
     * file set_user_gapper_id.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015-03-26
     *
     * useage SITE_ID=cf LAB_ID=nankai php set_user_gapper_id.php
     * brief 设置某个用户的 gapper_id
     */

require 'base.php';

$email = $argv[1];
$gapper_id = $argv[2];

if (!$email || !$gapper_id) {
    die("Usage: \n\n\t SITE_ID=cf LAB_ID=nankai php set_user_gapper_id.php support@geneegroup.com gapper_id\n");
} 

$user = O('user', ['email'=> $email]);

if (!$user->id) {
    die("邮箱填写有误!\n");
}

$user->set('gapper_id', $gapper_id)->save();
