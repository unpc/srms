<?php
/*
 * @file browser.php
 * @author Jia Huang<jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 通过$_SERVER['HTTP_USER_AGENT'] 分析判断获取的浏览器相关信息是否正确
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/browser

 前提条件：为$_SERVER['HTTP_USER_AGENT']赋值为
               'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8'

 函数:name()
 1.测试数据
 	输入：无;
 	输出：'firefox';
 
 函数:version()
 1.测试数据
 	输入：无
 	输出：'3.6'
 
 函数:revision()
 1.测试数据
 	输入：无;
 	输出：8;
 
 函数:supported()
 1.测试数据
 	输入：无
 	输出：TRUE; 	
 	
 	前提条件：为$_SERVER['HTTP_USER_AGENT']赋值为
              'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)'

 函数:name()
 1.测试数据
 	输入：无;
 	输出：'ie';
 
函数:version()
 1.测试数据
 	输入：无
 	输出：'8.0'
 
 函数:revision()
 1.测试数据
 	输入：无;
 	输出：NULL;
 
 函数:supported()
 1.测试数据
 	输入：无
 	输出：FALSE;
 	
前提条件：为$_SERVER['HTTP_USER_AGENT']赋值为
              'Opera/9.70 (Linux ppc64 ; U; en) Presto/2.2.1'
函数:name()
 1.测试数据
 	输入：无;
 	输出：'opera';
 
函数:version()
 1.测试数据
 	输入：无
 	输出：'9.70'
 
函数:revision()
 1.测试数据
 	输入：无;
 	输出：NULL;
 
函数:supported()
 1.测试数据
 	输入：无
 	输出：FALSE;
*/
//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(browser)", class_exists('browser'));
Unit_Test::assert("method_exists(browser::name)", method_exists('browser', 'name'));
Unit_Test::assert("method_exists(browser::version)", method_exists('browser', 'version'));
Unit_Test::assert("method_exists(browser::revision)", method_exists('browser', 'revision'));
Unit_Test::assert("method_exists(browser::supported)", method_exists('browser', 'supported'));
Unit_Test::echo_endl();

class Browser_Test{

	private static $var;
	
	static function init($arr) {
		$ua = array_shift($arr);
		$_SERVER['HTTP_USER_AGENT'] = $ua;
		Unit_Test::echo_title($ua);
		Browser::reset();
		self::$var = $arr;
	}
	
	static function test_name($expect){
		$output=Browser::name();
		$info = 'Browser::name';
		Unit_Test::assert($info,$expect==$output,$output);
		Unit_Test::echo_endl();
	}
	
	static function test_version($expect){
		$output=Browser::version();
		$info = 'Browser::version';
		Unit_Test::assert($info,$expect==$output,$output);
		Unit_Test::echo_endl();
	}
	
	static function test_revision($expect){
		$output=Browser::revision();
		$info = 'Browser::revision';
		Unit_Test::assert($info,$expect==$output,$output);
		Unit_Test::echo_endl();
	}
	
	static function test_supported($expect){
		$output=Browser::supported();
		$info = 'Browser::supported';
		Unit_Test::assert($info,$expect==$output,$output);
		Unit_Test::echo_endl();
	}
	
	static function test() {
		foreach (self::$var as $k=>$v) {
			Browser_Test::$k($v);
		}
	}
}
 
$ua = [ 
	[
		'浏览器信息：Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.8) Gecko/20100723 Ubuntu/10.04 (lucid) Firefox/3.6.8',
		'test_name'=>'firefox',
		'test_version'=>'3.6',
		'test_revision'=>8,
		'test_supported'=>TRUE
	],
	[
		'浏览器信息：Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
		'test_name'=>'ie',
		'test_version'=>'8.0',
		'test_revision'=>NULL,
		'test_supported'=>FALSE
	],
	[
		'浏览器信息：Opera/9.70 (Linux ppc64 ; U; en) Presto/2.2.1',
		'test_name'=>'opera',
		'test_version'=>'9.70',
		'test_revision'=>NULL,
		'test_supported'=>TRUE
	],
	[
		'浏览器信息：Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13 ',
		'test_name'=>'chrome',
		'test_version'=>'0.2',
		'test_revision'=>149.27,
		'test_supported'=>FALSE
	],
		[
		'浏览器信息：Mozilla/5.0 (Windows; U; Windows NT 6.1; ja-JP) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16',
		'test_name'=>'safari',
		'test_version'=>'5.0',
		'test_revision'=>NULL,
		'test_supported'=>TRUE
	]
	
];

foreach ($ua as $tmp) {
	Browser_Test::init($tmp);
	Browser_Test::test();
}

