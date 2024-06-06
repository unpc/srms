<?php
/*
 * @file session.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试session基类的setup cleanup_other_urls set_url_specific get_url_specific 函数功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/session

函数：setup()
	1.测试数据
		输入:无
		输出:无（成员变量：$url_key='URL:d41d8cd98f00b204e9800998ecf8427e'）
		
函数：cleanup_other_urls()
	1.测试数据：(条件：$_SESSION不存在)
		输入：无；
		输出：无，(成员变量$cleaned_others = TRUE)；
	2.测试数据：(条件：$_SESSION存在，但$_SESSION没有以'URL:'开头键)
		输入：无；
		输出：无，(成员变量$cleaned_others = TRUE)；
	3.测试数据：(条件：$_SESSION存在，且键以'URL:'开头，但键不等于成员变量$url_key)
		输入：无；
		输出：无，(成员变量$cleaned_others = TRUE)；
	4.测试数据：(条件：$_SESSION存在,且键以'URL:'开头，并且键等于成员变量$url_key)
		输入：无；
		输出：无，(从$_SESSION中消除该项,成员变量$cleaned_others = TRUE)；
		
函数：set_url_specific()
	1.测试数据
		输入：'键1',NULL;
		输出：无，(销毁$_SESSION[$url_key]['键1'])
	2.测试数据
		输入：'键2','值2';
		输出：无，($_SESSION[$url_key]['键2']='值2')
	
						
函数：get_url_specific()
	1.测试数据
		输入：'键1',NULL；
		输出：NULL;
	2.测试数据（前提是$_SESSION存在）
		输入：'键2','value2'；
		输出：'值2'；
		
注：函数set_url_specific()和函数get_url_specific()一起进行单元测试；

*/
//创建测试环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(session)",class_exists('session'));
Unit_Test::assert("method_exists(session ::setup)",method_exists('session','setup'));
Unit_Test::assert("method_exists(session ::cleanup_other_urls)",method_exists('session','cleanup_other_urls'));
Unit_Test::assert("method_exists(session ::get_url_specific)",method_exists('session','get_url_specific'));
Unit_Test::assert("method_exists(session ::set_url_specific)",method_exists('session','set_url_specific'));

Unit_Test::echo_endl();

class Session_Test{
	static function test_setup($info, $expect) {
		Session::setup();
		$output=Session::$url_key;
		Unit_Test::assert($info,$output==$expect,$output);
	}
	
	static function test_cleanup_other_urls($info, $expect) {
		
		Session::cleanup_other_urls();
		$output=Session::$cleaned_others;
		Unit_Test::assert($info,$output==$expect,$output);
	}
	
	static function test_set_get_url_specific($info,$name,$default,$value,$expect) {
	    Session::setup();
	    Session::set_url_specific($name,$value);
		$output=Session::get_url_specific($name, $default);
		Unit_Test::assert($info,$output==$expect,$output);
	}
}

//测试
Unit_Test::echo_title('Session::setup测试:');
	Session_Test::test_setup('1','URL:d41d8cd98f00b204e9800998ecf8427e');
Unit_Test::echo_endl();

Unit_Test::echo_title('Session::cleanup_other_urls测试');
	Session_Test::test_cleanup_other_urls('1',TRUE);
	
	session_start();
	Session_Test::test_cleanup_other_urls('2', TRUE);
	
	session_register('URL::aabb');
	$_SESSION['URL::aabb']='测试数据';
	Session_Test::test_cleanup_other_urls('3', TRUE);
    unset($_SESSION['URL::aabb']);
    
    session_register(Session::$url_key);
	$_SESSION[Session::$url_key]='测试数据';
	Session_Test::test_cleanup_other_urls('4', TRUE);
Unit_Test::echo_endl();

Unit_Test::echo_title('Session::set_url_specific,Session::get_url_specific测试:');
	session_unset();
	Session_Test::test_set_get_url_specific('1','键1',NULL,NULL,NULL);
	Session_Test::test_set_get_url_specific('2','键2','value2','值2','值2');
	Session_Test::test_set_get_url_specific('3','键3',NULL,'值3','值3');
	Session_Test::test_set_get_url_specific('4','键4','值4',NULL,'值4');
Unit_Test::echo_endl();
