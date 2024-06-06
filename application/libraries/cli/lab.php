<?php
class CLI_Lab extends CLI_Frame{

	static function __index() {
		echo "Available commands:\n";
		echo "  is_valid_lab\n";
		echo "  close_expire_lab\n";
		echo "  generate_etc\n";
		echo "  run site lab name url [create|backup|delete|open|close]\n";
	}

	//检测lab是否合法
	static function is_valid_lab() {
		if (Q('user')->total_count() > 0) {
			echo 'SITE_ID=' . SITE_ID . ' ' . 'LAB_ID=' . LAB_ID . "\n";
		}
	}

	static function close_expire_lab() {
		/*
			关闭当前已过期的实验室
		*/

		$time = Date::get_day_start();
		$query = "lims_account[etime>0][etime<{$time}]";

		$expired_accounts = Q($query);
		$just_closed = [];

		foreach ($expired_accounts as $ea) {
			if (Accounts_Sync::site_is_ready($ea) && Accounts_Sync::site_is_open($ea)) {
				$content = Accounts_Sync::site_close($ea);
				$just_closed[] = $ea->lab_name;
			}
		}

		if ($just_closed) {

			$email = new Email;
			$recipients = Config::get('accounts_sync.observer_emails');

			$email->to($recipients);
			$email->subject(HT('LIMS2 试用站点关闭通知'));

			$body = HT("以下客户由于试用过期, 已关闭: %accounts", [
						   '%accounts' => join(', ', $just_closed)
						   ]);

			$email->body($body);

            $email->send();
		}

        //获取将来过期的账号
        $to_expire_accounts = [];

        $status_normal = Lims_Account_Model::STATUS_NORMAL;
        //仅对状态为正常的进行修正
        foreach(Q("lims_account[etime>{$time}][status={$status_normal}]") as $a) {
            //获取过期时间对应的那一天的0点, 0点表示过期
            $etime = Date::get_day_start($a->etime);
            $nday = $a->nday;
            if ($today + ($nday -1) * 86400 < $etime && $today + $nday * 86400 >= $etime) {
                $to_expire_accounts[] = $a;
            }
        }

        if (count($to_expire_accounts)) {

            $email = new Email;
            $recipients = Config::get('accounts_sync.observer_emails');

            $email->to($recipients);
            $email->subject(T('LIMS2 试用站点即将到期关闭!'));

            $body = T('<p>以下客户试用站点即将到期关闭: </p>');

            foreach($to_expire_accounts as $a) {
                $body .= T("<p>%accounts 过期时间 %time, 请及时处理!</p>", [
                    '%accounts' => URI::anchor($a->url, $a->lab_name),
                    '%time'=> Date::format($a->etime, 'Y/m/d'),
                ]);
            }

            $email->body(null, $body);

            $email->send();
        }
	}

	static function run($site=null, $lab=null, $name=null, $url=null, $action=null, $account_id=null) {
		if (!$site || !$lab || ! $name || !$url || !$action) {
			die("Usage:\nphp cli.php lab lab demo 基理实验室 'http://g.labscout.cn/demo/' [close,backup,delete,open,create] \n");
		}

		$lab = new CLI_Lab($site, $lab, $name, $url, $account_id);

		switch ($action) {
		case 'close':
			$lab->close();
			break;
		case 'backup':
			$lab->backup();
			break;
		case 'delete':
			$lab->delete();
			break;
		case 'open':
			$lab->open();
			break;
		case 'create':
			$lab->create();
			break;
		default:
		}
	}

	private $account;
	private $lab_path;

	private $db_backup;
	private $dir_backup;

    private $modules = [];

	function __construct($site_id, $lab_id, $lab_name, $url, $account_id=null) {
		/*
		$account = O('lims_account', $account_id);

		if (!$account->id) {
			$this->fatal_error('account 不存在');
		}
		else if (!$account->code_id ||
				 $account->code_id == 'admin' ||
				 $account->code_id == 'backup') {
			$this->fatal_error('account 的 code_id 不合法');
		}

		$this->account = $account;
		*/
		if (!$lab_id ||
			$lab_id == 'admin' ||
			$lab_id == 'backup') {

			$this->fatal_error('account 的 code_id 不合法');
		}

		if($account_id){
			$account = O('lims_account', $account_id);
			$this->account = $account;
		}

		$this->site_id = $site_id;
		$this->code_id = $lab_id;
		$this->lab_name = $lab_name;
		$this->url = $url;

        $this->modules = $account->modules;

		$this->lab_path = ROOT_PATH . '/sites/'. $this->site_id . '/labs/' . $lab_id; // TODO lab_path 需考虑站点类型!!(xiaopei.li@2012-02-04)
	}

	function destroy() {
		// backup
		// destroy
	}

	function backup() {
		// TODO 把 backup dir 换为Config::get('accounts.backup.dir') 获得

		$suffix = date('ymdHi', time());

		$this->backup_db($suffix);
		$this->backup_dir($suffix);
		$this->show_message('备份成功!');
	}

	function create() {
		$this->prepare_dir();

		$this->init_config();

		$this->init_db();

		$this->init_user();

		$this->create_orm_tables();

		$this->add_to_proj_list();

		$this->show_message('站点已开通! 但还需 10 分钟左右新站点的服务(如全文索引,发信)才会正常. 10 分钟后若服务还不正常, 请联系运维!');
	}

	function do_close() {
		$this->touch_disable_file();
        $this->delete_from_proj_list();
		$this->show_message('关闭成功!');
	}

	function undo_close() {
		$this->delete_disable_file();
        $this->add_to_proj_list();
		$this->show_message('关闭失败!');
	}

	function do_open() {
		$this->delete_disable_file();
		$this->show_message('开通成功!');
	}

	function undo_open() {
		$this->touch_disable_file();
		$this->show_message('开通失败!');
	}

	function touch_disable_file() {
		return touch($this->lab_path . DIRECTORY_SEPARATOR . 'disable');
	}

	function delete_disable_file() {
		return File::delete($this->lab_path . DIRECTORY_SEPARATOR . 'disable');
	}

	function delete() {
		$this->delete_database();
		$this->delete_dir();
	}

	function do_delete_database() {
		// $this->backup_db('before_delete');
		$db = Database::factory();
		$db->query(strtr('drop database if exists lims2_%lab_id;', ['%lab_id' => $this->code_id]));
	}

	function do_delete_dir() {
		// $this->backup_dir('before_delete');
		$ret = File::rmdir($this->lab_path);
	}

	function create_orm_tables() {
		putenv('Q_ROOT_PATH='.ROOT_PATH);
		putenv('SITE_ID='.$this->site_id);
		putenv('LAB_ID='.$this->code_id);
		$content = '';

		$cmd = 'php ' . ROOT_PATH . 'cli/create_orm_tables.php 2>&1';
		$ph = popen($cmd, 'r');
		if ($ph) {
			while (FALSE !== ($output = fgets($ph, 2096))) $content.=$output;
			pclose($ph);
		}
		else {
			$content .= '缺少 create_orm_tables 脚本';
		}
	}

	function add_to_proj_list() {

		$proj_list = '/etc/lims2/proj_list';
		$this_proj = "{$this->site_id}\t{$this->code_id}";

		$grep_cmd = "grep -qs '^$this_proj$' $proj_list";
		exec($grep_cmd, $foo, $return_var);

		if ($return_var !== 0) {
			file_put_contents($proj_list, "\n" . $this_proj, FILE_APPEND);
		}
	}

    function delete_from_proj_list() {
        $proj_list = '/etc/lims2/proj_list';
        $this_proj = "{$this->site_id}\t{$this->code_id}";

        $command = "sed '/$this_proj/d' $proj_list";

        //sed 执行后不会标准输出, ob 进行获取
        ob_start();

        //执行
        system($command);

        $content = ob_get_contents();

        ob_end_clean();

        file_put_contents($proj_list, $content);
    }

	// 该方法需以 root 运行
	static function generate_etc() {


		$proj_list_file = '/etc/lims2/proj_list';
		$proj_list_stat = stat($proj_list_file);

		$touch_file = '/etc/lims2/proj_check';
		$touch_file_stat = stat($touch_file);

		// http://www.php.net/stat
		// 9 是 mtime, 如果 proj_list 在 上次检查后未改动, 则退出
		if ($proj_list_stat && $touch_file_stat && $proj_list_stat[9] < $touch_file_stat[9]) {
			return;
		}

		$root_path = ROOT_PATH;

		//generate 后进行一次更新
        $fix_php = "$root_path/cli/fix_lims_proj_list.php";
        $fix_lims_proj_list_cmd = "SITE_ID=lab LAB_ID=admin php Q_ROOT_PATH=$root_path php $fix_php";
        @exec($fix_lims_proj_list_cmd);

		$proj_list = file_get_contents($proj_list_file);
		$projs = explode("\n", $proj_list);
		

		$daemon_conf = '/etc/lims2/daemon.conf';
		file_put_contents($daemon_conf, '');

		$cron_conf = '/etc/cron.d/lims2';
		// cron 配置只有 root 可写
		file_put_contents($cron_conf, '');

		$sphinx_conf = '/etc/sphinxsearch/conf.d/lims2.conf';
		file_put_contents($sphinx_conf, '');

		foreach ($projs as $proj) {
			if (!$proj) continue;

			list($proj_site_id, $proj_lab_id) = explode("\t", $proj);

			// 若该处有修改, 应同步修改打包时生成配置的 deploy/debian_package.php
			// (xiaopei.li@2013-06-21)

			// daemon
			$daemon_php = "$root_path/cli/cli.php daemon get_config /usr/share/lims2/";
			$daemon_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $daemon_php >> $daemon_conf";
			echo "$daemon_cmd\n";
			exec($daemon_cmd);

			// cron
			$cron_php = "$root_path/cli/cli.php cron get_config www-data /usr/share/lims2/";
			$cron_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $cron_php >> $cron_conf";
			echo "$cron_cmd\n";
			exec($cron_cmd);

			// sphinx
			$sphinx_php = "$root_path/cli/cli.php sphinx get_config";
			$sphinx_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $sphinx_php >> $sphinx_conf";
			echo "$sphinx_cmd\n";
			exec($sphinx_cmd);
		}

		exec('service lims2_daemon restart');
		exec('service sphinxsearch reload');

		touch($touch_file);

    }

	function backup_dir($suffix) {
		$labs_base_path = $this->_check_labs_base_path();
		$backup_base_path = $labs_base_path . DIRECTORY_SEPARATOR . 'backup';
		$backup_path = $backup_base_path . DIRECTORY_SEPARATOR . $this->code_id . DIRECTORY_SEPARATOR . $suffix . DIRECTORY_SEPARATOR . 'file';

		File::check_path($backup_path . 'foo');

		if (File::exists($this->lab_path)) {
			if (is_readable($this->lab_path)) {
				File::copy_r($this->lab_path, $backup_path);
				$this->dir_backup = $backup_path;
			}
			else {
				$this->warning_error(sprintf('%s 不可读', $this->lab_path));
			}
		}
		else {
			$this->warning_error(sprintf('%s 不存在', $this->lab_path));
		}
	}

	function backup_db($suffix) {

		$labs_base_path = $this->_check_labs_base_path();
		$backup_base_path = $labs_base_path . DIRECTORY_SEPARATOR . 'backup';
		$backup_path = $backup_base_path . DIRECTORY_SEPARATOR . $this->code_id . DIRECTORY_SEPARATOR . $suffix;

		File::check_path($backup_path . DIRECTORY_SEPARATOR . 'foo');
		$dbfile = $backup_path . DIRECTORY_SEPARATOR . 'db.sql';

		$db_name = 'lims2_' . $this->code_id;
		$db_dump = "mysqldump -u genee $db_name > $dbfile";
		exec($db_dump);
	}

	function _check_labs_base_path() {
		$q_root_path = $_SERVER['Q_ROOT_PATH'];
		$labs_base_path = $q_root_path . join(DIRECTORY_SEPARATOR, ['sites', $this->site_id, 'labs']);

		if (!is_readable($labs_base_path)) {
			$this->fatal_error("对{$labs_base_path}缺少读权限");
		}
		if (!is_writable($labs_base_path)) {
			$this->fatal_error("对{$labs_base_path}缺少写权限");
		}

		return $labs_base_path;
	}


	function do_prepare_dir() {

		$labs_base_path = $this->_check_labs_base_path();

		$lab_path = $labs_base_path . DIRECTORY_SEPARATOR . $this->code_id;

		$this->show_message($lab_path);

		@mkdir($lab_path);

		/*
		if (!File::check_path($lab_path)) {
			$this->fatal_error("创建{$lab_path}目录失败");
		}
		*/

		$this->lab_path = $lab_path;
	}

	function undo_prepare_dir() {
		if (is_dir($this->lab_path)) {
			File::rmdir($this->lab_path);
		}
	}

	function do_init_config() {
		// TODO 建立各模块的特定配置(xiaopei.li@2011.09.22)

		$lab_path = $this->lab_path;

		if (!is_writable($lab_path)) {
			$this->fatal_error("{$lab_path}课题组目录缺少写权限");
		}

		$config_path = $lab_path . DIRECTORY_SEPARATOR . 'config/';

		@mkdir($config_path);
		/*
		if (!File::check_path($config_path)) {
			$this->fatal_error('配置目录创建失败');
		}
		*/

		$lab_config_file = $config_path . 'lab.php';
		$lab_config_content = "<?php
\$config['currency_sign'] = '%currency_sign';
\$config['name'] = '%lab_name';
";

        if (count($this->modules)) {
            $accounts_modules = Config::get('accounts.modules');
            $_m = [];
            foreach($this->modules as $name) {
                //如果该模块开通包含多个模块
                //则均开通
                if ($accounts_modules[$name]['modules']) {
                    foreach($accounts_modules[$name]['modules'] as $m) {
                        $_m[$m] = TRUE;
                    }
                }
                else {
                    $_m[$name] = TRUE;
                }
            }

            ob_start();
            var_export($_m);
            $lab_modules = ob_get_contents();
            ob_end_clean();

            $lab_config_content .= "\n". '$config[\'modules\'] = ' . $lab_modules . ';';
        }

        switch($this->account->currency) {
            case 'RMB' :
                $currency_sign = '￥';
                break;
            case 'dollar' :
                $currency_sign = '$';
                break;
        }

		$lab_config_content = strtr($lab_config_content, [
										'%currency_sign'=> $currency_sign,
										'%lab_name' => $this->lab_name,
										]);
		file_put_contents($lab_config_file, $lab_config_content);

		// 增加 equipment config key (xiaopei.li@2012-02-04)
		$eq_config_file = $config_path . 'equipment.php';
		$eq_config_content = "<?php
\$config['private_key'] = '
%key
';
";

		$key = shell_exec('openssl genrsa 2048 2>/dev/null'); // shell_exec return the complete output as a string
		$eq_config_content = strtr($eq_config_content, [
							   '%key' => $key,
							   ]);
		file_put_contents($eq_config_file, $eq_config_content);


		$system_config_file = $config_path . 'system.php';
		$system_config_content = "<?php
\$config['locale'] = '%language';
\$config['timezone'] = '%timezone';
if (defined('CLI_MODE')) {
    \$config['base_url']  = \$config['script_url'] = '%url';
}
";

		$system_config_content = strtr($system_config_content, [
							   '%language'=>$this->account->language,
							   '%timezone'=>$this->account->timezone,
							   '%url' => $this->url,
							   ]);
		file_put_contents($system_config_file, $system_config_content);


	}

	function do_init_db() {
		$db = Database::factory();

		$SQL_TEMPLATES['create_database'] = "
CREATE DATABASE IF NOT EXISTS `lims2_%lab_id` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";

		$SQL = strtr($SQL_TEMPLATES['create_database'],
					 ['%lab_id' => $this->code_id]);

		// $this->show_message($SQL);
		$db->query($SQL);
	}

	function undo_init_db() {
		$db = Database::factory();
		$SQL_TEMPLATES['drop_database'] = "
DROP DATABASE `lims2_%lab_id`;
";
		$SQL = strtr($SQL_TEMPLATES['drop_database'], ['%lab_id' => $this->code_id]);
		// $this->show_message($SQL);
		$db->query($SQL);
	}

	function do_init_user() {
		$db = Database::factory($this->code_id);

		/* auth */
		$auth_schema = [
			'fields' => [
				'token'=>['type'=>'varchar(80)', 'null'=>FALSE, 'default'=>''],
				'password'=>['type'=>'varchar(100)', 'null'=>FALSE, 'default'=>''],
				],
			'indexes' => [
				'primary'=>['type'=>'primary', 'fields'=>['token']],
				]
			];

		class_exists('Auth');

		if ($db->prepare_table('_auth', $auth_schema)) {

			$SQL_TEMPLATES['insert_auth'] = "
INSERT INTO `_auth`
(`token`, `password`)
values
('%token', '%md5_password');
";

			// TODO add users(xiaopei.li@2012-01-18)
			// add genee
			$query = strtr($SQL_TEMPLATES['insert_auth'], [
								 '%token' => 'genee',
								 '%md5_password' => md5('83719730'),
							   ]);
			// error_log($query);
			$db->query($query);
			/*
			// add pi
			$db->query(strtr($SQL_TEMPLATES['insert_auth'], array(
								 '%token' => $this->pi_id,
								 '%md5_password' => md5('123456'),
								 )));
			*/
		}

		/* user */
		$schema = ORM_Model::schema('user');
		if ($schema) {
			if ($db->prepare_table('user', $schema)) {
				$SQL_TEMPLATES['insert_user'] = "
INSERT INTO `user`
(`token`, `email`, `name`, `atime`, `hidden`)
VALUES
('%token', '%email', '%name', 1, '%is_hidden');
";

				/* add genee */
				$genee_name = Config::get('accounts_sync.genee_name');
				$query = strtr($SQL_TEMPLATES['insert_user'], [
								   '%token' => 'genee|database',
								   '%email' => 'support@geneegroup.com',
								   '%name' => $genee_name,
								   '%is_hidden' => 1,
								   ]);

				$db->query($query);
			}
		}
	}

    static function update_lab_config($account_id = 0) {
        if ($account_id) {
            $account = O('lims_account', $account_id);

            $lab_id = $account->code_id;

            $modules = $account->modules;

            $lab_path = ROOT_PATH . '/sites/lab/labs/' . $lab_id;

            $config_path = $lab_path . DIRECTORY_SEPARATOR . 'config/';

            @mkdir($config_path);

            $lab_config_file = $config_path . 'lab.php';
            $lab_config_content = "<?php
\$config['currency_sign'] = '%currency_sign';
\$config['name'] = '%lab_name';
";

            if (count($modules)) {
                $accounts_modules = Config::get('accounts.modules');
                $_m = [];
                foreach($modules as $name) {
                    //如果该模块开通包含多个模块
                    //则均开通
                    if ($accounts_modules[$name]['modules']) {
                        foreach($accounts_modules[$name]['modules'] as $m) {
                            $_m[$m] = TRUE;
                        }
                    }
                    else {
                        $_m[$name] = TRUE;
                    }
                }

                ob_start();
                var_export($_m);
                $lab_modules = ob_get_contents();
                ob_end_clean();

                $lab_config_content .= "\n". '$config[\'modules\'] = ' . $lab_modules . ';';
            }

            switch($account->currency) {
                case 'RMB' :
                    $currency_sign = '￥';
                    break;
                case 'dollar' :
                    $currency_sign = '$';
                    break;
            }

            $lab_config_content = strtr($lab_config_content, [
                '%currency_sign'=> $currency_sign,
                '%lab_name' => $account->lab_name,
            ]);

            file_put_contents($lab_config_file, $lab_config_content);

            putenv('Q_ROOT_PATH='.ROOT_PATH);
            putenv('SITE_ID=lab');
            putenv('LAB_ID='.$account->code_id);

            $cmd = "php " . ROOT_PATH . 'cli/create_orm_tables.php 2>&1';
            $ph = popen($cmd, 'r');
            if ($ph) {
                pclose($ph);
            }
        }
    }

    static function update_system_config($account_id = 0) {
        if ($account_id) {
            $account = O('lims_account', $account_id);

            $lab_id = $account->code_id;

            $lab_path = ROOT_PATH . '/sites/lab/labs/' . $lab_id;

            $config_path = $lab_path . DIRECTORY_SEPARATOR . 'config/';

            @mkdir($config_path);

            $system_config_file = $config_path . 'system.php';
            $system_config_content = "<?php
\$config['locale'] = '%language';
\$config['timezone'] = '%timezone';
if (defined('CLI_MODE')) {
    \$config['base_url']  = \$config['script_url'] = '%url';
}
";

            $system_config_content = strtr($system_config_content, [
                '%language'=>$account->language,
                '%timezone'=>$account->timezone,
                '%url' => $account->url,
            ]);

            file_put_contents($system_config_file, $system_config_content);
        }
    }
}
