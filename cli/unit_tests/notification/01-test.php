#!/usr/bin/env php
<?php
    /*
     * file 01-test.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013-09-05
     *
     * useage SITE_ID=cf LAB_ID=may php 01-test.php rpc_url
     * brief 进行notification的相关api调用
     */

require dirname(dirname(dirname(__FILE__))). '/base.php';
require ROOT_PATH. 'unit_test/bin/unit_test.php';

$api = $argv[1];
if (!$api) {
    die("Usage:: SITE_ID=cf LAB_ID=demo php 01-test.php http://rui.ma.cf.gin.genee.cn/demo/api\n");
}

$rpc = new RPC($api);

Unit_Test::echo_title('notification rpc测试开始');

//1.进行错误的extract，返回false
Unit_Test::echo_title('1.进行错误的extract，返回false');
Unit_Test::assert('extract_users->(\'foobar\')', $rpc->notification->extract_users('foobar') == FALSE);

Unit_Test::echo_title('2.进行all获取，返回token');
$token = $rpc->notification->extract_users('all');
Unit_Test::assert('extract_users->(\'all\')', $token != FALSE);

Unit_Test::echo_title('3.通过token获取对应的user_ids,per_page为10');
$users = $rpc->notification->get_user_ids($token, 10);
Unit_Test::assert('get_user_ids(token, 10)', count($users) == 10);

Unit_Test::echo_title('4.通过token获取对应的user_ids, per_page为1');
$users = $rpc->notification->get_user_ids($token, 1);
Unit_Test::assert('get_user_ids(token, 1)', count($users) == 1);

Unit_Test::echo_title('5.进行all获取，尝试获取all的用户合集是否为100');
$token = $rpc->notification->extract_users('all');
$users = $rpc->notification->get_user_ids($token, 1000);
Unit_Test::assert('get_user_ids(token, 1000)', count($users) == 100);

Unit_Test::echo_title('6.进行group获取, 返回token');
$group_root = Tag_Model::root('group');
$group = Q("tag_group[root={$group_root}]:sort(id DESC)")->current();
$token = $rpc->notification->extract_users('group', $group->id);
Unit_Test::assert('extract_users->(\'group\', id)', $token != FALSE);

Unit_Test::echo_title('7.通过token，获取group下的user_ids, per_page为10');
$users = $rpc->notification->get_user_ids($token, 10);
Unit_Test::assert('get_user_ids(token, 10)', count($users) == 10);

Unit_Test::echo_title('8.通过token, 获取group下的user_ids, per_page为1');
$users = $rpc->notification->get_user_ids($token, 1);
Unit_Test::assert('get_user_ids(token, 1)', count($users) == 1);

Unit_Test::echo_title('9.重新进行group下的user_ids后去，per_page为1000');
$token = $rpc->notification->extract_users('group', $group->id);
$users = $rpc->notification->get_user_ids($token, 1000);
Unit_Test::assert('get_user_ids(token)', count($users) == 99);

Unit_Test::echo_title('10.进行role获取, 返回token');
$role = Q('role:sort(id DESC)')->current();
$token = $rpc->notification->extract_users('role', $role->id);
Unit_Test::assert('extract_users->(\'role\', id)', $token != FALSE);

Unit_Test::echo_title('11.通过token，获取role下的user_ids, per_page为10');
$users = $rpc->notification->get_user_ids($token, 10);
Unit_Test::assert('get_user_ids(token, 10)', count($users) == 10);

Unit_Test::echo_title('12.通过token, 获取role下的user_ids, per_page为1');
$users = $rpc->notification->get_user_ids($token, 1);
Unit_Test::assert('get_user_ids(token, 1)', count($users) == 1);

Unit_Test::echo_title('13.重新进行role下的user_ids后去，per_page为1000');
$token = $rpc->notification->extract_users('role', $role->id);
$users = $rpc->notification->get_user_ids($token, 1000);
Unit_Test::assert('get_user_ids(token)', count($users) == 33);
