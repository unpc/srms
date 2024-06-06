<?php

/*
 * @file charge_test.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date 2011-07-26
 *
 * @brief 根据不同收费方式、不同预约情况、不同使用情况进行仪器计费测试
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/charge_test

	此脚本用来测试仪器计费
	(xiaopei.li@2011.07.26)

	0. 需要准备
	财务
	实验室
	人员
	仪器
	记录

	1. 收费方式
	免费
	按时间
	按次数
	按样品数
	按自定义方式

	2. 使用方式(参考收费结算方式):
	a. 无预约
	b. 按时使用
	c. 按时间断使用
	d. 超时使用
	e. 爽约
	f. 被他人占用时间
	g. 使用期间被管理员占用时间
	h. 一次超时，一次爽约
	i. 一次超时
  */
if (!Module::is_installed('eq_charge') || !Module::is_installed('billing')) return TRUE;

require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

class charge_test
{
	public $db;
	public $user;
	public $lab;
	public $equipment;
	public $dept;
	public $acct;

	/* 环境设置 */
	function set_up() {
        define('DISABLE_NOTIFICATION', TRUE);
        
        //unit test 时使用ut数据库， 解决database connect error问题
        Database::reset();
        $db = Database::factory();

        $db->empty_database();

        Database::reset();
	}

	function make_environment($die = FALSE) {
        ORM_Model::destroy('user');
        ORM_Model::destroy('lab');
        ORM_Model::destroy('equipment');
        ORM_Model::destroy('eq_record');
        ORM_Model::destroy('eq_charge');
        ORM_Model::destroy('calendar');
        ORM_Model::destroy('cal_component');
        ORM_Model::destroy('billing_department');
        ORM_Model::destroy('billing_account');
        ORM_Model::destroy('billing_transaction');

        Database::factory()->empty_database();
        
        // 2016-1-21 unpc 清空数据库之后需要更新ORM-S以保证数据表的存在
        foreach(Config::$items['schema'] as $name=>$schema) {
			$db = Database::factory();
			$schema = ORM_Model::schema($name);
			if ($schema) {
				$ret = $db->prepare_table($name, $schema);
		        if (!$ret) {
		            echo $name."表更新失败\n";
		        }
			}
		}

        Database::reset();

        if ($die) {
            die();
        }

		$rand = rand();

		Unit_Test::echo_title('环境准备');
		$user_name = 'otto' . $rand;
		$user = O('user');
		$user->token = Auth::normalize($user_name);
		$user->name = $user_name;
		$user->email = $user_name . '@foo.bar';
		$user->atime = 1;
		Unit_Test::assert('准备使用者', $user->save());
		$this->user = $user;

		//第二个用户
		$user_name2 = 'otto2' . $rand;
		$user2 = O('user');
		$user2->token = Auth::normalize($user_name2);
		$user2->name = $user_name2;
		$user2->email = $user_name2 . '@foo.bar';
		$user2->atime = 1;
		Unit_Test::assert('准备使用者2', $user2->save());
		$this->user2 = $user2;

		$lab_name = 'NFI' . $rand;
		$lab = O('lab');
		$lab->name = $lab_name;
		$lab->owner = $user;
		Unit_Test::assert('准备实验室', $lab->save());
		$user->lab = $lab;
		$user2->lab = $lab;
		$user->save();
		$this->lab = $lab;

		//考虑单财务和多财务部门的情况
		$GLOBALS['preload']['billing.single_department'] = FALSE;

        $dept_name = 'russia' . $rand;
        $dept = O('billing_department');
        $dept->name = $dept_name;
        Unit_Test::assert('准备财务部门', $dept->save());
        $this->$dept = $dept;        
			
		$acct = O('billing_account');
		$acct->department = $dept;
		$acct->lab = $lab;
		Unit_Test::assert('准备财务账户', $acct->save());

		$trans = O('billing_transaction');
		$trans->income = 5000;
		$trans->account = $acct;
		$trans->status = Billing_Transaction_Model::STATUS_CONFIRMED;
		$trans->save();

		$acct = ORM_Model::refetch($acct);

		Unit_Test::assert('账户充值5000', $acct->get('balance', TRUE) == 5000);

		$this->acct = $acct;

		$equipment_name = 'buran' . $rand;
		$eq_incharge = O('user');
		$eq_incharge->name = $equipment_name;
		$eq_incharge->token = Auth::normalize($equipment_name);
		$eq_incharge->email = $equipment_name . '@foo.bar';
		$eq_incharge->atime = 1;
		Unit_Test::assert('准备仪器负责人', $eq_incharge->save());

		$equipment = O('equipment');
		$equipment->name = $equipment_name;
		$equipment->contact = $eq_incharge;
		$equipment->billing_dept = $dept;
		$equipment->accept_reserv = TRUE;

		$equipment = $this->set_up_equipment($equipment);

		Unit_Test::assert('准备仪器', $equipment->save());

		$equipment = $this->set_up_charge($equipment);

		$equipment->connect($eq_incharge, 'contact');
		$equipment->connect($eq_incharge, 'incharge');

		$this->equipment = $equipment;

        Database::reset();
	}

	/* 环境还原 */
	function tear_down() {

		$this->user = NULL;
		$this->lab = NULL;
		$this->equipment = NULL;
		$this->dept = NULL;
		$this->acct = NULL;

        Database::reset();
	}

	static function mk_reserv($equipment, $orgnizer, $dtstart, $dtend) {

		if (!is_numeric($dtstart)) {
			$dtstart = strtotime($dtstart);
		}

		if (!is_numeric($dtend)) {
			$dtend = strtotime($dtend) - 1;
		}

		$reserv = O('cal_component');
		$reserv->organizer = $orgnizer;

		$calendar = Q("{$equipment}<parent calendar:limit(1)")->current();
		if (!$calendar) {
			$calendar = O('calendar');
			$calendar->parent = $equipment;
			$calendar->name = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
			$calendar->save();
		}

		$reserv->calendar = $calendar;
		$reserv->type = 0;		/* 预约 */

		$reserv->dtstart = $dtstart;
		$reserv->dtend = $dtend;
		$reserv->name = '预约时段';

		$reserv->save();

		return $reserv;
	}

	static function mk_record($equipment, $user, $dtstart, $dtend, $samples = 1) {
		if (!is_numeric($dtstart)) {
			$dtstart = strtotime($dtstart);
		}
		if (!is_numeric($dtend)) {
			$dtend = strtotime($dtend) - 1;
		}

		$record = O('eq_record');
		$record->equipment = $equipment;
		$record->user = $user;

		$record->dtstart = $dtstart;
		$record->dtend = $dtend;
		$record->samples = $samples;

		$record->save();


		return $record;
	}

	static function mk_record_and_assert_auto_amount_before_save($amount, $equipment, $user, $dtstart, $dtend, $samples = 1) {
		if (!is_numeric($dtstart)) {
			$dtstart = strtotime($dtstart);
		}
		if (!is_numeric($dtend)) {
			$dtend = strtotime($dtend) - 1;
		}

		$record = O('eq_record');
		$record->equipment = $equipment;
		$record->user = $user;

		$record->dtstart = $dtstart;
		$record->dtend = $dtend;
		$record->samples = $samples;

		$charge = O('eq_charge');
        $charge->source = $record;
        $lua = new EQ_Charge_LUA($charge);
        $result = $lua->run(['fee']);
        $fee = $result[fee];

		$ret = ($fee == $amount);
		Unit_Test::assert("保存前使用记录的auto_amount为 {$amount}", $ret);
		if (!$ret) {
			printf("测试结果为 %.2f\n", $fee);
		}
		
		$record->save();

		return $record;
	}

	function assert_balance($assertion, $condition) {
		$balance = ORM_Model::refetch($this->acct)->get('balance', TRUE);
		$ret = ($condition == $balance) || ($condition == round($balance)) || ($condition == (int)$balance);

		Unit_Test::assert($assertion, $ret);
		if (!$ret) {
			printf("测试结果为 %.2f\n", $balance);
		}
	}

	static function unique_dept_get() {
		$department = null;
		if ($GLOBALS['preload']['billing.single_department']) {
			$department = Q('billing_department')->current();
			if (!$department->id) {
				$department = O('billing_department');
				$department->name = I18N::T('billing', '财务部门');
				$department->save();
			}
		}
		return $department;
	}
}

if (!function_exists('println')) {
    function println($s)
    {
        print "{$s}\n";
    }
}
