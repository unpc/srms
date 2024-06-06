<?php
$config['achievements']['查看所有实验室成果'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['achievements']['查看负责实验室成果'] = FALSE;
    $config['achievements']['查看所属实验室成果'] = FALSE;
    $config['achievements']['查看下属机构仪器的关联成果'] = FALSE;
} 
else {
    $config['achievements']['查看本实验室成果'] = FALSE;
}

$config['achievements']['添加/修改所有实验室成果'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['achievements']['添加/修改负责实验室成果'] = FALSE;
    /* (@2018.02.27)
     * BUG #14244::成果管理-经过产品经理确认，麻烦把权限“添加/修改所属课题组成果”隐藏掉 
     */
    // $config['achievements']['添加/修改所属实验室成果'] = FALSE;
    /* end */
}
else {
    $config['achievements']['添加/修改本实验室成果'] = FALSE;
}

$config['achievements']['#name'] = '成果管理';
$config['achievements']['#icon'] = '!achievements/icons/32/achievements.png';


$config['default_roles']['课题组负责人']['default_perms'][] = "添加/修改本实验室成果";
$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室成果";

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['achievements'] = [];
    $config['achievements']['#name'] = '成果管理';
    $config['achievements']['#perm_in_uno'] = TRUE;

    $config['achievements']['-管理'] = FALSE;
    $config['achievements']['查看成果'] = FALSE;
    $config['achievements']['添加/修改成果'] = FALSE;
}