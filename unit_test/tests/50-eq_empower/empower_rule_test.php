<?php

class empower_rule_test
{
	const FAILED = "\033[31mFAILED\033[0m";
	const PASSED = "\033[32mPASSED\033[0m";
	private $equipment;
	private $empower;
	private $dtstart;
	private $dtend;
	private $targetuser;
	/* 环境设置 */
	function set_up() {
		$this->equipment = new Equipment_Mock_Model();
		$this->equipment->id = 1;
		$this->empower = new EQ_Empower_Mock_Model();
		$this->empower->id = 1;
		$this->targetuser = null;
   	}

	//预约时间在规则范围内
	//输入规则：每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：8:00:00-11:00:00
	//检测时间：2014-11-8 9:00:00 -11:00:00
	//期望输出：允许预约
	function function1(){
		$this->dtstart = mktime(9,0,0,11,8,2014);
		$this->dtend = mktime(11,0,0,11,8,2014);
		$c_startdate = mktime(0,0,0,11,7,2014);
		$c_enddate = mktime(0,0,0,11,7,2015);
		$c_starttime = mktime(8,0,0,1,1,1971);
		$c_endtime = mktime(11,0,0,1,1,1971);
		$this->empower->rules = '{"1":{"startdate":'.$c_startdate.',"enddate":'.$c_enddate.',"starttime":'.$c_starttime.',"endtime":'.$c_endtime.',"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('预约时间在规则范围内', $this->get_result());
	}

	//预约时间未在规则范围内
	//输入规则：每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：8:00:00-11:00:00
	//检测时间：2014-11-8 6:00:00 -9:00:00
	//期望输出：不许允许预约
	function function2() {
		$this->dtstart = mktime(6,0,0,11,8,2014);
		$this->dtend = mktime(9,0,0,11,8,2014);
		$c_startdate = mktime(0,0,0,11,7,2014);
		$c_enddate = mktime(0,0,0,11,7,2015);
		$c_starttime = mktime(8,0,0,1,1,1971);
		$c_endtime = mktime(11,0,0,1,1,1971);
		$this->empower->rules = '{"1":{"startdate":'.$c_startdate.',"enddate":'.$c_enddate.',"starttime":'.$c_starttime.',"endtime":'.$c_endtime.',"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('预约时间未在规则范围内', !$this->get_result());
	}

	//时间规则重叠，预约在范围内
	//输入规则：1. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：8:00:00-11:00:00
	//			2. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：10:00:00-13:00:00
	//检测时间：2014-11-8 9:00:00 -12:00:00
	//期望输出：允许预约
	function function3(){
		$this->dtstart = mktime(9,0,0,11,8,2014);
		$this->dtend = mktime(12,0,0,11,8,2014);
		$c_startdate = mktime(0,0,0,11,7,2014);
		$c_enddate = mktime(0,0,0,11,7,2015);
		$c_starttime = mktime(8,0,0,1,1,1971);
		$c_endtime = mktime(11,0,0,1,1,1971);
		$c_startdate1 = mktime(0,0,0,11,7,2014);
		$c_enddate1 = mktime(0,0,0,11,7,2015);
		$c_starttime1 = mktime(10,0,0,1,1,1971);
		$c_endtime1 = mktime(13,0,0,1,1,1971);
		$this->empower->rules = '{"1":{"startdate":'.$c_startdate.',"enddate":'.$c_enddate.',"starttime":'.$c_starttime.',"endtime":'.$c_endtime.',"rtype":"1","rnum":"1"},"2":{"startdate":'.$c_startdate1.',"enddate":'.$c_enddate1.',"starttime":'.$c_starttime1.',"endtime":'.$c_endtime1.',"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('时间规则重叠，预约在范围内', $this->get_result());
	}

	//时间时间重叠，预约未在范围内
	//输入规则：1. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：9:00:00-11:00:00
	//			2. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：10:00:00-13:00:00
	//检测时间：2014-11-8 7:00:00 -12:00:00
	//期望输出：不能预约
	function function4(){
		$this->dtstart = mktime(7,0,0,11,8,2014);
		$this->dtend = mktime(12,0,0,11,8,2014);
		$c_startdate = mktime(0,0,0,11,7,2014);
		$c_enddate = mktime(0,0,0,11,7,2015);
		$c_starttime = mktime(9,0,0,1,1,1971);
		$c_endtime = mktime(11,0,0,1,1,1971);
		$c_startdate1 = mktime(0,0,0,11,7,2014);
		$c_enddate1 = mktime(0,0,0,11,7,2015);
		$c_starttime1 = mktime(10,0,0,1,1,1971);
		$c_endtime1 = mktime(13,0,0,1,1,1971);
		$this->empower->rules = '{"1":{"startdate":'.$c_startdate.',"enddate":'.$c_enddate.',"starttime":'.$c_starttime.',"endtime":'.$c_endtime.',"rtype":"1","rnum":"1"},"2":{"startdate":'.$c_startdate1.',"enddate":'.$c_enddate1.',"starttime":'.$c_starttime1.',"endtime":'.$c_endtime1.',"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('时间规则重叠，预约未在范围内', !$this->get_result());
	}

	//以月循环
	//输入规则：1. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：8:00:00-9:00:00
	//			2. 每2天重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：10:00:00-11:00:00
	//			3. 每周重复，起始日期2013-11-14，结束结束2015-11-15，起始时间：12:00:00-13:00:00
	//			4. 每月重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：14:00:00-15:00:00
	//			5. 每年重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：16:00:00-17:00:00
	//检测时间：2014-11-8 14:00:00 -15:00:00
	//期望输出：能预约
	function function5(){
		echo '以月循环规则 ';
		$this->dtstart = mktime(14,0,0,11,10,2014);
		$this->dtend = mktime(15,0,0,11,10,2014);
		$this->empower->rules = '{"1":{"startdate":1415289600,"enddate":1446872399,"starttime":31536000,"endtime":31539600,"rtype":"1","rnum":"1"},"2":{"startdate":1383753600,"enddate":1478494799,"starttime":31543200,"endtime":31546800,"rtype":"1","rnum":"2"},"3":{"startdate":1384358400,"enddate":1447477199,"starttime":31550400,"endtime":31554000,"rtype":"2","rnum":"1","week_day":[1,4]},"4":{"startdate":1384358400,"enddate":1447477199,"starttime":31557600,"endtime":31561200,"rtype":"3","rnum":"1","month_day":[2,10,13,14,15,18,26]},"5":{"startdate":1384358400,"enddate":1447477199,"starttime":31564800,"endtime":31568400,"rtype":"4","rnum":"1","year_month":[10,11]}}';
		//$this->empower->rules = '{"1":{"startdate":1384358400,"enddate":1447477199,"starttime":31557600,"endtime":31561200,"rtype":"3","rnum":"1","month_day":[2,10,13,14,15,18,26]}}';
		$this->output($this->get_result());
	}

	//以年循环
	//输入规则：1. 每天重复，起始日期2014-11-7，结束结束2015-11-7，起始时间：8:00:00-9:00:00
	//			2. 每2天重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：10:00:00-11:00:00
	//			3. 每周重复，起始日期2013-11-14，结束结束2015-11-15，起始时间：12:00:00-13:00:00
	//			4. 每月重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：14:00:00-15:00:00
	//			5. 每年重复，起始日期2013-11-7，结束结束2016-11-7，起始时间：16:00:00-17:00:00
	//检测时间：2014-11-8 16:00:00 -17:00:00
	//期望输出：能预约
	function function6(){
		$this->dtstart = mktime(16,0,0,11,8,2014);
		$this->dtend = mktime(17,0,0,11,8,2014);
		$this->empower->rules = '{"1":{"startdate":1415289600,"enddate":1446872399,"starttime":31536000,"endtime":31539600,"rtype":"1","rnum":"1"},"2":{"startdate":1383753600,"enddate":1478494799,"starttime":31543200,"endtime":31546800,"rtype":"1","rnum":"2"},"3":{"startdate":1384358400,"enddate":1447477199,"starttime":31550400,"endtime":31554000,"rtype":"2","rnum":"1","week_day":[1,4]},"4":{"startdate":1384358400,"enddate":1447477199,"starttime":31557600,"endtime":31561200,"rtype":"3","rnum":"1","month_day":[2,10,13,14,15,18,26]},"5":{"startdate":1384358400,"enddate":1447477199,"starttime":31564800,"endtime":31568400,"rtype":"4","rnum":"1","year_month":[10,11]}}';
		Unit_Test::assert('以年循环规则', $this->get_result());
	}

	//跨天预约
	//输入规则：每天重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:59
	//检测时间：2014-11-8 21:00:00 - 2014-11-15 1:00:00
	//期望输出：能预约
	function function7() {
		$this->dtstart = mktime(21,0,0,11,14,2014);
		$this->dtend = mktime(1,0,0,11,15,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593599,"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('跨天预约', $this->get_result());
	}

	//不能跨天预约
	//输入规则：每天重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:50
	//检测时间：2014-11-8 21:00:00 - 2014-11-15 1:00:00
	//期望输出：不能预约
	function function8() {
		$this->dtstart = mktime(21,0,0,11,8,2014);
		$this->dtend = mktime(1,0,0,11,9,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593590,"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('不能跨天预约', !$this->get_result());
	}

	//跨工作日预约
	//输入规则：每工作日重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:59
	//检测时间：2014-11-8 21:00:00 - 2014-11-15 1:00:00
	//期望输出：能预约
	function function9() {
		$this->dtstart = mktime(21,0,0,11,13,2014);
		$this->dtend = mktime(1,0,0,11,14,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593599,"rtype":"-2","rnum":"1","week_day":["1","2","3","4","5"]}}';
		Unit_Test::assert('工作日预约跨天预约', $this->get_result());
	}

	//不能跨工作日预约
	//输入规则：每工作日重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:50
	//检测时间：2014-11-8 21:00:00 - 2014-11-15 1:00:00
	//期望输出：能预约
	function function10() {
		$this->dtstart = mktime(21,0,0,11,13,2014);
		$this->dtend = mktime(1,0,0,11,14,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593590,"rtype":"-2","rnum":"1","week_day":["1","2","3","4","5"]}}';
		Unit_Test::assert('不工作日预约跨天预约', !$this->get_result());
	}

	//周末跨天预约
	//输入规则：每周末重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:59
	//检测时间：2014-11-8 23:00:00 - 2014-11-15 1:00:00
	//期望输出：能预约
	function function11() {
		$this->dtstart = mktime(23,0,0,11,15,2014);
		$this->dtend = mktime(1,0,0,11,16,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593599,"rtype":"-3","rnum":"1","week_day":["0","6"]}}';
		Unit_Test::assert('周末预约跨天预约', $this->get_result());
	}

	//跨周预约
	//输入规则：每天重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:59
	//检测时间：2014-11-15 21:00:00 - 2014-11-16 1:00:00
	//期望输出：能预约
	function function12() {
		$this->dtstart = mktime(21,0,0,11,15,2014);
		$this->dtend = mktime(1,0,0,11,16,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593599,"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('设置每日跨周预约', $this->get_result());
	}

	//跨月预约
	//输入规则：每天重复，起始日期2014-11-12，结束结束2015-11-12，起始时间：00:00:00-23:59:59
	//检测时间：2014-11-30 21:00:00 - 2014-12-01 1:00:00
	//期望输出：能预约
	function function13() {
		$this->dtstart = mktime(21,0,0,11,30,2014);
		$this->dtend = mktime(1,0,0,12,1,2014);
		$this->empower->rules = '{"1":{"startdate":1415721600,"enddate":1447304399,"starttime":31507200,"endtime":31593599,"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('设置每日跨月预约', $this->get_result());
	}

	//跨月预约
	//输入规则：1. 每天重复，起始日期2014-11-07，结束结束2015-11-07，起始时间：08:00:00-09:00:00
	//			2. 每天重复，起始日期2014-11-07，结束结束2015-11-07，起始时间：10:00:00-11:00:00
	//			3. 每天重复，起始日期2014-11-07，结束结束2015-11-07，起始时间：09:00:00-10:00:00
	//检测时间：2014-11-16 08:30:00 - 10:30:00
	//期望输出：能预约
	function function14() {
		$this->dtstart = mktime(8,30,0,11,16,2014);
		$this->dtend = mktime(10,30,0,11,16,2014);
		$this->empower->rules = '{"1":{"startdate":1415289600,"enddate":1446872399,"starttime":31536000,"endtime":31539600,"rtype":"1","rnum":"1"},"2":{"startdate":1415289600,"enddate":1446872399,"starttime":31543200,"endtime":31546800,"rtype":"1","rnum":"1"},"3":{"startdate":1415289600,"enddate":1446872399,"starttime":31539600,"endtime":31543200,"rtype":"1","rnum":"1"}}';
		Unit_Test::assert('规则拼接', $this->get_result());
	}

	function run() {
		echo "run case\n";
//		$this->function5();
		for($i = 1;$i<=14; $i++) {
			$func_name = 'function'.strval($i);
			$this->$func_name();
		}
	}

	function get_result() {
		return EQ_Empower::check_workingtime($this->equipment, $this->dtstart, $this->dtend, $this->targetuser, $this->empower);
	}

	/* 环境还原 */
	function tear_down() {
	}
}

//Mock model
class Equipment_Mock_Model {
	public $id;
}

class EQ_Empower_Mock_Model {
	public $id;
	public $rules;
}
