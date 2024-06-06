<?php
/*
 * @file number.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 测试number类中currency fill degree 三个函数功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/number

函数：currency()
	1.测试数据
		输入：10；
		输出：'¥10.00';
	2.测试数据
		输入：10.12313；
		输出：'¥10.12';
	3.测试数据
		输入：'aa'；
		输出：'¥0.00';
		
函数：fill()
	1.测试数据：
		输入：'aa',6,'0',STR_PAD_LEFT；
		输出：'0000aa'；
	2.测试数据：
		输入：'aa',6,'bbbbbbbbbbbbb',STR_PAD_LEFT；
		输出：'bbbbaa'；
	3.测试数据：
		输入：'aa',6,'bbbbbbbbbbbbb',STR_PAD_RIGHT；
		输出：,'aabbbb'；
		
函数：degree()
	1.测试数据：
		输入：5.2；
		输出："5° 12' 0.00\""；
	2.测试数据：
		输入：5.5；
		输出："5° 30' 0.00\""；
	3.测试数据：
		输入：0；
		输出："0° 0' 0.00\""；
	4.测试数据：
		输入：'aa'；
		输出："0° 0' 0.00\""；
	5.测试数据：
		输入：NULL；
		输出："0° 0' 0.00\""；

*/

// 创建测试环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(number)",class_exists('number'));
Unit_Test::assert("method_exists(number::currency)",method_exists('number','currency'));
Unit_Test::assert("method_exists(number::fill)",method_exists('number','fill'));
Unit_Test::assert("method_exists(number::degree)",method_exists('number','degree'));

Unit_Test::echo_endl();

class Number_Test{
	static function test_currency($info,$num,$expect){
		$output = Number::currency($num);
		Unit_Test::assert($info, $output == $expect,$output);
	}
	
	static function test_fill($info,$num, $length=6, $pad_string='0', $pad_type=STR_PAD_LEFT,$expect){
		$output=Number::fill($num, $length, $pad_string, $pad_type);
		Unit_Test::assert($info,$output==$expect,$output);	
	}
	
	static function test_degree($info,$num,$expect){
		$output=Number::degree($num);
		Unit_Test::assert($info,$output==$expect,$output);	
	}
}

// 测试
Unit_Test::echo_title('Number::currency测试:');
	Number_Test::test_currency('1',10,'¥10.00');
	Number_Test::test_currency('2',10.123,'¥10.12');
	Number_Test::test_currency('3','aa','¥0.00');

Unit_Test::echo_endl();

Unit_Test::echo_title('Number::fill测试:');
	Number_Test::test_fill('1','aa',6,'0',STR_PAD_LEFT,'0000aa');
	Number_Test::test_fill('2','aa',6,'bbbbbbbbbbbbb',STR_PAD_LEFT,'bbbbaa');
	Number_Test::test_fill('3','aa',6,'bbbbbbbbbbbbb',STR_PAD_RIGHT,'aabbbb');
	
Unit_Test::echo_endl();

//有问题
Unit_Test::echo_title('Number::degree测试:');
	Number_Test::test_degree('1',5.2,"5° 12' 0.00\"");
	Number_Test::test_degree('2',5.5,"5° 30' 0.00\"");
	Number_Test::test_degree('3',0,"0° 0' 0.00\"");
	Number_Test::test_degree('4','aa',"0° 0' 0.00\"");
	Number_Test::test_degree('5',NULL,"0° 0' 0.00\"");
Unit_Test::echo_endl();



