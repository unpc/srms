#!/usr/bin/env php
<?php
    /*
     * file glogon_packager.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2015/01/05 17:25
     *
     * useage SITE_ID=cf LAB_ID=may php glogon_packager.php
     * brief 用于对 Glogon 离线文件进行打包, 两个 zip 包
     */

require dirname(__FILE__). '/base.php';

$path = VIEW_BASE. 'glogon/package/';

if (!$path = Core::file_exists($path, 'equipments')) dir("致命错误! 数据不存在!\n");

//用来配置多少需要打包计算的路径
$package_main_dir = ['login', 'logout', 'prompt'];

//多语言配置
$locales = [
    'zh_CN',
    'en_US',
];

foreach($locales as $l) {

    foreach($package_main_dir as $dir) {

        $zip = new ZipArchive;

        // ROOT_PATH/modules/equipments/views/glogon/package/zh_CN/login.zip
        $file = $path . $l . '/'. $dir. '.zip';

        File::delete($file);

        if ($zip->open($file, ZIPARCHIVE::CREATE) === TRUE) {

            File::traverse($path. $l. '/src/'. $dir, function($path, $params) {
                $name = File::relative_path($path, $params['base']);
                $name = iconv('UTF-8', 'GB2312', $name);
                $zip = $params['zip'];

                if (is_file($path)) {
                    $zip->addFile($path, $name);
                }
                else {
                    $zip->addEmptyDir($name);
                }

                return $zip;
            }, [
                'base'=> $path. $l. '/src/'. $dir,
                'zip'=> $zip,
            ]);

            $zip->close();

        }
    }
}
