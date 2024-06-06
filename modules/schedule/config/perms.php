<?php
$config['schedule']['管理所有成员的日程安排'] = FALSE;
$config['schedule']['查看所有成员的日程安排'] = FALSE;
$config['schedule']['管理所有成员的日程附件'] = FALSE;
$config['schedule']['查看所有成员的日程附件'] = FALSE;

$config['schedule']['管理本实验室的日程安排'] = FALSE;
$config['schedule']['管理本实验室的日程附件'] = FALSE;
$config['schedule']['查看本实验室的日程安排'] = FALSE;
$config['schedule']['查看本实验室的日程附件'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['schedule']['管理负责实验室的日程安排'] = FALSE;
    $config['schedule']['管理负责实验室的日程附件'] = FALSE;
    $config['schedule']['查看负责实验室的日程安排'] = FALSE;
    $config['schedule']['查看负责实验室的日程附件'] = FALSE;
}

$config['schedule']['#name'] = '日程管理';
$config['schedule']['#icon'] = '!schedule/icons/32/schedule.png';

// 日程管理
$config['default_roles']['课题组负责人']['default_perms'][] = "管理本实验室的日程安排";
$config['default_roles']['课题组负责人']['default_perms'][] = "管理本实验室的日程附件";
$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室的日程安排";
$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室的日程附件";
$config['default_roles']['课题组负责人']['default_perms'][] = "管理所有成员的日程安排";
$config['default_roles']['课题组负责人']['default_perms'][] = "管理所有成员的日程附件";
$config['default_roles']['课题组负责人']['default_perms'][] = "查看所有成员的日程安排";
$config['default_roles']['课题组负责人']['default_perms'][] = "查看所有成员的日程附件";
