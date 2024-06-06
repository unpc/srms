<?php
/*
* @file attachment.php
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 附件管理测试用例环境架设脚本
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/attachment
*/

require_once(ROOT_PATH . 'unit_test/helpers/environment.php');
echo "开始环境自动生成:nfs模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('吴凯');
$user3 = Environment::add_user('吴天放');
$user4 = Environment::add_user('马睿');


$role1 = Environment::add_role('附件上传管理员', ['添加/修改所有实验室成果', '查看所有实验室成果', '上传/创建所有仪器的附件', '修改所有仪器的送样']);
$role2 = Environment::add_role('附件编辑管理员', ['添加/修改所有实验室成果', '查看所有实验室成果', '更改/删除所有仪器的附件', '修改所有仪器的送样']);
$role3 = Environment::add_role('附件删除管理员', ['添加/修改所有实验室成果', '查看所有实验室成果', '更改/删除所有仪器的附件', '修改所有仪器的送样']);
$role4 = Environment::add_role('附件下载测试员'); 

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);
Environment::set_role($user3, $role3);
Environment::set_role($user4, $role4);

Environment::add_equipment('深蓝II', [$user1], [$user1]);

echo "\n环境生成完毕\n";
