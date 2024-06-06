<?php

define('ROLE_CURRENT_MEMBERS', -4);
define('ROLE_PAST_MEMBERS', -5);
define('ROLE_VISITORS',-6);

/*
	$config['default_roles'][ROLE_VISITORS] = array('key'=>'visitors', 'name'=>'访客', 'weight'=>ROLE_VISITORS);
*/
$config['default_roles'][ROLE_PAST_MEMBERS] = ['key'=>'past', 'name'=>'过期成员', 'weight'=>ROLE_PAST_MEMBERS];

$config['default_roles'][ROLE_CURRENT_MEMBERS] = ['key'=>'current', 'name'=>'目前成员', 'weight'=>ROLE_CURRENT_MEMBERS];

define('ROLE_STUDENTS', -200);
define('ROLE_TEACHERS', -100);

$config['default_roles'][ROLE_STUDENTS] = ['key'=>'students', 'name'=>'学生', 'weight'=>ROLE_STUDENTS];
$config['default_roles'][ROLE_TEACHERS] = ['key'=>'teachers', 'name'=>'教师', 'weight'=>ROLE_TEACHERS];


define('ROLE_LAB_PI', -90);
define('ROLE_EQUIPMENT_CHARGE', -80);
$config['default_roles'][ROLE_LAB_PI] = ['key'=>'lab_pi', 'name'=>'课题组负责人', 'weight'=>ROLE_LAB_PI];
$config['default_roles'][ROLE_EQUIPMENT_CHARGE] = ['key'=>'equipment_charge', 'name'=>'仪器负责人', 'weight'=>ROLE_EQUIPMENT_CHARGE];


