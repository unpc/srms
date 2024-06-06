#!/usr/bin/env php
<?php
    /*
     * file  00-init.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-05-17
     *
     * useage SITE_ID=cf LAB_ID=may php 00-init.php
     * brief 用来进行billing_api测试环境的简单环境初始化测试
     */

require dirname(dirname(dirname(__FILE__))). '/base.php';

//加载unit_test  environment.php
require ROOT_PATH. 'unit_test/helpers/environment.php';

Class E extends Environment {};

//测试环境初始化
E::init_site();

//系统初始化后会创建genee的账号
$user = O('user', '1');

//创建实验室
$lab = E::add_lab('genee', $user);

//设定为单财务模式
$GLOBALS['preload']['billing.single_department'] = FALSE;

//增加财务部门
$department = E::add_department('department');

//设定department的标识名
$department->nickname = 'department';
$department->save();

//财务账号
$account = E::add_account($lab, $department);

//创建一个财务明细，充值1000元
E::add_transaction($account, $user, '1000');

//创建一个财务明细，扣费 50元
E::add_outcome_transaction($account, $user, '50');

//最终情况
//余额 950
//总收入1000
//总支出50
