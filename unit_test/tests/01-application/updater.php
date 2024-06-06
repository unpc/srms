<?php
/*
 * @file updater.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试updater available_update 函数功能是否正常 
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/updater

函数available_update（）；
	1.测试数据(存在此配置信息，并且版本号相同)
		输入： 'LIMSLogon','win','2.0.0.6';
		输出： NULL；
	2.测试数据(存在此配置信息，但版本信息不同)
		输入： 'LIMSLogon','win','1.0.0.1';
		输出：(object) array(
				'version' => $release['version'],
				'update_uri'=>$release['update_uri'],
				'public_key_token'=>$release['public_key_token'],
			  ); 
    3.测试数据(不存在此配置信息)
    	输入：'other_name','linux','1.0.0.1'
    	输出：NULL；
*/





//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert('class_exists(updater)',class_exists('updater'));
Unit_Test::assert('method_exists(updater::available_update)',method_exists('updater','available_update'));
Unit_Test::echo_endl();

class Updater_Test{
	static function set_config($name) {
		$arr=[
					'win' => [
						'version' => '2.0.0.6',
						'update_uri' => '',
						'public_key_token' => '',
					],
					'osx_leopard' => [
						'version' => '1.0',
					],
					'ox_tiger' => [
						'version' => '1.0', 
					],
				];
		Config::set('updater.'.$name, $arr);
	}
	
	static function result() {
		return (object)[
				'version' => '2.0.0.6',
				'update_uri' => '',
				//'uninstall_uri'=>NULL,  //由于config的配置不同，注销如下返回信息
				'public_key_token' => '', 
				//'autorun_uri'=>NULL 
			];
	} 
	
	static function test_available_update($info,$name, $os, $version,$expect) {
		Updater_Test::set_config($name);
		$output = Updater::available_update($name, $os, $version);
        Unit_Test::assert($info, $output == $expect, $output);
		
	}
}

// 测试
Unit_Test::echo_title('Updater::available_update测试:');
    Updater_Test::test_available_update('1','LIMSLogon','win','2.0.0.6',NULL);
    Updater_Test::test_available_update('2','LIMSLogon','win','1.0.0.1',Updater_Test::result());
    Updater_Test::test_available_update('3','other_name','linux','1.0.0.1',NULL);
Unit_Test::echo_endl();
