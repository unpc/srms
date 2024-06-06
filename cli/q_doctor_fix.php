#!/usr/bin/env php
<?php

require 'base.php';

//数据库检查
putenv('Q_ROOT_PATH='.ROOT_PATH);
putenv('SITE_ID='.SITE_ID);
putenv('LAB_ID='.LAB_ID);

$content = '';

$cmd = 'php ' . ROOT_PATH . 'cli/create_orm_tables.php 2>&1';
$ph = popen($cmd, 'r');

if ($ph) {
    while (FALSE !== ($output = fgets($ph, 2096))) $content .= "\033[31m" . $output . "\033[0m";
    pclose($ph);
    $content .= "\033[32m执行 create_orm_tables 脚本 \033[0m";
}
else {
    $content .= "\033[31m缺少 create_orm_tables 脚本 \033[0m";
}

echo $content."\n";

//模块检查
$modules = Config::get('lab.modules');

foreach ($modules as $module => $is_open) {
    if ($is_open && !file_exists(ROOT_PATH . 'modules/' . $module)) {
        echo "\033[31m" . $module . '模块目录不存在' . "\n\033[0m";   
    }
}

//服务检查 gordon /home/jipeng.huang/cli/service_observer.php
//会有一个单独的cron挂载在宿主机外面来检测环境的服务是否正常

//socket 目录权限 [nfs]
$current_dirs = [
    '/tmp/genee-nodejs-ipc/',
    '/tmp/lims2/'.SITE_ID.'/'
];

foreach ($current_dirs as $current_dir) {
    $files = scandir($current_dir, 1);
    array_pop($files);
    array_pop($files);
    foreach ($files as $file) {
        $path = $current_dir.$file;
        if (!is_writable($path)) {
            echo "\033[31m" . $path . '没有写权限, 进行修改' . "\n\033[0m";
            exec('chmod 777 ' . $path);
            if (is_writable($path)) {
                echo "\033[32m" . $path . '已具有写权限' . "\n\033[0m";
            }
        }

        if (!is_readable($path)) {
            echo "\033[31m" . $path . '没有读权限' . "\n\033[0m";
            exec('chmod 777 ' . $path);
            if (is_readable($path)) {
                echo "\033[32m" . $path . '已具有读权限' . "\n\033[0m";
            }
        }
    }
}

//白名单检测
$white_list = [
    'eq_mon' => ['eq_mon'], 
    'equipments' => ['glogon', 'cacs'], 
    'eq_meter' => ['epc'], 
    'entrance' => ['entrance'],
    'vidmon' => ['vidmon'], 
    'envmon' => ['envmon', 'tszz'], 
    'eq_reserv' => ['eq_reserv']
];

foreach ($white_list as $module => $lists) {
    if ($modules[$module]) {
        foreach ($lists as $key => $value) {
            $list = Config::get('api.white_list_' . $value, []);
            if (!count($list)) {
                echo "\033[31m" . $value . '白名单未设置'."\n\033[0m"; 
            }
        }
    }
}