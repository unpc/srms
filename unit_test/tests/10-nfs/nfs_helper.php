<?php

/*
* @file nfs_helper.php
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
*
* @brief 测试基类nfs的fix_path get_path功能是否正常
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/nfs_helper
*/

//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Environment::init_site();
Unit_Test::assert('class_exists(NFS)', class_exists('NFS'));
Unit_Test::assert('method_exists(NFS::fix_path)', method_exists('NFS', 'fix_path'));
Unit_Test::assert('method_exists(NFS::get_path)', method_exists('NFS', 'get_path'));

NFS::setup();

$has_share_module = Module::is_installed('nfs_share');

Unit_Test::echo_endl();

class NFS_UT {
	static function test_fix_path($str, $path, $forecast_path) {
		$echo_path = NFS::fix_path($path);
		Unit_Test::assert($str, $forecast_path == $echo_path, $echo_path);
	}

	static function test_get_path($str, $object, $path, $type, $use, $forecast_path) {
		$full_path = NFS::get_path($object, $path, $type, $use);
		Unit_Test::assert($str, $forecast_path == $full_path, $full_path);
	}
}


Unit_Test::echo_title('NFS::fix_path测试');
	NFS_UT::test_fix_path('1', '../test.txt', 'test.txt');
	NFS_UT::test_fix_path('2', './test.txt', 'test.txt');
	NFS_UT::test_fix_path('3', '/ ../test.txt', 'test.txt');
	NFS_UT::test_fix_path('4', '/asdh ../test.txt', 'asdh ../test.txt');
	NFS_UT::test_fix_path('5', '//^|+-*../test.txt', '^|+-*../test.txt');
	NFS_UT::test_fix_path('6', 'abc/../test.txt', 'abc/test.txt');
	NFS_UT::test_fix_path('7', 'abc/./test.txt', 'abc/test.txt');
Unit_Test::echo_endl();

Unit_Test::echo_title('NFS::get_path测试');
    Config::set('nfs.root', '/home/disk/cf/ut/');
	$user = Environment::prepare_user('cheng.liu');
    if ($has_share_module) {
        NFS_UT::test_get_path('1',$user,'public/test.txt','share',TRUE, '/home/disk/cf/ut/share/users/'. $user->id. '/public/test.txt');
    }
    else {
        NFS_UT::test_get_path('1',$user,'public/test.txt','share',TRUE, '/home/disk/cf/ut/public/test.txt');
    }
	NFS_UT::test_get_path('2',$user,'public/test.txt','share',FALSE,'public/test.txt');
	NFS_UT::test_get_path('3',$user,'public/test.txt','attachments',TRUE,'/home/disk/cf/ut/attachments/user/'.$user->id.'/public/test.txt');
	NFS_UT::test_get_path('4',$user,'public/test.txt','attachments',FALSE,'public/test.txt');
	
	$equipment = Environment::prepare_equipment('A');
    if ($has_share_module) {
        NFS_UT::test_get_path('5',$equipment,'public/test.txt','share',TRUE,'/home/disk/cf/ut/share/public/public/test.txt');
    }
    else {
        NFS_UT::test_get_path('5',$equipment,'public/test.txt','share',TRUE,'/home/disk/cf/ut/public/test.txt');
    }
	NFS_UT::test_get_path('6',$equipment,'public/test.txt','share',FALSE,'public/test.txt');
	NFS_UT::test_get_path('7',$equipment,'public/test.txt','attachments',TRUE,'/home/disk/cf/ut/attachments/equipment/'.$equipment->id.'/public/test.txt');
	NFS_UT::test_get_path('8',$equipment,'public/test.txt','attachments',FALSE,'public/test.txt');
Unit_Test::echo_endl();

Unit_Test::echo_title('撤销环境');
	Environment::destroy();
Unit_Test::echo_endl();


if (!file_exists($full_path)) {
	File::check_path($full_path);
	@mkdir($full_path, 0755);
}
else {
	deleteDir($full_path);
	File::check_path($full_path);
	@mkdir($full_path, 0755);
}

$new = fopen($full_path.$file_path, 'w');
$content = 'This is a new file! A new test file!';
fwrite($new, $content);
fclose($new);

$list = NFS::file_list($full_path);

//测试函数
foreach($list as $file) {
	echo '文件名 : '.$file['name']."\n";
	echo '文件大小 : '.$file['size']."B\n";
	echo '是否是文件 : '.($file['file'] ? 'yes' : 'no')."\n";
	echo '是否是目录 : '.($file['dir'] ? 'yes' : 'no') ."\n";
	echo '是否是符号连接 : '.($file['link'] ? 'yes' : 'no') ."\n";
	echo '文件相对路径 : '.$file['path']."\n";
	
}
//销毁环境

deleteDir($full_path);
function deleteDir($dir){
	if (is_dir($dir)) {
		$dp = opendir($dir);
		if (!$dp) {
			exit(253);
		}
		while (FALSE!==($file=readdir($dp))) {
			if ($file[0] == '.') continue;
		
			if (is_dir($dir.$file)) {
				deleteDir($dir.$file);
			}
			else {
				unlink($dir.$file);
			}
		}
		closedir($dp);
		rmdir($dir);
	}
}
