<?php
/*
 * @file uri.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 测试基类uri的函数url功能是否正常
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/uri

函数：url();
	1.测试数据
		输入：NULL,NULL,NUll；
		输出：'http://./test.php/~test/'；
	2.测试数据
		输入：'aa','bb=1','cc'；
		输出：'http://./test.php/~test/aa?bb=1#cc'
	3.测试数据
		输入：'http://aa','bb=1','cc'；
		输出：'http://aa/?bb=1#cc'；
	
*/
//创建环境
	//Database::reset();
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Environment::init_site();
Unit_Test::assert('class_exists(uri)',class_exists('uri'));
Unit_Test::assert('method_exists(uri::url)',method_exists('uri','url'));
Unit_Test::echo_endl();

Config::set('system.script_url','http://test.genee.cn');
class Uri_Test{
	static function test_url($info, $url, $query, $fragment, $expect){
		$output = URI::url($url, $query, $fragment);
		Unit_Test::assert($info, $output == $expect,$output);
	}
}

// 测试
Unit_Test::echo_title('URI::url测试:');
	Uri_Test::test_url('1',NULL,NULL,NUll,'http://test.genee.cn/');
	Uri_Test::test_url('2','aa','bb=1','cc','http://test.genee.cn/aa?bb=1#cc');
	Uri_Test::test_url('3','http://aa','bb=1','cc','http://aa/?bb=1#cc');
Unit_Test::echo_endl();
