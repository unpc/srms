<?php 

/* 
 * @file gismon.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 地理监控模块基类GISMon 中lon_lat_format函数测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/gismon/gismon
 */
if (!Module::is_installed('gismon')) return true;
//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Environment::init_site();
Unit_Test::assert("class_exists('gismon')", class_exists('GISMon'));
Unit_Test::assert("method_exists('GISMon::lon_lat_format')", method_exists('GISMon', 'lon_lat_format'));

Unit_Test::echo_endl();

class Test_Gismon{
	function test_lon_lat_format($str, $lot, $lat, $value){
		$out = GISMon::lon_lat_format($lot, $lat);
		Unit_Test::assert($str, $out == $value, $out);
	}
}

Unit_Test::echo_title("测试GISMon::lot_lat_format");
	$v = 'W -1° 0\' 0.00" / S -1° 0\' 0.00"';
	Test_Gismon::test_lon_lat_format('1', '-1', '-1', $v);
	$v = 'E 0° 0\' 0.00" / S -1° 0\' 0.00"';
	Test_Gismon::test_lon_lat_format('2', '0', '-1', $v);
	$v = 'W -1° 0\' 0.00" / N 0° 0\' 0.00"';
	Test_Gismon::test_lon_lat_format('3', '-1', '0', $v);
	$v = 'E 1° 0\' 0.00" / N 1° 0\' 0.00"';
	Test_Gismon::test_lon_lat_format('4', '1', '1', $v);
Unit_Test::echo_endl();
