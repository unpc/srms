<?php

$config['summary']['-管理 (所有)'] = false;

// 基础数据
$config['summary']['[大数据体系]查看所有仪器的预约情况'] = false;
$config['summary']['[大数据体系]查看所有仪器的送样情况'] = false;
$config['summary']['[大数据体系]查看所有仪器的使用记录'] = false;
$config['summary']['[大数据体系]查看所有仪器的使用收费情况'] = false;
$config['summary']['[大数据体系]查看所有仪器的故障情况'] = false;
$config['summary']['[大数据体系]查看所有成员的信用情况'] = false;


// 多维分析
$config['summary']['查看所有学院运行效益'] = false;
$config['summary']['查看所有仪器的使用汇总'] = false;
$config['summary']['查看所有仪器的效益统计'] = false;
$config['summary']['查看所有仪器的统计图表'] = false;
$config['summary']['查看所有机主服务绩效'] = false;
$config['summary']['查看所有课题组使用效益'] = false;

// 数据上报
$config['summary']['[大数据体系]管理申报任务'] = false;
$config['summary']['[大数据体系]填报仪器数据'] = false;
$config['summary']['[大数据体系]审核仪器数据'] = false;
$config['summary']['[大数据体系]辅助机主填报仪器数据'] = false;
// $config['summary']['机主'] = false;
// $config['summary']['辅助填报人'] = false;


$config['summary']['管理实验室信息统计'] = false;
// $config['summary']['科技部对接管理'] = false;
$config['summary']['管理科技厅上报信息'] = false;

// 数据概览
$config['summary']['查看所有仪器统计汇总信息'] = false;

$config['summary']['[大数据体系]查看所有技术服务记录'] = false;
$config['summary']['[大数据体系]查看所有技术服务人员效益'] = false;

$config['summary']['-管理 (机构)'] = false;

// 基础数据
$config['summary']['[大数据体系]查看下属机构仪器的预约情况'] = false;
$config['summary']['[大数据体系]查看下属机构仪器的送样情况'] = false;
$config['summary']['[大数据体系]查看下属机构仪器的使用记录'] = false;
$config['summary']['[大数据体系]查看下属机构仪器的使用收费情况'] = false;
$config['summary']['[大数据体系]查看下属机构仪器的故障情况'] = false;
$config['summary']['[大数据体系]查看下属机构成员的信用情况'] = false;

// 多维分析
$config['summary']['查看下属机构运行效益'] = false;
$config['summary']['查看下属机构仪器的使用汇总'] = false;
$config['summary']['查看下属机构仪器的效益统计'] = false;
$config['summary']['查看下属机构仪器的统计图表'] = false;

// 数据上报
$config['summary']['查看下属机构的仪器信息'] = false;
$config['summary']['管理下属机构实验室的教学项目信息'] = false;
$config['summary']['管理下属机构实验室科研项目信息'] = false;
$config['summary']['管理下属机构的实验室统计信息'] = false;

// 仪器统计
$config['summary']['查看负责平台仪器的效益统计'] = false;
$config['summary']['查看负责平台仪器的统计图表'] = false;

// 数据概览
$config['summary']['查看下属机构统计汇总信息'] = false;

$config['summary']['[大数据体系]查看下属机构技术服务记录'] = false;
$config['summary']['[大数据体系]查看下属机构技术服务人员效益'] = false;

$config['summary']['-管理 (负责)'] = false;

// 基础数据
$config['summary']['[大数据体系]查看负责仪器的预约情况'] = false;
$config['summary']['[大数据体系]查看负责仪器的送样情况'] = false;
$config['summary']['[大数据体系]查看负责仪器的使用记录'] = false;
$config['summary']['[大数据体系]查看负责仪器的使用收费情况'] = false;
$config['summary']['[大数据体系]查看负责仪器的故障情况'] = false;
// 数据上报
$config['summary']['[大数据体系]负责仪器的科技部对接管理'] = false;
$config['summary']['[大数据体系]管理负责仪器科技厅上报信息'] = false;
$config['summary']['[大数据体系]查看负责仪器的仪器信息'] = false;
// 多维分析
$config['summary']['[大数据体系]查看负责仪器的使用汇总'] = false;
$config['summary']['[大数据体系]查看负责仪器的效益统计'] = false;
$config['summary']['[大数据体系]查看负责仪器的统计图表'] = false;
$config['summary']['[大数据体系]查看自己的服务绩效'] = false;

$config['summary']['[大数据体系]查看负责技术服务记录'] = false;
$config['summary']['[大数据体系]查看负责技术服务人员效益'] = false;
$config['summary']['[大数据体系]查看本人技术服务效益'] = false;

$config['summary']['-管理 (课题组)'] = false;
$config['summary']['[大数据体系]查看负责课题组的效益统计'] = false;
$config['summary']['[大数据体系]查看负责课题组的统计图表'] = false;
$config['summary']['[大数据体系]查看负责课题组使用效益'] = false;



$config['summary']['#name'] = '大数据体系';
$config['summary']['#icon'] = '!summary/icons/32/summary.png';

$config['is_lab_owner'][] = '查看课题组的的临时增加';
$config['is_equipment_charge'][] = '查看负责仪器的临时增加';


// 大数据体系
$config['default_roles']['课题组负责人']['default_perms'][] = '[大数据体系]查看负责课题组的效益统计';
$config['default_roles']['课题组负责人']['default_perms'][] = '[大数据体系]查看负责课题组的统计图表';
$config['default_roles']['课题组负责人']['default_perms'][] = '[大数据体系]查看负责课题组使用效益';

$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的预约情况';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的送样情况';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的使用记录';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的使用收费情况';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的故障情况';

$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]负责仪器的科技部对接管理';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]管理负责仪器科技厅上报信息';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的仪器信息';

$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的使用汇总';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的效益统计';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看负责仪器的统计图表';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看自己的服务绩效';
$config['default_roles']['仪器负责人']['default_perms'][] = '[大数据体系]查看本人技术服务效益';

$config['default_roles']['科技部申报任务相关人员']['default_perms'][] = '[大数据体系]管理申报任务';
$config['default_roles']['科技部申报任务相关人员']['default_perms'][] = '[大数据体系]填报仪器数据';
$config['default_roles']['科技部申报任务相关人员']['default_perms'][] = '[大数据体系]审核仪器数据';

$config['default_roles']['数据上报-辅助填报人']['default_perms'][] = '[大数据体系]辅助机主填报仪器数据';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['summary'] = [];
    $config['summary']['#name'] = '大数据体系';
    $config['summary']['#perm_in_uno'] = TRUE;

    $config['summary']['-基础数据'] = FALSE;
    $config['summary']['查看仪器的预约情况'] = false;
    $config['summary']['查看仪器的送样情况'] = false;
    $config['summary']['查看仪器的使用情况'] = false;
    $config['summary']['查看仪器的使用收费情况'] = false;
    $config['summary']['查看仪器的故障情况'] = false;
    $config['summary']['查看成员的信用情况'] = false;
    $config['summary']['查看负责仪器的预约情况'] = false;
    $config['summary']['查看负责仪器的送样情况'] = false;
    $config['summary']['查看负责仪器的使用情况'] = false;
    $config['summary']['查看负责仪器的使用收费情况'] = false;
    $config['summary']['查看负责仪器的故障情况'] = false;

    $config['summary']['-多维分析'] = FALSE;
    $config['summary']['查看运行效益'] = false;
    $config['summary']['查看仪器的使用汇总'] = false;
    $config['summary']['查看仪器的效益统计'] = false;
    $config['summary']['查看下属机构仪器的效益统计'] = false;
    $config['summary']['查看下属机构仪器的统计图表'] = false;
    $config['summary']['查看下属机构仪器的使用汇总'] = false;
    $config['summary']['查看下属机构运行效益'] = false;
    $config['summary']['查看仪器的统计图表'] = false;
    $config['summary']['查看机主服务绩效'] = false;
    $config['summary']['查看使用效益'] = false;

   


    $config['summary']['查看负责仪器的使用汇总'] = false;
    $config['summary']['查看自己的服务绩效'] = false;

    $config['summary']['-数据上报'] = FALSE;
    $config['summary']['管理申报任务'] = false;
    $config['summary']['填报仪器数据'] = false;
    $config['summary']['审核仪器数据'] = false;
    $config['summary']['辅助机主填报仪器数据'] = false;
    $config['summary']['管理实验室信息统计'] = false;
    $config['summary']['管理科技厅上报信息'] = false;
    $config['summary']['查看仪器信息'] = false;
    $config['summary']['管理教学项目信息'] = false;
    $config['summary']['管理科研项目信息'] = false;
    $config['summary']['管理统计信息'] = false;

    $config['summary']['管理负责仪器的科技部对接'] = false;
    $config['summary']['管理负责仪器科技厅上报信息'] = false;
    $config['summary']['查看负责仪器的仪器信息'] = false;

    $config['summary']['-数据概览'] = FALSE;
    $config['summary']['查看仪器统计汇总信息'] = false;

    $config['summary']['-仪器统计'] = FALSE;
    $config['summary']['查看仪器的效益统计'] = false;
    $config['summary']['查看仪器的统计图表'] = false;

    $config['summary']['查看负责仪器的效益统计'] = false;
    $config['summary']['查看负责仪器的统计图表'] = false;
}
