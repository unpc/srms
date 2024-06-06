<?php
 /*
  * @file messages.php 
  * @author Jia Huang <jia.huang@geneegroup.com>
  * @date 2012-07-02
  * 
  * @brief 消息中心环境架设脚本
  * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-messages/messages
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Messages模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('柴志华');
$user2 = Environment::add_user('程莹');
$user3 = Environment::add_user('胡宁');
$user4 = Environment::add_user('沈冰');
$user5 = Environment::add_user('陈建宁');
$user6 = Environment::add_user('许宏山');
$user7 = Environment::add_user('吴天放');
$user8 = Environment::add_user('吴凯');

$role = Environment::add_role('管理员', ['']);

$group = Environment::add_group('化学系');

$lab1 = Environment::add_lab('陈建宁课题组', $user5);
$lab2 = Environment::add_lab('许宏山课题组', $user6);

Environment::set_group($user3, $group);
Environment::set_role($user4, $role);

Environment::set_lab($user7, $lab1);
Environment::set_lab($user8, $lab2);

echo "\n环境生成完毕\n";
