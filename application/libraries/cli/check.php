<?php
class CLI_Check{
	static function sys_ini() {
		ini_set('include_path', '/etc/php5/cgi/php.ini');

		Upgrader::echo_separator();
		Upgrader::echo_title('系统检测信息如下：');


		$upload_size = ini_get('upload_max_filesize');
		$post_size = ini_get('post_max_size');

		$exist_suhosin = extension_loaded('sohosin');

		Upgrader::echo_success('当前系统中upload_max_filesize值为:'.$upload_size);
		Upgrader::echo_success('当前系统中post_max_size值为:'.$post_size);

		Upgrader::echo_separator();
		if ($exist_suhosin) {
			Upgrader::echo_success('当前系统存在suhosin插件');
			$whitelist = ini_get('suhosin.executor.include.whitelist');
			Upgrader::echo_success($whitelist);
		}
		else {
			Upgrader::echo_fail('当前系统不存在suhosin插件');
		}
		Upgrader::echo_separator();
	}

	//根据配置进行错误检查
	static function error_data() {
		$checks = (array) Config::get('check');

		foreach ($checks as $title => $opts) {
			$title = $opts['title'] ? : $title;

			$result = call_user_func($opts['check_callback']);

			//self::noti($title, $result);
			self::error_log_noti($title, $result);
		}
	}

	static private function error_log_noti($title, $content) {
		Log::add(strtr('[CLI] ==== Checking %title ====', [
				'%title' => $title
			]), 'cli');
		Log::add(strtr('[CLI] ==== Get: %content', [
				'%content' => $content
			]), 'cli');
	}

	static private function noti($title, $content) {

		$email = new Email;

		$receivers = Config::get('system.email_address', []);
		$receivers = !is_array($receivers) ? [$receivers] : $receivers;

		foreach ((array)$receivers as $receiver) {
			$email->to($receiver);
			$email->subject($title . date('Ymd'));
			$email->body($content);
			$email->send();
		}

	}

	static function upgrade_data() {
		Upgrader::echo_separator();
		$new_db_name = Config::get('database.prefix') . LAB_ID;
		$old_db_name = Config::get('database.prefix') . 'old_' . LAB_ID;
		fwrite(STDOUT, sprintf("当前检查系统升级后数据库为 %s, 升级前数据库为: %s. \n确定 [Y/y], 取消 [C/c]: ", $new_db_name, $old_db_name));
		$yn = trim(fgets(STDIN));
		if ($yn != 'Y' && $yn != 'y') {
			Upgrader::echo_fail('升级取消!');
			return;
		}

		Upgrader::echo_separator();
		Upgrader::echo_title('1、检测数据库是否齐全...');
		if (!self::connected_db(LAB_ID)->connect_errno) {
			Upgrader::echo_success(sprintf('[%s]数据库连接正常!', $new_db_name));
		}
		else {
			Upgrader::echo_fail(sprintf('[%s]数据库连接失败!请检查数据库后重试!', $new_db_name));
			return;
		}
		if (!self::connected_db('old_' . LAB_ID)->connect_errno) {
			Upgrader::echo_success(sprintf('[%s]数据库连接正常!', $old_db_name));
		}
		else {
			Upgrader::echo_fail(sprintf('[%s]数据库连接失败!请检查数据库后重试!', $old_db_name));
			return;
		}

		$new_db = Database::factory(LAB_ID);
		$old_db = Database::factory('old_'.LAB_ID);


		// 仪器信息数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('2、尝试进行仪器信息数据检查...');
		$no_work_status = EQ_Status_Model::NO_LONGER_IN_SERVICE;
		$new_eq_count = $new_db->value('SELECT COUNT(id) FROM `equipment` WHERE `status` != %s', $no_work_status);
		$old_eq_count = $old_db->value('SELECT COUNT(id) FROM `equipment` WHERE `status` != %s', $no_work_status);
		self::assert('正常设备数量', $new_eq_count, $old_eq_count);
		$new_record_count = $new_db->value('SELECT COUNT(id) FROM `eq_record`');
		$old_record_count = $old_db->value('SELECT COUNT(id) FROM `eq_record`');
		self::assert('所有使用记录总数量', $new_record_count, $old_record_count);


		// 成员信息数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('3、尝试进行成员信息数据检查...');
		$new_auser_count = $new_db->value('SELECT COUNT(id) FROM `user`');
		$old_auser_count = $old_db->value('SELECT COUNT(id) FROM `user`');
		self::assert('所有用户数量', $new_auser_count, $old_auser_count);
		$now = time();
		$new_past_count = $new_db->value('SELECT COUNT(id) FROM `user` WHERE `dto` = 0 OR `dto` > %s', $now);
		$old_past_count = $old_db->value('SELECT COUNT(id) FROM `user` WHERE `dto` = 0 OR `dto` > %s', $now);
		self::assert('过期用户数量', $new_past_count, $old_past_count);
		$new_active_user_count = $new_db->value('SELECT COUNT(id) FROM `user` WHERE `atime` > 0 ');
		$old_active_user_count = $old_db->value('SELECT COUNT(id) FROM `user` WHERE `atime` > 0 ');
		self::assert('激活用户数量', $new_active_user_count, $old_active_user_count);
		$new_unactive_user_count = $new_db->value('SELECT COUNT(id) FROM `user` WHERE `atime` = 0');
		$old_unactive_user_count = $old_db->value('SELECT COUNT(id) FROM `user` WHERE `atime` = 0');
		self::assert('未激活用户数量', $new_unactive_user_count, $old_unactive_user_count);


		// 课题组信息数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('3、尝试进行课题组基本信息数据检查...');
		$new_lab_count = $new_db->value('SELECT COUNT(id) FROM `lab`');
		$old_lab_count = $old_db->value('SELECT COUNT(id) FROM `lab`');
		self::assert('所有实验室数量', $new_lab_count, $old_lab_count);
		$sql = 'SELECT COUNT(`u`.`id`) FROM `user` u LEFT JOIN `lab` l ON `u`.`lab_id` = `l`.`id` AND `u`.`lab_id` != 0';
		$new_lab_us_count = $new_db->value($sql);
		$old_lab_us_count = $new_db->value($sql);
		self::assert('所有实验室下成员数量', $new_lab_us_count, $old_lab_us_count);
		$new_active_lab_count = $new_db->value('SELECT COUNT(id) FROM `lab` WHERE `atime` = 0');
		$old_active_lab_count = $old_db->value('SELECT COUNT(id) FROM `lab` WHERE `atime` = 0');
		self::assert('所有未激活实验室数量', $new_active_lab_count, $old_active_lab_count);
		$sql = 'SELECT COUNT(`u`.`id`) FROM `user` u LEFT JOIN `lab` l ON `u`.`lab_id` = `l`.`id` AND `u`.`lab_id` != 0 WHERE `l`.`atime` = 0';
		$new_active_lab_u_count = $new_db->value($sql);
		$old_active_lab_u_count = $old_db->value($sql);
		self::assert('所有未激活实验室下成员数量', $new_active_lab_u_count, $old_active_lab_u_count);


		// 课题组信息数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('4、尝试进行课题组财务信息数据检查...');
		$labs = $new_db->query('SELECT * FROM `lab`')->rows();
		foreach ($labs as $lab) {
			$dept_count = "SELECT COUNT(`d`.`id`) FROM billing_department d JOIN billing_account a ON `a`.`department_id` = `d`.`id` WHERE `a`.`lab_id` = %s";
			$income_count = "SELECT SUM(`t`.`income`) FROM billing_transaction t JOIN billing_account a On `a`.`id` = `t`.`account_id` WHERE `a`.`lab_id` = %s";
			$outcome_count = "SELECT SUM(`t`.`outcome`) FROM billing_transaction t JOIN billing_account a On `a`.`id` = `t`.`account_id` WHERE `a`.`lab_id` = %s";
			$balance_count = "SELECT SUM(balance) FROM billing_account WHERE `lab_id` = %s";
			Upgrader::echo_title(sprintf("[%s]课题组财务信息数据检查...", $lab->name));
			self::assert("======财务部门数量:", $new_db->value($dept_count, $lab->id), $old_db->value($dept_count, $lab->id));
			self::assert("======财务收入总额:", $new_db->value($income_count, $lab->id), $old_db->value($income_count, $lab->id));
			self::assert("======财务支出总额:", $new_db->value($outcome_count, $lab->id), $old_db->value($outcome_count, $lab->id));
			self::assert("======财务余额:", $new_db->value($balance_count, $lab->id), $old_db->value($balance_count, $lab->id));
		}


		// 权限信息数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('5、尝试进行权限信息数据检查...');
		$new_role_count = $new_db->value('SELECT COUNT(id) FROM `role`');
		$old_role_count = $old_db->value('SELECT COUNT(id) FROM `role`');
		self::assert('已有角色数量:', $new_role_count, $old_role_count);


		// 财务中心数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('6、尝试进行财务信息数据检查...');
		$new_dept_count = $new_db->value('SELECT COUNT(id) FROM `billing_department`');
		$old_dept_count = $old_db->value('SELECT COUNT(id) FROM `billing_department`');
		self::assert('财务部门数量:', $new_dept_count, $old_dept_count);
		$depts = $new_db->query('SELECT * FROM `billing_department`')->rows();
		foreach ($depts as $dep) {
			$income_count = "SELECT SUM(`t`.`income`) FROM billing_transaction t JOIN billing_account a LEFT JOIN billing_department d ON ". 
			"`a`.`department_id` = `d`.`id` AND `t`.`account_id` = `a`.`id` WHERE `d`.`id` = %s";
			$outcome_count = "SELECT SUM(`t`.`outcome`) FROM billing_transaction t JOIN billing_account a LEFT JOIN billing_department d ON ". 
			"`a`.`department_id` = `d`.`id` AND `t`.`account_id` = `a`.`id` WHERE `d`.`id` = %s";
			$account_count = "SELECT COUNT(id) FROM billing_account WHERE department_id = %s";
			$trans_count = "SELECT COUNT(`t`.`id`) FROM billing_transaction t JOIN billing_account a LEFT JOIN billing_department d ON ". 
			"`a`.`department_id` = `d`.`id` AND `t`.`account_id` = `a`.`id` WHERE `d`.`id` = %s";
			Upgrader::echo_title(sprintf("[%s]财务部门信息数据检查...", $dep->name));
			self::assert("======收入金额:", $new_db->value($income_count, $dep->id), $old_db->value($income_count, $dep->id));
			self::assert("======支出总额:", $new_db->value($outcome_count, $dep->id), $old_db->value($outcome_count, $dep->id));
			self::assert("======账号数量:", $new_db->value($account_count, $dep->id), $old_db->value($account_count, $dep->id));
			self::assert("======明细数量:", $new_db->value($trans_count, $dep->id), $old_db->value($trans_count, $dep->id));
		}


		// 成果管理数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('7、尝试进行成果管理信息数据检查...');
		$new_pub_count = $new_db->value('SELECT COUNT(id) FROM publication');
		$old_pub_count = $old_db->value('SELECT COUNT(id) FROM publication');
		self::assert('论文数量: ', $new_pub_count, $old_pub_count);
		$new_award_count = $new_db->value('SELECT COUNT(id) FROM award');
		$old_award_count = $old_db->value('SELECT COUNT(id) FROM award');
		self::assert('获奖数量: ', $new_award_count, $old_award_count);
		$new_patent_count = $new_db->value('SELECT COUNT(id) FROM patent');
		$old_patent_count = $old_db->value('SELECT COUNT(id) FROM patent');
		self::assert('专利数量: ', $new_patent_count, $old_patent_count);


		// 仪器统计数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('8、尝试进行仪器统计信息数据检查...');
		$new_eq_stat_count = $new_db->value('SELECT COUNT(DISTINCT `eq_stat`.`equipment_id`) FROM eq_stat JOIN equipment ON `equipment`.`id` = `eq_stat`.`equipment_id`');
		$old_eq_stat_count = $old_db->value('SELECT COUNT(DISTINCT `eq_stat`.`equipment_id`) FROM eq_stat JOIN equipment ON `equipment`.`id` = `eq_stat`.`equipment_id`');
		self::assert('仪器统计中仪器数量: ', $new_eq_stat_count, $old_eq_stat_count);


		// 门禁数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('9、尝试进行门禁信息数据检查...');
		$new_door_count = $new_db->value('SELECT COUNT(id) FROM door');
		$old_door_count = $old_db->value('SELECT COUNT(id) FROM door');
		self::assert('门禁数量: ', $new_door_count, $old_door_count);
		$new_door_record_count = $new_db->value('SELECT COUNT(id) FROM dc_record');
		$old_door_record_count = $old_db->value('SELECT COUNT(id) FROM dc_record');
		self::assert('门禁进出记录数量: ', $new_door_record_count, $old_door_record_count);


		// 楼宇数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('10、尝试进行楼宇信息数据检查...');
		$new_build_count = $new_db->value('SELECT COUNT(id) FROM gis_building');
		$old_build_count = $old_db->value('SELECT COUNT(id) FROM gis_building');
		self::assert('楼宇信息数据检查: ', $new_build_count, $old_build_count);


		// 环境监控数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('11、尝试进行环境监控数据检查...');
		$new_node_count = $new_db->value('SELECT COUNT(id) FROM env_node');
		$old_node_count = $old_db->value('SELECT COUNT(id) FROM env_node');
		self::assert('监控对象数量: ', $new_node_count, $old_node_count);


		// 视频监控数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('12、尝试进行视频监控数据检查...');
		$new_vidcam_count = $new_db->value('SELECT COUNT(id) FROM vidcam');
		$old_vidcam_count = $old_db->value('SELECT COUNT(id) FROM vidcam');
		self::assert('摄像头数量: ', $new_vidcam_count, $old_vidcam_count);


		// 存货数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('13、尝试进行存货管理数据检查...');
		$new_stock_count = $new_db->value('SELECT COUNT(id) FROM stock');
		$old_stock_count = $old_db->value('SELECT COUNT(id) FROM stock');
		self::assert('存货数量: ', $new_stock_count, $old_stock_count);


		// 订单数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('14、尝试进行订单管理数据检查...');
		$new_order_count = $new_db->value('SELECT COUNT(id) FROM order');
		$old_order_count = $old_db->value('SELECT COUNT(id) FROM order');
		self::assert('订单数量: ', $new_order_count, $old_order_count);


		// 经费数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('15、尝试进行经费管理数据检查...');
		$new_grant_count = $new_db->value('SELECT COUNT(id) FROM `grant`');
		$old_grant_count = $old_db->value('SELECT COUNT(id) FROM `grant`');
		self::assert('经费数量: ', $new_grant_count, $old_grant_count);
		$new_amount_count = $new_db->value('SELECT SUM(amount) FROM `grant`');
		$old_amount_count = $old_db->value('SELECT SUM(amount) FROM `grant`');
		self::assert('经费金额: ', $new_amount_count, $old_amount_count);
		$new_balance_count = $new_db->value('SELECT SUM(balance) FROM `grant`');
		$old_balance_count = $old_db->value('SELECT SUM(balance) FROM `grant`');
		self::assert('经费余额: ', $new_balance_count, $old_balance_count);


		// 项目数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('16、尝试进行项目管理数据检查...');
		$new_project_count = $new_db->value('SELECT COUNT(id) FROM `tn_project`');
		$old_project_count = $old_db->value('SELECT COUNT(id) FROM `tn_project`');
		self::assert('项目总数: ', $new_project_count, $old_project_count);


		// 系统管理数据检查
		Upgrader::echo_separator();
		Upgrader::echo_title('17、尝试进行系统管理组织机构检查...');
		$root = Tag_Model::root('group');
		$eq_root = Tag_Model::root('equipment');

		function check_tag($parent, $new_db, $old_db, $f) {
			$new = $new_db->query('SELECT * FROM tag WHERE id = %s', $parent->id)->rows();
			$old = $old_db->query('SELECT * FROM tag WHERE id = %s', $parent->id)->rows();

			$f(sprintf('%s[%s]ID: ', $parent->name, $parent->id), $new->id, $old->id);
			$f('Name: ', $new->name, $old->name);
			$f('Root: ', $new->root_id, $old->root_id);
			$f('Parent: ', $new->parent_id, $old->parent_id);

			if ($new->id == $old_id && $new->name == $old->name && 
				$new->root_id == $old->root_id && $new->parent_id == $old->parent_id) {
				$tags = $new_db->query('SELECT * FROM tag WHERE parent_id = %s', $parent->id);
				if ($tags) {
					$tags = $tags->rows();
					if (count($tags)) foreach ($tags as $t) {
						check_tag($t, $new_db, $old_db, $f);
					}
				}
				
			}

		}

		$f = function($name, $new, $old) {
			echo "\033[0m";
			echo "ASSERT ($name) ... ";
			if ($new == $old) {
				echo "\033[32m";
				echo "SUCCESS";
				echo "\033[0m";
			}
			else {
				echo "\033[31m";
				echo "FAILED new: $new ~= old: $old ";
				echo "\033[0m";
			}
			echo "\n";
		};

		check_tag($root, $new_db, $old_db, $f);

		Upgrader::echo_separator();
		Upgrader::echo_title('18、尝试进行系统管理仪器分类检查...');
		check_tag($eq_root, $new_db, $old_db, $f);

        if (Module::is_installed('vidmon')) {
            // 仪器统计数据检查
            Upgrader::echo_separator();
            Upgrader::echo_title('19、尝试进行视频监存储文件目录检查...');
            $path = Config::get('vidmon.capture_path');
            if (File::exists($path)) {
                echo "\033[32m";
                echo "SUCCESS";
                echo "\033[0m";
            }
            else {
                echo "\033[0m";
                echo "\033[31m";
                echo "FAILED";
                echo "\033[0m";
            }
            echo "\n";
        }

        if (Module::is_installed('eq_charge')) {
            // 仪器统计数据检查
            Upgrader::echo_separator();
            Upgrader::echo_title('20、尝试收费配置存储储文件目录检查...');
            $path = LAB_PATH. PRIVATE_BASE. 'equipments';
            if (File::exists($path)) {
                echo "\033[32m";
                echo "SUCCESS";
                echo "\033[0m";
            }
            else {
                echo "\033[0m";
                echo "\033[31m";
                echo "FAILED";
                echo "\033[0m";
            }
            echo "\n";
        }
	}

	static private function connected_db($name) {
		$info = [];
		$url = Config::get('database.'.$name.'.url');
		if (!$url) {
			$dbname = Config::get('database.'.$name.'.db');
			if (!$dbname) $dbname = Config::get('database.prefix') . $name;
			$url = strtr(Config::get('database.root'), ['%database' => $dbname]);
		}

		$url = parse_url($url);
		$info['handler'] = $url['scheme'];	
		$info['host']= urldecode($url['host']);
		$info['port'] = (int)$url['port'];
		$info['db'] = substr(urldecode($url['path']), 1);
		$info['user'] = urldecode($url['user']);
		$info['password']  = isset($url['pass']) ? urldecode($url['pass']) : NULL;

		return new mysqli(
			$info['host'], 
			$info['user'], $info['password'],
			$info['db'],
			$info['port']
		);
	}

	static private function assert($name, $new, $old) {
		echo "\033[0m";
		echo "ASSERT ($name) ... ";
		if ($new == $old) {
			echo "\033[32m";
			echo "SUCCESS";
			echo "\033[0m";
		}
		else {
			echo "\033[31m";
			echo "FAILED new: $new ~= old: $old ";
			echo "\033[0m";
		}

		echo "\n";
	}
}
