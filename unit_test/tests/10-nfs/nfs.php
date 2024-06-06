<?php

/*
* @file nfs.php
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 文件系统环境架设脚本之一
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/nfs
*/

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:nfs模块\n\n";

Environment::init_site();

$role1 = Environment::add_role('文件系统管理员',[
	'管理文件分区',
]);
$role2 = Environment::add_role('课题组文件系统管理员',[
	'管理本实验室成员的文件分区',
]);

$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('吴凯');
$user3 = Environment::add_user('吴天放');

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);

$lab1 = Environment::add_lab('吴凯课题组', $user2);

Environment::set_lab($user3, $lab1);
