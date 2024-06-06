<?php

/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 地理监控模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/gismon/environment
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:gismon模块\n\n";

Environment::init_site();

$role1 = Environment::add_role('gis管理员',[
    '添加/修改楼宇',
    '调整GIS监控设备位置',
    '查看仪器地图'
]);


Environment::set_role_perms('目前成员', [
    '查看仪器地图'
]);

$user1 = Environment::add_user('许宏山');

Environment::set_role($user1, $role1);
