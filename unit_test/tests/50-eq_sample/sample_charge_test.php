<?php

class sample_charge_test
{
	public $backup_file;
	public $db;
	public $user;
	public $user2;
	public $lab;
	public $lab2;
	public $equipment;
	public $dept;
	public $acct;
	public $acct2;

	/* 环境设置 */
	function set_up() {
        define('DISABLE_NOTIFICATION', TRUE);
        
        Unit_Test::echo_title('备份数据库');
        $backup_file = tempnam('/tmp', 'database');
        //unit test 时使用ut数据库， 解决database connect error问题
        Database::reset();
        $db = Database::factory('ut');

		$ret = $db->snapshot($backup_file);
		Unit_Test::assert('数据库备份', $ret);
		Unit_Test::echo_endl();
		$this->backup_file = $backup_file;
		$this->db = $db;
        ORM_Model::destroy('equipment');
        ORM_Model::destroy('user');
        ORM_Model::destroy('lab');
        ORM_Model::destroy('eq_sample');
        ORM_Model::destroy('billing_department');
        ORM_Model::destroy('billing_account');
        ORM_Model::destroy('billing_transaction');

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
	}

	function make_environment() {
		$rand = rand();

		Unit_Test::echo_title('环境准备');
		$user_name = 'otto' . $rand;
		$user = O('user');
		$user->token = Auth::normalize($user_name);
		$user->name = $user_name;
		$user->email = $user_name . '@foo.bar';
		$user->atime = 1;
		Unit_Test::assert('准备使用者1', $user->save());
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
		Unit_Test::assert('准备实验室1', $lab->save());
		$user->lab = $lab;
		$user->save();
		$this->lab = $lab;

		$lab_name2 = 'NFI2' . $rand;
		$lab2 = O('lab');
		$lab2->name = $lab_name2;
		$lab2->owner = $user2;
		Unit_Test::assert('准备实验室2', $lab2->save());
		$user2->lab = $lab2;
		$user2->save();
		$this->lab2 = $lab2;

		//考虑单财务和多财务部门的情况
		if (!$GLOBALS['preload']['billing.single_department']) {
		    //多财务部门模式下相关设定
		    $dept_name = 'west' . $rand;
			$dept = O('billing_department');
			$dept->name = $dept_name;
			Unit_Test::assert('准备财务部门', $dept->save());
			$this->$dept = $dept;        
		}
		else {
		    //单财务部门模式下相关设定
		    $dept = self::unique_dept_get();
		    Q('billing_transaction')->delete_all();
		    Unit_Test::assert('准备财务部门', $dept->save());
		}
			
		$acct = O('billing_account');
		$acct->department = $dept;
		$acct->lab = $lab;
		Unit_Test::assert('准备财务账户1', $acct->save());

		$acct2 = O('billing_account');
		$acct2->department = $dept;
		$acct2->lab = $lab2;
		Unit_Test::assert('准备财务账户2', $acct2->save());


		$trans = O('billing_transaction');
		$trans->income = 5000;
		$trans->account = $acct;
		$trans->status = Billing_Transaction_Model::STATUS_CONFIRMED;
		$trans->save();

		$trans2 = O('billing_transaction');
		$trans2->income = 5000;
		$trans2->account = $acct2;
		$trans2->status = Billing_Transaction_Model::STATUS_CONFIRMED;
		$trans2->save();

		$acct = ORM_Model::refetch($acct);
		$acct2 = ORM_Model::refetch($acct2);

		Unit_Test::assert('准备财务账户1充值5000', $acct->get('balance', TRUE) == 5000);
		Unit_Test::assert('准备财务账户2充值5000', $acct2->get('balance', TRUE) == 5000);

		$this->acct = $acct;
		$this->acct2 = $acct2;

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
		$equipment->sample_charge_mode = EQ_Sample_Model::CHARGE_MODE_SAMPLES;
		$equipment->sample_unit_price = 10;
		$equipment->sample_minimum_fee = 0;
		$equipment->accept_sample = TRUE;

		Unit_Test::assert('准备仪器', $equipment->save());

		$equipment->connect($eq_incharge, 'contact');
		$equipment->connect($eq_incharge, 'incharge');

		$this->equipment = $equipment;
	}

	/* 环境还原 */
	function tear_down() {
		Unit_Test::echo_title('还原数据库');
		$ret = $this->db->restore($this->backup_file);
		Unit_Test::assert('导入备份数据库', $ret);
		Unit_Test::echo_endl();

		$this->backup_file = NULL;
		$this->db = NULL;
		$this->user = NULL;
		$this->user2 = NULL;
		$this->lab = NULL;
		$this->lab2 = NULL;
		$this->equipment = NULL;
		$this->dept = NULL;
		$this->acct = NULL;
		$this->acct2 = NULL;
	}

	static function mk_sample($equipment, $user, $dtstart, $dtend, $samples = 1) {
		if (!is_numeric($dtstart)) {
			$dtstart = strtotime($dtstart);
		}
		if (!is_numeric($dtend)) {
			$dtend = strtotime($dtend) - 1;
		}

		$amount = $equipment->sample_unit_price * $samples + $equipment->sample_minimum_fee;
		
		$sample = O('eq_sample');
		$sample->equipment = $equipment;
		$sample->count = $samples;
		$sample->status = EQ_Sample_Model::STATUS_APPLIED;
		$sample->dtstart = $dtstart;
		$sample->dtend = $dtend;
		$sample->amount = $amount;
		$sample->sender = $user;
		$sample->lab = $user->lab;
		$sample->save();
		return $sample;
	} 

    static function unique_dept_get() {
        return Billing_Department::get();
    }

	static function mk_sample_change_sender($sample, $user) {
		$sample->sender = $user;
		$sample->lab = $user->lab;
		$sample->save();
		return $sample;
	}

}

if (!function_exists('println')) {
    function println($s)
    {
        print "{$s}\n";
    }
}
