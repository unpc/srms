<?php 

/*
 * @file openid_rp.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 基类OpenID_RP函数is_xri normalize_xri normalize_url normalize create_message dh_binary_to_long dh_long_to_binary dh_base64_to_long dh_long_to_base64 get_params 测试
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/neighbors/openid_rp
 */

require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

if (!Module::is_installed('neighbors')) {
    return TRUE;
	//exit(Environment::echo_error("neighbors模块不存在，退出测试\n"));
}

//创建环境
Unit_Test::assert('class_exists(OpenID_RP)', class_exists('OpenID_RP'));
Unit_Test::assert('method_exists(Openid_RP::is_xri)', method_exists('Openid_RP', 'is_xri'));
Unit_Test::assert('method_exists(Openid_RP::normalize_xri)', method_exists('Openid_RP', 'normalize_xri'));
Unit_Test::assert('method_exists(Openid_RP::normalize_url)', method_exists('Openid_RP', 'normalize_url'));
Unit_Test::assert('method_exists(Openid_RP::normalize)', method_exists('Openid_RP', 'normalize'));
Unit_Test::assert('method_exists(Openid_RP::create_message)', method_exists('Openid_RP', 'create_message'));
Unit_Test::assert('method_exists(Openid_RP::parse_message)', method_exists('Openid_RP', 'parse_message'));
Unit_Test::assert('method_exists(Openid_RP::dh_binary_to_long)', method_exists('Openid_RP', 'dh_binary_to_long'));
Unit_Test::assert('method_exists(Openid_RP::dh_long_to_binary)', method_exists('Openid_RP', 'dh_long_to_binary'));
Unit_Test::assert('method_exists(Openid_RP::dh_base64_to_long)', method_exists('Openid_RP', 'dh_base64_to_long'));
Unit_Test::assert('method_exists(Openid_RP::dh_long_to_base64)', method_exists('Openid_RP', 'dh_long_to_base64'));
Unit_Test::assert('method_exists(Openid_RP::get_params)', method_exists('Openid_RP', 'get_params'));


Unit_Test::echo_endl();

class Test_OpenID_RP {
	static function test_is_xri($str, $string, $v) {
		$out = OpenID_RP::is_xri($string);
		Unit_Test::assert($str, $out == $v, $out);
	}
	
	static function test_normalize_xri($str, $xri, $v) {
		$out = OpenID_RP::normalize_xri($xri);
		Unit_Test::assert($str, $out == $v, $out);
	}
	
	static function test_normalize_url($str, $url, $v) {
		$out = OpenID_RP::normalize_url($url);
		Unit_Test::assert($str, $out == $v, $out);
	}
	
	static function test_normalize($str, $s , $v) {
		$out = OpenID_RP::normalize($s);
		Unit_Test::assert($str, $out == $v, $out);
	}
	
	static function test_create_message($str, $data, $v) {
		$out = OpenID_RP::create_message($data);
		Unit_Test::assert($str, $out == $v, $out);		
	}
	
	static function test_parse_message($str, $mess, $v) {
		$out = OpenID_RP::parse_message($mess);
		Unit_Test::assert($str, $out === $v, $out);
	}
	
	static function test_binary_long($str, $s) {
		$out = OpenID_RP::dh_binary_to_long($s);
		$out = OpenID_RP::dh_long_to_binary($out);
		Unit_Test::assert($str, $out == $s, $out);
	}
	
	static function test_base64_long($str, $s) {
		$out = OpenID_RP::dh_long_to_base64($s);
		$out = OpenID_RP::dh_base64_to_long($out);
		Unit_Test::assert($str, $out == $s, $out);
	}
	
	static function test_get_params($str, $s, $v) {
		$out = OpenID_RP::get_params($s);
		Unit_Test::assert($str, $v == $out, $out);
	}
}

Unit_Test::echo_title('测试OpenID_RP::is_xri');
	Test_OpenID_RP::test_is_xri('1','xri://sdsd',TRUE);
	Test_OpenID_RP::test_is_xri('2','@dsa',TRUE);
	Test_OpenID_RP::test_is_xri('3','=asd',TRUE);
	Test_OpenID_RP::test_is_xri('4','asd://asd',FALSE);
	Test_OpenID_RP::test_is_xri('5','xri:@sdsd',FALSE);
UNit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::normalize_xri');
	Test_OpenID_RP::test_normalize_xri('1','xri://sdsd','sdsd');
	Test_OpenID_RP::test_normalize_xri('2','xri://','');
	Test_OpenID_RP::test_normalize_xri('3','@://','@://');
UNit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::normalize_url');
	Test_OpenID_RP::test_normalize_url('1','@sdsd','http://@sdsd/');
	Test_OpenID_RP::test_normalize_url('2','@sdsd/','http://@sdsd/');
	Test_OpenID_RP::test_normalize_url('3','://@sds/d','://@sds/d');
	Test_OpenID_RP::test_normalize_url('4','http://@sdsd','http://@sdsd/');
UNit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::normalize');
	Test_OpenID_RP::test_normalize('1','xri://sdsd','sdsd');
	Test_OpenID_RP::test_normalize('2','@sdsd','@sdsd');
	Test_OpenID_RP::test_normalize('3','=sdsd','=sdsd');
	Test_OpenID_RP::test_normalize('4','a://a','a://a/');
	Test_OpenID_RP::test_normalize('5','xri:@a','http://xri:@a/');
	Test_OpenID_RP::test_normalize('6','://a/','://a/');
UNit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::create_message');
	$data = ['xri'=>'www.baidu.com'];
	Test_OpenID_RP::test_create_message('1', $data, "xri:www.baidu.com\n");
	$data = ["\n"=>'www.baidu.com'];
	Test_OpenID_RP::test_create_message('2', $data, null);
	$data = [':'=>'www.baidu.com'];
	Test_OpenID_RP::test_create_message('3', $data, null);
	$data = ['xri'=>"\n"];
	Test_OpenID_RP::test_create_message('4', $data, null);
	$data = ['a'=>'b', 'c'=>'d'];
	Test_OpenID_RP::test_create_message('5', $data, "a:b\nc:d\n");
Unit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::parse_message');
	$data = ['a'=>'b', 'c'=>'d'];
	Test_OpenID_RP::test_parse_message('1', "a:b\nc:d\n", $data);
	Test_OpenID_RP::test_parse_message('2', "a:b\nc::d\n", ['a'=>'b','c'=>':d']);
	Test_OpenID_RP::test_parse_message('3', "a:bc::d\n", ['a'=>'bc::d']);
	Test_OpenID_RP::test_parse_message('4', 'a:bc::d', ['a'=>'bc::d']);
	Test_OpenID_RP::test_parse_message('5', 'abcd', []);
Unit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::dh_binary_to_long, OpenID_RP::dh_long_to_binary');
	Test_OpenID_RP::test_binary_long('1','a');
	Test_OpenID_RP::test_binary_long('2','ab');
	Test_OpenID_RP::test_binary_long('3','123:');
	Test_OpenID_RP::test_binary_long('4','ad2/1');
Unit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::dh_base64_to_long, OpenID_RP::dh_long_to_base64');
	Test_OpenID_RP::test_base64_long('1','97');
	Test_OpenID_RP::test_base64_long('2','4564');
	Test_OpenID_RP::test_base64_long('3','123');
	Test_OpenID_RP::test_base64_long('4','784');
Unit_Test::echo_endl();

Unit_Test::echo_title('测试OpenID_RP::get_params');
	Test_OpenID_RP::test_get_params('1', '1=2', ['1'=>'2']);
	Test_OpenID_RP::test_get_params('2', '1=2&3=2', ['1'=>'2','3'=>'2']);
	Test_OpenID_RP::test_get_params('3', '12', []);
	Test_OpenID_RP::test_get_params('4', '12&2=3!=', ['2'=>'3!=']);
UNit_Test::echo_endl();

