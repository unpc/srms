<?php

$config['fund_report']['#name'] = '基金申报';
$config['fund_report']['#icon'] = '!fund_report/icons/32/fund_report.png';

$config['fund_report']['管理申报年度'] = FALSE;
$config['fund_report']['填报所有基金申请'] = FALSE;
$config['fund_report']['填报负责仪器的基金申请'] = FALSE;
$config['fund_report']['查看基金申请单'] = FALSE;
$config['fund_report']['初审基金申请单'] = FALSE;
$config['fund_report']['复审基金申请单'] = FALSE;
$config['fund_report']['录入审批资助基金'] = FALSE;
$config['fund_report']['查看基金资助情况'] = FALSE;
$config['fund_report']['填写基金实施情况'] = FALSE;
$config['fund_report']['查看基金实施情况'] = FALSE;
$config['fund_report']['查看下属机构的基金申报'] = FALSE;
$config['fund_report']['查看负责仪器的基金申报'] = FALSE;

$config['is_equipment_charge'][] = '查看负责仪器的基金申报';

$config['default_roles']['仪器负责人']['default_perms'][] = '查看负责仪器的基金申报';
$config['default_roles']['仪器负责人']['default_perms'][] = '填报负责仪器的基金申请';
$config['default_roles']['仪器负责人']['default_perms'][] = '填写基金实施情况';



if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['fund_report'] = [];
	$config['fund_report']['#name'] = '基金申报';
	$config['fund_report']['#perm_in_uno'] = TRUE;

	$config['fund_report']['-管理'] = FALSE;
	$config['fund_report']['管理申报年度'] = FALSE;
    $config['fund_report']['填报基金申请'] = FALSE;
	$config['fund_report']['查看基金申请单'] = FALSE;
	$config['fund_report']['初审基金申请单'] = FALSE;
    $config['fund_report']['复审基金申请单'] = FALSE;
    $config['fund_report']['录入审批资助基金'] = FALSE;
    $config['fund_report']['查看基金资助情况'] = FALSE;
    $config['fund_report']['填写基金实施情况'] = FALSE;
    $config['fund_report']['查看基金实施情况'] = FALSE;
    $config['fund_report']['查看基金申报'] = FALSE;

    $config['fund_report']['-负责'] = FALSE;
    $config['fund_report']['查看负责仪器的基金申报'] = FALSE;
    $config['fund_report']['填报负责仪器的基金申请'] = FALSE;
}

