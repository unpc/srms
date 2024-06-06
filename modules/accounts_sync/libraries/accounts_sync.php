<?php
class Accounts_Sync {

	// hook
	static function setup_account($e) {
		Event::bind('account.edit.tab', 'Accounts_Sync::account_edit_sync_tab', 100);
	}

	// 设置标签页
	static function account_edit_sync_tab($e, $tabs) {
		$account = $tabs->account;
		$me = L('ME');

		Event::bind('account.edit.content', 'Accounts_Sync::account_edit_sync_content', 0, 'sync');
		$tabs->add_tab('sync', [
						   'url' => $account->url('sync', NULL, NULL, 'edit'),
						   'title' => I18N::HT('accounts_sync', '站点'),
						   'weight' => 100,
						   ]);
	}

	// 设置内容
	static function account_edit_sync_content($e, $tabs) {
		$account = $tabs->account;

		if (!$account->mod_enable) {
			$account->mod_enable = array_keys(Config::get('lab.modules_default'));
			$account->save();
		}

        $can_sync = TRUE;

        if (!$account->lab_name || !$account->code_id) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts_sync', '请完善该站点基本信息后再开通站点!'));
            $can_sync = FALSE;
        }

		$tabs->content = V('accounts_sync:site/sync', [
							   'account' => $account,
							   'site' => $site,
                               'can_sync'=> $can_sync,
							   ]);
	}

	// 获得当前状态下的链接
	static function get_sync_links($account, $mode = 'index') {

		$links = [];

		/*
		  if 开通
		    显示 关闭 按钮
		  else
		    显示 开通 按钮
			if 有备份
			  点开通后提示还原
			end
		  end
		  (xiaopei.li@2012-01-19)
		*/
		switch($mode) {
		case 'edit':
			if (self::site_is_ready($account) && self::site_is_open($account)) {
				$links['close'] = [
					'url' => '#',
					'text'  => I18N::T('accounts_sync', '关闭'),
					'extra' =>
					'q-object="close" '.
					'q-event="click" '.
					'q-src="'.URI::url('!accounts_sync/lims_site').'" '.
					'q-static="'.H(['id'=>$account->id]).'" '.
					'class="font-button-delete"'
					];
			}
			else {
				if ($account->status == LIMS_Account_Model::STATUS_NORMAL) {
					$links['open'] = [
						'url' => '#',
						'text'  => I18N::T('accounts_sync', '开通'),
						'extra' =>
						'q-object="open" '.
						'q-event="click" '.
						'q-src="'.URI::url('!accounts_sync/lims_site').'" '.
						'q-static="'.H(['id'=>$account->id]).'" '.
						'class="button button_refresh"'
					];
				}
				else {
					$links['cannot_open'] = [
						'text' => '非正常状态下, 站点不能开通',
					];
				}
			}
			break;
		case 'index':
		default:
		}

		return $links;
	}

	// 暂时关闭
	static function site_close($account) {
		if (self::disable_account($account)) {
			$comment_log = O('comment');
			$comment_log->author = L('ME');
			$comment_log->object = $account;
			$comment_log->content = I18N::T('accounts', '暂时关闭站点');
			$comment_log->save();

			$ret = I18N::T('accounts_sync', '站点已暂时关闭');
		}
		else {
			$ret = I18N::T('accounts_sync', '站点关闭失败');
		}

		return $ret;
	}

	//
	static function get_account_disable_file($account) {
		return ROOT_PATH . 'sites/lab/labs/' . $account->code_id . '/disable'; // TODO(xiaopei.li@2012-02-04)
		// return $account->lab_path . '/disable';
	}

	static function disable_account($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);

		$content = '';

		$cmd = 'php '.ROOT_PATH.'cli/cli.php lab run ' .
			self::lab_cmd_opts($account) . ' close 2>&1';

		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content .= I18N::T('accounts_sync', '缺少 close 脚本');
		}

		return $content; // TODO t/f
	}

	static function enable_account($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);
		$content = '';

		$cmd = 'php '.ROOT_PATH.'cli/cli.php lab run '. self::lab_cmd_opts($account) . ' open 2>&1';

		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content .= I18N::T('accounts_sync', '缺少 open 脚本');
		}

		return $content; // TODO t/f
	}

	static function lab_cmd_opts($account) {
		$types = LIMS_Account_Model::get_available_types();
		$site_id = $types[$account->type]['site_id'];
		$code_id = $account->code_id;
		$lab_name = $account->lab_name ? : 'Genee Lab';
		$url = $account->url ? : '/';
        $account_id = $account->id;
		$cmd = "{$site_id} {$code_id} '{$lab_name}' '{$url}'";
		return $cmd;
	}

	static function backup_account($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);
		$content = '';

		$cmd = 'php ' . ROOT_PATH . 'cli/cli.php lab run ' . self::lab_cmd_opts($account) . ' backup 2>&1';

		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content .= I18N::T('accounts_sync', '缺少 backup 脚本');
		}

		return $content;
	}

	// 删除站点
	static function delete_account($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);
		$content = '';

		$cmd = 'php '.ROOT_PATH.'cli/cli.php lab run '. self::lab_cmd_opts($account) . ' delete 2>&1';

		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content .= I18N::T('accounts_sync', '缺少 delete 脚本');
		}

		return $content;
	}

	static function init_account($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);
		$content = '';
		$types = LIMS_Account_Model::get_available_types();

		$cmd = 'php '.ROOT_PATH.'cli/cli.php lab run ' . self::lab_cmd_opts($account) .' create '.$account->id.' 2>&1';

		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);

			$comment_log = O('comment');
			$comment_log->author = L('ME');
			$comment_log->object = $account;
			$comment_log->content = I18N::T('accounts', '初始化站点');
			$comment_log->save();
		}
		else {
			$content .= I18N::T('accounts_sync', '缺少 open 脚本');
		}

		return $content;
	}

	static function site_open($account) {

		$ret = FALSE;

		if (self::site_is_ready($account)) {
			if (self::site_is_open($account)) {
				$ret = TRUE;
			}
			else {
				$ret = self::enable_account($account);

				if ($ret) {
					$comment_log = O('comment');
					$comment_log->author = L('ME');
					$comment_log->object = $account;
					$comment_log->content = I18N::T('accounts', '重新开通站点');
					$comment_log->save();
				}
			}
		}
		else {
			$ret = self::init_account($account);
		}

		return $ret;
	}

    static function lims_account_before_save($e, $account, $new_data) {

		if (!(isset($new_data['url']) && $new_data['url'])) {
			// 如果未设置 url, 则按配置生成默认 url

			$network_url = Config::get('accounts_sync.network_url');

			$lab_id = $new_data['code_id'] ? : $account->code_id;

			$account->url =  $network_url . $lab_id . '/';
		}

		if (isset($new_data['status']) &&
			$new_data['status'] == LIMS_Account_Model::STATUS_DELETED) {
			self::backup_account($account);
			self::delete_account($account);
		}

	}

	/*
	  检查站点是否**完备**
	  需要检查:
	  - 目录
	  - 数据库
	  - 如果有pages模块，还需检查wp
	  (xiaopei.li@2011.09.02)
	*/
	static function site_is_ready($account) {
		$root_path = ROOT_PATH;
		$lab_path = "{$root_path}/sites/lab/labs/{$account->code_id}";

		$path_ok = File::exists($lab_path);

		$db = Database::factory();
		$sql = strtr("SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE `SCHEMA_NAME` like 'lims2_%lab_id';",
					 ['%lab_id' => $account->code_id]); 		// TODO 暂时的mysql解决方案，建议在 database 中增加 database_exists 方法，或是在实例化 database 失败时，抛出错误，而不是直接die

		$db_ok = $db->value($sql);

		return ($path_ok && $db_ok);
	}

	/*
	  检查站点是否**开通**
	  需要检查:
	  - 目录下的disable文件
	  (xiaopei.li@2011.10.23)
	*/
	static function site_is_open($account) {
		return !File::exists(self::get_account_disable_file($account));
	}

	static function hooked_site_is_open($e, $account) {
		$ret = self::site_is_ready($account) && self::site_is_open($account);
		$e->return_value = $ret;
	}

	static function site_is_backedup($account) {
		$base_backup_dir = Config::get('accounts.backup.dir');
		$lab_backup_dir = $base_backup_dir . '/' . $account->name;

		return (File::exists($lab_backup_dir));
	}

	static function scan_labs_only_in_dir($site_id) {
		// 扫描目录中有的 lab
		$labs_only_in_dir = self::scan_labs_in_dir($site_id);

		// ...排除保留的lab_id, 如 admin, backup 等
		$reserved_lab_id = Lab::get('accounts_sync.reserved_lab_id');

		// ...排除自己的 lab_id
		$reserved_lab_id[] = LAB_ID;

		$labs_only_in_dir = array_diff($labs_only_in_dir, $reserved_lab_id);

		foreach ($labs_only_in_dir as $i => $lab_id) {
			$account = O('lims_account', ['code_id' => $lab_id]);

			if ($account->id) {
				// ...如果 db 中无此记录, 则需要提示
				unset($labs_only_in_dir[$i]);
			}
		}

		return $labs_only_in_dir;
	}

	static function sync_labs_to_db($site_id, $labs, &$labs_sync = [], &$labs_failed = []) {
		include(ROOT_PATH . "/sites/{$site_id}/config/lab.php");

		foreach ($labs as $lab_id) {

			$account = O('lims_account');
			$account->code_id = $lab_id;
			$account->lab_name = $lab_id;

			$lab_config_file = ROOT_PATH . "/sites/{$site_id}/labs/{$lab_id}/config/lab.php";
			if (include($lab_config_file)) {
				$lab_name = $config['name'] ? : $lab_id;
				unset($config['name']);

				$account->lab_name = $lab_name;
			}

			if ($account->save()) {
				$labs_sync[] = $lab_id;
			}
			else {
				$labs_failed[] = $lab_id;
			}
		}
	}

	static function alarm_only_in_dir_labs($e) {

		// TODO 以后可加入多站点类型管理(xiaopei.li@2012-01-07)
		// $site_ids = Lab::get('accounts_sync.admin_type');


		$site_id = 'lab';

		$labs_only_in_dir = self::scan_labs_only_in_dir($site_id);

		if (count($labs_only_in_dir)) {
			$message = I18N::T('accounts_sync',
							   '系统目录中存在下列客户未列入客户列表, 请检查: %labs_only_in_dir. <a href="%fix_url">点我尝试修复</a>',
							   ['%labs_only_in_dir' => join(', ', $labs_only_in_dir),
									 '%fix_url' => URI::url('!accounts_sync/lims_site/dir_to_db'),
								   ]);

			$e->return_value = $message;
		}
	}

	static function scan_labs_in_dir($site_id) {
		$root_path = ROOT_PATH;

		$labs_path = "{$root_path}/sites/{$site_id}/labs";
		$labs = [];

		if ($handle = opendir($labs_path)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && is_dir("{$labs_path}/{$file}")) {
					$labs[] = $file;
				}
			}
		}

		return $labs;
	}

    static function lims_account_saved($e, $account, $old_data, $new_data) {
        //如果属于新添加站点, 则进行逻辑判断
        if (
                $new_data['id'] && !$old_data['id']
                ||
                ($new_data['status'] == LIMS_Account_Model::STATUS_NORMAL && $old_data['status'] != LIMS_Account_Model::STATUS_NORMAL)
           ) {
            $content = Accounts_Sync::site_open($account);
            Lab::message(Lab::MESSAGE_NORMAL, '<pre>' . $content . '</pre>');
        }
        else {
            //更新
            if (
                $new_data['modules'] != $old_data['modules']
               ) {
                Accounts_Sync::update_lab_config($account);
            }

            if (
                $new_data['url'] != $old_data['url']
                ||
                $new_data['language'] != $old_data['language']
                ||
                $new_data['timezone'] != $old_data['timezone']
                ) {
                Accounts_Sync::update_system_config($account);
            }

            if ($new_data['status'] == LIMS_Account_Model::STATUS_DELETED) {
                self::delete_account($account);
            }
        }
    }

    static function update_lab_config($account) {
        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);
        putenv('LAB_ID='.LAB_ID);
        $content = '';

        $cmd = 'php '.ROOT_PATH.'cli/cli.php lab update_lab_config '. $account->id.' 2>&1';

        $ph = popen($cmd, 'r');
        if ($ph) {
            while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
            pclose($ph);

            $comment_log = O('comment');
            $comment_log->author = L('ME');
            $comment_log->object = $account;
            $comment_log->content = I18N::T('accounts', '修改站点模块等信息');
            $comment_log->save();
        }
        else {
            $content .= I18N::T('accounts_sync', '缺少更新脚本');
        }

        return $content;
    }

    static function update_system_config($account) {
        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);
        putenv('LAB_ID='.LAB_ID);
        $content = '';

        $cmd = 'php '.ROOT_PATH.'cli/cli.php lab update_system_config '. $account->id.' 2>&1';

        $ph = popen($cmd, 'r');
        if ($ph) {
            while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
            pclose($ph);

            $comment_log = O('comment');
            $comment_log->author = L('ME');
            $comment_log->object = $account;
            $comment_log->content = I18N::T('accounts', '修改站点语言、时区、金额等信息');
            $comment_log->save();
        }
        else {
            $content .= I18N::T('accounts_sync', '缺少更新脚本');
        }

        return $content;
    }

	/*
	static function site_sync($account) {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.SITE_ID);
		putenv('LAB_ID='.LAB_ID);
		$account = O('lims_account', $account_id);
    	if (!$account->id) $account = NULL;
		$ph = popen('php '.ROOT_PATH.'cli/cli.php lab '.self::lab_cmd_opts($account).' create 2>&1', 'r');
		if ($ph) {
			$content = '';
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content = I18N::T('accounts_sync', '找不到 setup 脚本');
		}

		// 关于开通模块更好更直观的做法应该是每个模块有个开关,切换后(保存时)自动备份同步
		// $account->mod_changed = FALSE;
		// $account->save();

		return $content;
	}
	*/
}
