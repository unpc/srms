<?php
/*
 * @file date.php
 * @author Jia Huang <jia.huang>
 * @date 2012-07-02
 *
 * @brief 测试Date基类中通过传入日期范围，获取日期范围内工作日天数的函数get_work_days函数功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/01-application/date

函数：get_work_days();
	1.测试数据
		输入：'2010-08-02','2010-08-06',TRUE;
		输出：5;
	2.测试数据
		输入：'2010-08-07','2010-08-08',FALSE;
		输出：2;		
*/
//创建环境
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
Unit_Test::assert("class_exists(date)",class_exists('date'));
Unit_Test::assert("method_exists(date::get_work_days)",method_exists('date','get_work_days'));

Unit_Test::echo_endl();
class Date_Test{
	static function test_get_work_days($info,$start_date,$end_date,$is_workday,$expect){
	    $output=Date::get_work_days($start_date,$end_date,$is_workday);
		Unit_Test::assert($info, $output == $expect, $output);	
	}
}

//测试
Unit_Test::echo_title('Date::get_work_days测试:');
	Date_Test::test_get_work_days('1','2010-08-02','2010-08-06',TRUE,5);
    Date_Test::test_get_work_days('2','2010-08-07','2010-08-08',FALSE,2);
Unit_Test::echo_endl();
