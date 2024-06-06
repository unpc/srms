#!/usr/bin/env php
<?php
    /*
     * file  00-init.php
     * author Yu Li <rui.ma@geneegroup.com>
     * date 2013-05-17
     *
     * useage SITE_ID=cf LAB_ID=ly php 00-init.php
     * brief 用来进行api_people 和  api_lab 的 测试环境的简单环境初始化测试
     */

require dirname(dirname(dirname(__FILE__))). '/base.php';

//加载unit_test  environment.php
require ROOT_PATH. 'unit_test/helpers/environment.php';

Class E extends Environment {};

//测试环境初始化
E::init_site();


$root_group = O('tag', 1);
$group_l1 = O('tag');
$group_l1->name = '组织机构1';
$group_l1->parent = $root_group;
$group_l1->root = $root_group;
$group_l1->save();

$group_l2 = O('tag');
$group_l2->name = '组织机构2';
$group_l2->parent = $group_l1;
$group_l2->root = $root_group;
$group_l2->save();



//系统初始化后会创建genee的账号
$user = O('user', '1');
$user->card_no = 123456789;
$user->card_no_s = 12345;
$user->address = 'address';
$user->member_type = 11; // 科研助理
$user->gender = 1; // 女
$user->ctime = 111111111;
$user->creator = $user;
$user->auditor = $user;
$user->ref_no = 54321;
$user->major = 'major';
$user->group = $group_l2;
$user->organization = 'organization';
$user->save();

/*
Array
(
    [token] => genee|database
    [email] => genee@geneegroup.com
    [name] => 技术支持
    [gender] => 女
    [card_no] => 123456789
    [card_no_s] => 12345
    [ctime] => 111111111
    [phone] => 83719730
    [address] => address
    [group] => Array
        (
            [0] => 组织机构1
            [1] => 组织机构2
        )

    [member_type] => 科研助理
    [creator] => 技术支持
    [auditor] => 技术支持
    [ref_no] => 54321
    [major] => major
    [organization] => organization
)
*/




//创建实验室
$lab = E::add_lab('genee', $user);
$lab->creator = $user;
$lab->auditor = $user;
$lab->owner = $user;
$lab->description = 'description';
$lab->ctime = 22222222;
$lab->contact = 'contact';
$lab->group = $group_l1;
$lab->save();



/*
Array
(
    [creator] => 技术支持
    [auditor] => 技术支持
    [owner] => 技术支持
    [name] => genee
    [description] => description
    [ctime] => 22222222
    [contact] => contact
    [group] => Array
        (
            [0] => 组织机构1
        )

    [owner_token] => genee|database
    [owner_email] => genee@geneegroup.com
    [owner_phone] => 83719730
)
*/




//简历verify用户的信息
