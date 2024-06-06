#!/usr/bin/env php
<?php
    /*
     * file get_all_labs_infos.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-06-09
     *
     * useage SITE_ID=cf LAB_ID=nankai php get_all_labs_infos.php
     * brief 获取所有lab下的pi的相关信息
     */

require 'base.php';

$data = [];

$all_backend = Config::get('auth.backends');

$csv = new CSV('get_all_labs_infos.csv', 'w');

$csv->write([
    '课题组名称',
    'pi姓名',
    'pi登录账号',
    'pi的电子邮箱',
    'pi的联系电话',
]);

foreach(Q('lab') as $lab) {
    $pi = $lab->owner;
    list($token, $backend) = Auth::parse_token($pi->token);
    $token = Auth::make_token($token, $all_backend[$backend]['title']);

    $csv->write([
        $lab->name,
        $pi->name,
        $token,
        $pi->email,
        $pi->phone,
    ]);
}

$csv->close();
