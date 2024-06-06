#!/usr/bin/env php
<?php
    /*
     * file glogon_package_calculator.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015/01/05 16:01
     *
     * useage php glogon_package_calculator.php
     * brief 用来进行 Glogon 欲打包目录下所有文件进行 md5 计算
     */

require dirname(__FILE__). '/base.php';

$locales = [
    'zh_CN',
    'en_US',
];

//用来配置打包文件的路径
$path = VIEW_BASE. 'glogon/package/';

if (!$path = Core::file_exists($path, 'equipments')) die("致命错误! 数据不存在!\n");

//用来配置多少下级需要打包计算的路径
$package_main_dir = ['login', 'logout', 'prompt'];

function get_dir_files_content($path) {

    $return = '';

    if (is_dir($path)) {
        foreach(glob($path. '/*') as $_p) {
           $return .= get_dir_files_content($_p);
       }
    }
    else {
        $return .= @file_get_contents($path);
    }

    return $return;
}

$result = [];

foreach($locales as $l) {

    foreach($package_main_dir as $dir) {
        $result[$l][$dir] = md5(get_dir_files_content($path. $l .'/src/'. $dir));
    }
}


$content = <<<EOF
请增加如下配置到 \033[32mequipments\033[0m 模块下的 \033[32mglogon.php \033[0m 中

\$config['offline_md5'] = %config;

EOF;

echo strtr($content, [
    '%config'=> var_export($result, TRUE),
]);
