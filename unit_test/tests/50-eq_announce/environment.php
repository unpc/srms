<?php
/*
 * @file environment.php
 * @author jia huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器公告模块测试用例环境架设脚本
 * @usage site_id=cf lab_id=ut q_root_path=~/lims2/ php test.php ../tests/50-eq_announce/environment
*/

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_announce模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('柴志华');
$user2 = Environment::add_user('胡宁');
$user3 = Environment::add_user('程莹');

$equ1  = Environment::add_equipment('公告仪器', $user3 ,$user3);

$role1 = Environment::add_role('仪器管理员',['添加/修改所有机构的仪器']);

Environment::set_role($user2, $role1);
