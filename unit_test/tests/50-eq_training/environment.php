<?php

/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器培训模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_training/environment
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_training模块\n\n";

Environment::init_site();

$role1 = Environment::add_role('仪器培训管理员',[
	'管理负责仪器的培训记录',
	'管理所有仪器的培训记录',
	'修改负责仪器的使用设置',
]);

$user1 = Environment::add_user('许宏山');
$user2 = Environment::add_user('柴志华');

$equ1  = Environment::add_equipment('400M核磁-1', $user1, $user1);
Environment::set_role($user1, $role1);
