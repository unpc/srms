<?php 
/*
 * @file daemon_test.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试Daemon服务基类中获取pid文件功能是否正常
 * @useage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/daemon_test

函数：get_pid_file();
	1.测试数据
		输入：'test';
		输出：'/tmp/test.daemon_test.pid';
	2.测试数据
		输入：12；
		输出：'/tmp/test.daemon_12.pid';
	3.测试数据
		输入：NULL;
		输出：'/tmp/test.daemon_.pid';
*/


//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert('class_exists(daemon)', class_exists('daemon'));
Unit_Test::assert('method_exists(daemon::get_pid_file)', method_exists('daemon', 'get_pid_file'));
Unit_Test::echo_endl();

class Daemon_Test{
	static function test_get_pid_file($info,$name,$expect){
		$output = Daemon::get_pid_file($name);
		Unit_Test::assert($info,$expect==$output,$output);
	}
}

Unit_Test::echo_title('test_get_pid_file测试:');
	Daemon_Test::test_get_pid_file('1','aa','/tmp/daemon_aa.pid');
	Daemon_Test::test_get_pid_file('2','12','/tmp/daemon_12.pid');
	Daemon_Test::test_get_pid_file('3',NULL,'/tmp/daemon_.pid');
//get_pid_file中已经没有了test的增加
Unit_Test::echo_endl();
