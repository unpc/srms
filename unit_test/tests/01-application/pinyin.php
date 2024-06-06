<?php
/*
 * @file pinyin.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试Pinyin基类中code函数功能是否正常
 * @SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/pinyin

函数：code()
	1.测试数据
		输入：'张国平',FALSE;
		输出：'zhang guo ping';
	2.测试数据
		输入：'张 国 平',FALSE;
		输出：'zhang  guo  ping';
	3.测试数据
		输入：' 张国平 ',FALSE;
		输出：'zhang guo ping';
	4.测试数据
		输入：'张国平',TRUE;
		输出：'zgp';
	5.测试数据
		输入：'abc',TRUE;
		输出：'abc';
	6.测试数据
		输入："",TRUE/FALSE;
		输出："";
*/
//创建测试环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(pinyin)",class_exists('pinyin'));
Unit_Test::assert("method_exists(pinyin:code)",method_exists('pinyin','code'));


Unit_Test::echo_endl();

class PinYin_Test{
	static function test_code($info,$str, $first_only,$expect){
		$output = PinYin::code($str, $first_only);
		Unit_Test::assert($info, $output == $expect, $output);
	}
}

//测试
Unit_Test::echo_title('PinYin::code测试:');
	PinYin_Test::test_code('1','张国平',FALSE,'zhang guo ping');
	PinYin_Test::test_code('2','张 国 平',FALSE,'zhang  guo  ping');
	PinYin_Test::test_code('3',' 张国平 ',FALSE,'zhang guo ping');
	PinYin_Test::test_code('4','张国平',TRUE,'zgp');
	PinYin_Test::test_code('5','abc',TRUE,'abc');
	PinYin_Test::test_code('6','',TRUE,'');
Unit_Test::echo_endl();



