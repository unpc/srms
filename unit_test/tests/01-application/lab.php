<?php
/*
 * @file lab.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试Lab基类是否可正常进行不同类型的Lab_MESSAGE的显示
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/lab

函数：message()
	1.测试数据
		输入：MESSAGE_NORMAL，'测试01'；
		输出：无(成员变量赋值：Lab::$messages[MESSAGE_NORMAL][] ='测试01')；
	2.测试数据
		输入：MESSAGE_WARNING，'测试02'；
		输出：无(成员变量赋值：Lab::$messages[MESSAGE_WARNING][] ='测试02')；
	3.测试数据
		输入：MESSAGE_ERROR，'测试03'；
		输出：无(成员变量赋值：Lab::$messages[MESSAGE_ERROR][] ='测试03')；
	4.测试数据
		输入：MESSAGE_ERROR，array('测试a','测试b');
		输出：无(成员变量赋值：Lab::$messages[MESSAGE_ERROR][] =array('测试a','测试b'))；
		
函数：messages()
	1.测试数据
		输入：MESSAGE_NORMAL;
		输出：Lab::$messages[MESSAGE_NORMAL];
	2.测试数据
		输入：MESSAGE_WARNING;
		输出：Lab::$messages[MESSAGE_NORMAL];
	3.测试数据
		输入：MESSAGE_ERROR;
		输出：Lab::$messages[MESSAGE_NORMAL];
注：测试时两个方法应一起测试，即先赋值，再取值；	
*/
//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(lab)",class_exists('lab'));
Unit_Test::assert("method_exists(lab::message)",method_exists('lab','message'));
Unit_Test::assert("method_exists(lab::messages)",method_exists('lab','messages'));
Unit_Test::echo_endl();

class Lab_Test{
	//填充并获得某类信息
	static function set_messages($type,$text) {
		Lab::message($type,$text);
		return Lab::messages($type);
	}
	static function test_message_messages($info,$type,$expect) {
	    $output=Lab::messages($type);
		Unit_Test::assert($info,$output == $expect, $output);
	}
}


//测试
Unit_Test::echo_title('Lab::messages测试:');
	Lab_Test::test_message_messages('1',MESSAGE_NORMAL,Lab_Test::set_messages(MESSAGE_NORMAL, '测试01'));
	Lab_Test::test_message_messages('2',MESSAGE_WARNING,Lab_Test::set_messages(MESSAGE_WARNING, '测试02'));
	Lab_Test::test_message_messages('3',MESSAGE_ERROR,Lab_Test::set_messages(MESSAGE_ERROR, '测试03'));
	Lab_Test::test_message_messages('4',MESSAGE_ERROR,Lab_Test::set_messages(MESSAGE_ERROR, ['测试a','测试b']));
Unit_Test::echo_endl();


