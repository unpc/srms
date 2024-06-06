<?php
/*
 * @file notification.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 测试notification中内容中symbol转换为markup 以及根据不同类型进行notification的发送是否功能正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/notification

函数：symbol_to_markup()
	1.测试数据
		输入：array(a=>'a',b=>'b',c=>'c')，array(a=>'A',b=>'B',z='z')；
		输出：array(a=>'A',b=>'B',c=>'c');
	2.测试数据
		输入：array(a=>'a',b=>'b',c=>'c'),'d'；
		输出：array(a=>'a',b=>'b',c=>'c');
	3.测试数据（）
		输入：'d',array(a=>'a',b=>'b',c=>'c')；
		输出：'d';
		
函数：get_send_types()
	1.测试数据
		输入：'测试01'；
		输出：FALSE;
	2.测试数据(注：输出数组的第2个元素）
		输入：array("元素01","元素02")；
		输出：元素02;
*/


//创建测试环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert('class_exists(notification)',class_exists('notification'));
Unit_Test::assert('method_exists(notification::symbol_to_markup)',method_exists('notification','symbol_to_markup'));
Unit_Test::echo_endl();

class Notification_Test{
	static function test_symbol_to_markup($info,$arr_str, $params,$expect){
		$output = Notification::symbol_to_markup($arr_str,$params);
		Unit_Test::assert($info,$output==$expect,$output);
	}
}

// 测试
Unit_Test::echo_title('Notification::symbol_to_markup测试：');
	$arr_str=[a=>'a',b=>'b',c=>'c'];
	$params=[a=>'A',b=>'B',z=>'z'];
	$expect=[a=>'A',b=>'B',c=>'c'];
	Notification_Test::test_symbol_to_markup('1',$arr_str, $params,$expect);
	Notification_Test::test_symbol_to_markup('2',[a=>'a',b=>'b',c=>'c'],'d',[a=>'a',b=>'b',c=>'c']);
	Notification_Test::test_symbol_to_markup('3','d',[a=>'a',b=>'b',c=>'c'],'d');
Unit_Test::echo_endl();

//Notification get_send_types已经被删除，无需进行测试
