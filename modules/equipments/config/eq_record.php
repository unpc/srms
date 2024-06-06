<?php

//不自动关联项目
if ($GLOBALS['preload']['people.multi_lab']) {
	$config['must_connect_lab_project'] = TRUE;
}
else {
	$config['must_connect_lab_project'] = FALSE;
}

//glogon退出登录是否samples必填
$config['glogon_require_samples'] = FALSE;

//默认record样品数
$config['record_default_samples'] = 1;

//手动添加record记录显示的默认样品数
$config['record_manual_default_samples'] = 1;

$config['feedback_need_samples'] = FALSE;
