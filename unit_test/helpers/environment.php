<?php


class Environment {

	static $backend = 'database';

	static function echo_error($msg){
		echo "\033[31m".$msg."\033[0m";
	}

	static function echo_hl($msg){
		echo "\033[1m".$msg."\033[0m";
	}

	static function echo_green($msg){
		echo "\033[32m".$msg."\033[0m";
	}

	static function init_site() {
		echo "清空数据库";
		$db = Database::factory();
		$db->empty_database();
		Database::reset();

        //需要销毁如下ORM
        ORM_Model::destroy('role');
        ORM_Model::destroy('user');
        ORM_Model::destroy('lab');
        ORM_Model::destroy('tag');
        ORM_Model::destroy('equipment');
        ORM_Model::destroy('eq_record');
        ORM_Model::destroy('eq_charge');
        ORM_Model::destroy('eq_sample');
        ORM_Model::destroy('door');
        ORM_Model::destroy('billing_department');
        ORM_Model::destroy('billing_account');
        ORM_Model::destroy('billing_transaction');
        ORM_Model::destroy('calendar');
        ORM_Model::destroy('cal_component');
        ORM_Model::destroy('follow');

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


		self::echo_green("成功！\n");
		self::add_user('技术支持', 'genee', 'Genee123456', 'genee@geneegroup.com', '83719730');
	}


	static function add_user($name, $token=NULL, $password='123456', $email=NULL, $phone=NULL) {

		if( !$token ){
			$token = str_replace(' ', '', PinYin::code($name) );
		}

		if( !$email ) {
			$email = $token.'@geneegroup.com';
		}

		if( !$phone ) {
			$phone = rand(10000000, 99999999 );
		}


		echo "创建帐号：$name";
		$token = Auth::make_token($token, self::$backend );
		$auth  = new Auth($token);
		try {
			if (User_Model::is_reserved_token($token)) {
				throw new Exception('登录帐号已被保留！');
			}

			if (O('user', ['token'=>$token])->id ) {
				throw new Exception('帐号在系统中已存在！');
			}

			if (O('user', ['email'=>$email])->id ) {
				throw new Exception('邮箱在系统中已存在！');
			}

			$user = O('user');
			$user->name  = $name;
			$user->token = $token;
			$user->email = $email;
			$user->phone = $phone;	
			$user->atime = Date::time();
			$user->ref_no = null;

			if ( !$user->save() ) {
				throw new Exception('user保存失败');
			}

			if ( !$auth->create($password) ) {
				throw new Exception('auth保存失败');
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

		return $user;
	}

	static function add_role($name, $perms=[] ) {
	
		$role = O('role');

		echo "创建角色：$name";
		try {
			$role->name = $name;
			$role->weight = 0;
			if ( !$role->save() ){
				throw new Exception("角色保存失败");
			}

			$role_perms = [];
			foreach($perms as $perm) {
				$role_perms[$perm] = 'on';
			}
			if ( !Properties::factory($role)->set('perms', $role_perms)->save() ) {
				throw new Exception('权限保存失败');
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}


		return $role;
    }

    static function set_role_perms($role_name, $perms=[]) {

        try {
            echo "设定角色：$role_name";

            $roles_arr = array_flip(L('ROLES')->to_assoc('id', 'name'));
            $roles = L('ROLES');
            $role = $roles[($roles_arr[$role_name])];

            if (!$role->id)  throw new Exception('获取角色失败！');

			$role_perms = [];

			foreach($perms as $perm) {
				$role_perms[$perm] = 'on';
			}
			if ( !Properties::factory($role)->set('perms', $role_perms)->save() ) {
				throw new Exception('权限保存失败');
			}

			self::echo_green("成功！\n");
        }
        catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
        }

        return $role;
    }

    //增加系统设置中仪器标签所使用的函数
    static function add_eq_tag($name) {
        $root = Tag_Model::root('equipment');
        $tag = O('tag');

		echo "创建仪器标签：$name";
		try {
			$tag->name   = $name;
			$tag->root   = $root;
			$tag->parent = $root;

			if ( !$tag->save() ) {
				throw new Exception('tag保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

		return $tag;
    }

    //增加仪器使用记录,time为null则随机增加使用记录
    static function add_eq_record($equipment, $user, $time = NULL) {
        echo "为 $equipment->name 创建 $user->name 的使用记录";
        do {
            $record = O('eq_record');
            $record->user = $user;
            $record->equipment = $equipment;
            $record->dtstart = rand(0, Date::time());
            $record->dtend = $time ? $record->dtstart + $time : rand($record->dtstart, Date::time());
            if (!$record->save()) {
                self::echo_error("失败，尝试重新创建");
            }
            self::echo_green("成功！\n");
        } while(!$record->save());
    }

    static function add_eq_record_by_dtstart_and_dtend($equipment, $user, $dtstart, $dtend, $samples=1) {
        echo "为 $equipment->name 创建 $user->name 的使用记录";
        if (!$dtstart) {
            self::echo_error("起始时间不能为空！\n");
            die;
        }

        if (!$dtend) {
            self::echo_error("结束时间不能为空！\n");
            die;
        }
        $record = O('eq_record');
        $record->user = $user;
        $record->equipment = $equipment;
        $record->dtstart = $dtstart;
        $record->dtend = $dtend;
        $record->samples = $samples;
        if ($record->save()) {
            self::echo_green("成功！\n");
        }
        else {
            self::echo_error("失败\n");
        }
        $record->status = EQ_Reserv::RECORD_RESERV_USED;

        return $record;
    }

	static function add_group($name, $parent=NULL) {
		$root = Tag_Model::root('group');
		$tag = O('tag');

		echo "创建机构：$name";
		try {
			$tag->name   = $name;
			$tag->root   = $root;
			$tag->parent = $parent?:$root;

			if ( !$tag->save() ) {
				throw new Exception('tag保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

		return $tag;
	}

	static function add_lab($name, $owner, $group = null ) {
		$lab = O('lab');

		echo "创建实验室：$name";
		try {
			if ( !$owner instanceof User_Model || !$owner->id ) {
				throw new Exception('owner对象不正确');
			}

			$lab->name = $name;
			$lab->owner = $owner;
			$lab->group = $group;
			$lab->atime = Date::time();

			if( !$lab->save() ) {
				throw new Exception('lab保存失败');
			}
	
			if ($group){	
				$group->connect($lab);
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}


        //创建实验室，并设定某人为实验室的pi后，应同时设定该用户所在实验室为自己负责的实验室
        self::set_lab($owner, $lab);

		return $lab;
	}

    static function add_lab_project($lab, $name) {
        echo "为 $lab->name 创建项目 $name";
        $project = O('lab_project');
        $project->lab = $lab;
        $project->name = $name;
        if ($project->save()) {
            self::echo_green("成功!\n");
        }
        else {
            self::echo_error("失败!\n");
        }
        return $project;
    }

	static function add_equipment($name, $incharges, $contacts=null) {

		$equipment = O('equipment');

		echo "创建仪器：$name";
		try {

			$equipment->name = $name;
			if ( !$equipment->save() ){
				throw new Exception('equipment保存失败');
			}

            if ($contacts === NULL) {
                $contacts = $incharges;
            }

			if ( !is_array($contacts) ){
				$contacts = [$contacts];
			}
			foreach( $contacts as $contact ) {
				$equipment->connect($contact, 'contact');
				$contact->follow($equipment);
			}

			if( !is_array($incharges) ){
				$incharges = [$incharges];
			}
			foreach( array_merge($incharges,$contacts) as $incharge ) {
				$equipment->connect($incharge, 'incharge');
				$incharge->follow($equipment);
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

		return $equipment;
		
	}

	static function add_door($name, $incharger=NULL ) {
		echo "创建门禁：$name";
		$door = O('door');
		try {

			$door->name = $name;
			$door->incharger = $incharger;
			if ( !$door->save() ){
				throw new Exception('equipment保存失败');
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

		return $door;
	}

	static function set_group($obj, $group ) {
		echo "设置{$obj->name}组织机构为：{$group->name}";
		try {
            $group->connect($obj);
            $obj->group = $group;
			if (!$obj->save()) {
				throw new Exception("组织机构设置失败");
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $obj;
	}

	static function set_lab($obj, $lab ) {
		echo "设置{$obj->name}的实验室为：{$lab->name}";
		try {
			$obj->lab = $lab;
			if (!$obj->save()) {
				throw new Exception("组织机构设置失败");
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $obj;
	}

	static function set_role($user, $roles) {
        if (!is_array($roles)) $roles = [$roles];

        foreach((array) $roles as $role) {
            echo "为{$user->name}添加角色：{$role->name}";
            try {
                if (!$user->connect($role) ) {
                    throw new Exception('角色设置失败');
                }
                self::echo_green("成功！\n");
            } catch (Exception $e) {
                self::echo_error("错误！\t");
                echo $e->getMessage()."\n";
            }
        }
		return $user;
	}

    static function set_role_privacy($role, $privacy_id) {
        $privacys = Role_Model::$privacy;
        echo "为{$role->name}设置隐私为 $privacys[$privacy_id]";
        try {
            $role->privacy = $privacy_id;
            if (!$role->save()) {
                throw new Exception('角色隐私设置失败');
            }
            self::echo_green("成功！\n");
        }
        catch (Exception $e) {
            self::echo_error("错误！\t");
            echo $e->getMessage()."\n";
        }
    }

	static function set_config($key, $value){
		echo "设置系统变量$key 为：$value";
		try {
			Lab::set($key, $value);
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}

	}

	static function equ_add_tag($equipment, $name, $users){
		echo "为仪器{$equipment->name}添加用户标签：$name ";
		$root = $equipment->get_root();
		$tag  = O('tag');

		try {
			$tag->name = $name;
			$tag->parent = $root;

			$tags = Q("tag[root={$root}]:sort(weight D)");
			if (!count($tags)) {
				$tag->weight = 0;
			}
			else {
				$tag->weight = $tags->current()->weight + 1;
			}

			if ( !$tag->update_root()->save() ){
				throw new Exception('tag保存失败');
			}

			if( !is_array($users) ) {
				$users = [$users];
			}
			foreach($users as $user){
				if( !$tag->connect($user) ){
					throw new Exception('user关联失败');
				}
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
	}

	static function add_department($name, $incharges=NULL) {
		$department = O('billing_department');

		echo "创建财务部门：$name";

		try {
			$department->name = $name;
			if (!$department->save()){
				throw new Exception('department保存失败');
			}

			if ($incharges) {
				if (!is_array($incharges)){
					$incharges = [$incharges];
				}

				foreach ($incharges as $incharge ) {
					if (!$incharge instanceof User_Model || !$incharge->id) {
						continue;
					}
					$incharge->connect($department);
				}
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $department;
	}

	static function add_account($lab, $department) {
		$account = O('billing_account');

		echo "为$lab->name 在$department->name 创建财务帐号";

		try {
			if (!$lab->id) {
				throw new Exception('lab对象错误');
			}
			
			$account->lab = $lab;

			if (!$department->id) {
				throw new Exception('department对象错误');
			}
			
			$account->department = $department;

			if (!$account->save()){
				throw new Exception('account保存失败');
			}

			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $account;
	}

	static function add_transaction($account, $user, $income) {
		$transaction = O('billing_transaction');

		echo "添加费用记录";

		try {
			if (!$account->id) {
				throw new Exception('account对象错误');
			}
			
			$transaction->account = $account;
			$transaction->status = Billing_Transaction_Model::STATUS_CONFIRMED;

			if (!$user->id) {
				throw new Exception('user对象错误');
			}
			
			$transaction->user = $user;
			$transaction->income = round($income, 2);
			$transaction->description = I18N::T('billing', '%user给账户[%account]充值金额。', [
													'%user'=>Markup::encode_Q($user),
													'%account'=>H($account->lab->name)]);

			if (!$transaction->save()){
				throw new Exception('transaction保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $transaction;
	}

	static function add_pending_transaction($account, $user, $income) {
		//添加为确认状态的充值，不计入财务明细
		$transaction = O('billing_transaction');

		echo "添加费用记录";

		try {
			if (!$account->id) {
				throw new Exception('account对象错误');
			}
			
			$transaction->account = $account;
			$transaction->status = Billing_Transaction_Model::STATUS_PENDING;
			$transaction->source = 'billing.nankai';

			if (!$user->id) {
				throw new Exception('user对象错误');
			}
			
			$transaction->user = $user;
			$transaction->income = round($income, 2);
			$transaction->description = I18N::T('billing', '%user给账户[%account]充值金额。', [
													'%user'=>Markup::encode_Q($user),
													'%account'=>H($account->lab->name)]);

			if (!$transaction->save()){
				throw new Exception('transaction保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $transaction;
	}

    static function add_income_transaction($account, $user, $income) {
        self::add_transaction($account, $user, $income);
    }

    static function add_outcome_transaction($account, $user, $outcome) {
		$transaction = O('billing_transaction');

		echo "添加费用记录";

		try {
			if (!$account->id) {
				throw new Exception('account对象错误');
			}
			
			$transaction->account = $account;

			if (!$user->id) {
				throw new Exception('user对象错误');
			}
			
			$transaction->user = $user;
			$transaction->outcome = round($outcome, 2);
			$transaction->status = Billing_Transaction_Model::STATUS_CONFIRMED;
			$transaction->description = I18N::T('billing', '%user给账户[%account]扣费金额。', [
													'%user'=>Markup::encode_Q($user),
													'%account'=>H($account->lab->name)]);

			if (!$transaction->save()){
				throw new Exception('transaction保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $transaction;
    }


    static function add_pending_outcome_transaction($account, $user, $outcome) {
		$transaction = O('billing_transaction');

		echo "添加费用记录";

		try {
			if (!$account->id) {
				throw new Exception('account对象错误');
			}
			
			$transaction->account = $account;

			if (!$user->id) {
				throw new Exception('user对象错误');
			}
			
			$transaction->user = $user;
			$transaction->outcome = round($outcome, 2);
			$transaction->status = Billing_Transaction_Model::STATUS_PENDING;
			$transaction->description = I18N::T('billing', '%user给账户[%account]扣费金额。', [
													'%user'=>Markup::encode_Q($user),
													'%account'=>H($account->lab->name)]);

			if (!$transaction->save()){
				throw new Exception('transaction保存失败');
			}
			self::echo_green("成功！\n");
		} catch (Exception $e) {
			self::echo_error("错误！\t");
			echo $e->getMessage()."\n";
		}
		return $transaction;
    }

    //设定财务帐号信用额度
    static function set_account_credit_line($account, $credit_line) {
        echo  "设定财务帐号信用额度";
        try {
			if (!$account->id || !($account instanceof Billing_Account_Model)) {
				throw new Exception('account对象错误');
			}

            $account->credit_line = (float) $credit_line;
            if (!$account->save()) {
                throw new Exception('财务信用额度设定');
            }
            self::echo_green("成功\n");
        }
        catch(Exception $e) {
            self::echo_error("错误\t");
            echo $e->getMessage(). "\n";
        }
    }

    //Environment::add('user', 'user`s name')
    static function add() {
        $args = func_get_args();
        $type = array_shift($args);

        try {
            $func_name = 'add_'. $type;

            if (!method_exists('Environment', $func_name)) {
                throw new Exception('增加相关对象失败！');
            }

            call_user_func_array(['Environment', $func_name], $args);
        }
        catch (Exception $e) {
            self::echo_error("错误！\t");
            echo $e->getMessage(). "\n";
        }
    }

    //Environment::set('lab', $user, $lab);
    static function set() {
        $args = func_get_args();
        $type = array_shift($args);

         try {
            $func_name = 'set_'. $type;

            if (!method_exists('Environment', $func_name)) {
                throw new Exception('设定相关对象参数失败！');
            }

            call_user_func_array(['Environment', $func_name], $args);
        }
        catch (Exception $e) {
            self::echo_error("错误！\t");
            echo $e->getMessage(). "\n";
        }
    }




    //原env_helper.php中的方法
    static function prepare_lab($prefix) {
		$lab = O('lab');
		$lab->name = '测试实验室'.$prefix;
		$lab->save();
		Unit_Test::assert("建立 $lab->name", $lab->id > 0);
		self::enlist($lab);
		return $lab;
	}
	
	static function prepare_user($prefix, $lab) {
		$user = O('user', ['token'=>'temp_token_'.$prefix]);
		$user->name = '测试用户'.$prefix;
		$user->token = 'temp_token_'.$prefix;
		$user->email = 'temp'.$prefix.'@test.com';
		$user->lab = $lab;
		$user->save();
		Unit_Test::assert("建立 $user->name", $user->id > 0);
		self::enlist($user);
		return $user;
	}
	
	static function prepare_equipment($prefix) {
		$equipment = O('equipment');
		$equipment->name = '测试仪器'.$prefix;
		$equipment->save();
		Unit_Test::assert("建立 $equipment->name", $equipment->id > 0);
		self::enlist($equipment);
		return $equipment;
	}
	
	private static $_objects = [];	
	static function enlist($object) {
		self::$_objects[] = $object;
	}
	
	static function destroy() {
		foreach(self::$_objects as $object) {
			$object->delete();
		}
		self::$_objects = NULL;
	}
	/*
	NO.TASK#228（guoping.zhang@2010.11.15)
	为测试创建财务部门
	*/
	static function prepare_department($suffix) {
		$department = O('billing_department');
		$department->name = '测试财务部门' . $suffix;
		$department->save();
		Unit_Test::assert("建立 $department->name", $department->id > 0);
		self::enlist($department);
		return $department;
	}
	/*
	NO.TASK#228（guoping.zhang@2010.11.15)
	为测试创建收费帐号
	*/
	static function prepare_account($suffix, $department, $lab) {
		$account = O('billing_account');
		$account->name = '测试收费帐号' . $suffix;
		$account->department = $department;
		$account->lab = $lab;
		$account->save();
		Unit_Test::assert("建立 $account->name", $account->id > 0);
		self::enlist($account);
		return $account;
	}
}
