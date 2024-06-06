#!/usr/bin/env php
<?php
    /*
     * file 00-init.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013-09-05
     *
     * useage SITE_ID=cf LAB_ID=may php 00-init.php
     * brief 进行notification的相关rpc测试的初始化配置
     */

require dirname(dirname(dirname(__FILE__))). '/base.php';

require ROOT_PATH. 'unit_test/helpers/environment.php';

class E extends Environment {};

//环境初始化
E::init_site();


for ($i = 0; $i < 3; $i ++) {
    //创建了3个group
    $last_group = E::add_group($i);
}

for($i = 0; $i < 3; $i ++) {
    $last_role = E::add_role($i);
}

for ($i = 1; $i < 100; $i ++) {
    $user = E::add_user($i);
    $user->group = $last_group;
    $last_group->connect($user);
    $user->save();

    if (! ($i % 3)) {
        E::set_role($user, $last_role);
    }
}

//创建了100个用户，99个为for循环创建，1个为genee用户
//创建了3个组织机构, id最大的组织机构包含了99个用户
//创建了2个角色，id最大的角色包括了33个用户
