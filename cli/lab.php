#!/usr/bin/env php
<?php
require $_SERVER['Q_ROOT_PATH'] . 'cli/base.php';
require dirname(__FILE__) . '/frame.php';

// TODO 重构至形如 php lab.php -cmd=open -lid=2 (xiaopei.li@2012-02-04)

class Lab_Deployment extends Frame {
    private $account;
	private $lab_path;

	private $db_backup;
	private $dir_backup;

	function __construct($site_id, $lab_id, $lab_name, $url, $account = NULL) {
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
		$this->site_id = $site_id;
		$this->code_id = $lab_id;
		$this->lab_name = $lab_name;
		$this->url = $url;

        if ($account !== NULL) {
            $this->account = $account;
            $this->locale =  $account->locale;
            $this->timezone = $account->timezone;
            $this->currency = $account->currency;
        }


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
		$this->add_to_proj_list();
		$this->show_message('站点已开通! 但还需 10 分钟左右新站点的服务(如全文索引,发信)才会正常. 10 分钟后若服务还不正常, 请联系运维!');
	}

	function do_close() {
		$this->touch_disable_file();
		$this->show_message('关闭成功!');
	}

	function undo_close() {
		$this->delete_disable_file();
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

	function add_to_proj_list() {

		$proj_list = '/etc/lims2/proj_list';
		$this_proj = "{$this->site_id}\t{$this->code_id}";

		$grep_cmd = "grep -qs '^$this_proj$' $proj_list";
		exec($grep_cmd, $foo, $return_var);

		if ($return_var !== 0) {
			file_put_contents($proj_list, "\n" . $this_proj, FILE_APPEND);
		}
	}

	// 该方法需以 root 运行
	static function generate_etc() {


		$proj_list_file = '/etc/lims2/proj_list';
		@touch($proj_list_file);
		$proj_list_stat = @stat($proj_list_file);

		$touch_file = '/etc/lims2/proj_check';
		@touch($touch_file);
		$touch_file_stat = @stat($touch_file);

		// http://www.php.net/stat
		// 9 是 mtime, 如果 proj_list 在 上次检查后未改动, 则退出
		if ($proj_list_stat && $touch_file_stat && $proj_list_stat[9] < $touch_file_stat[9]) {
			return;
		}

		$proj_list = file_get_contents($proj_list_file);
		$projs = explode("\n", $proj_list);

		$root_path = ROOT_PATH;

		$daemon_conf = '/etc/lims2/daemon.conf';
		@touch($daemon_conf);
		file_put_contents($daemon_conf, '');

		$cron_conf = '/etc/cron.d/lims2';
		if(!is_file($cron_conf)) @touch($cron_conf);
		// cron 配置只有 root 可写
		file_put_contents($cron_conf, '');

		$sphinx_conf = '/etc/sphinxsearch/conf.d/lims2.conf';
		if(!is_file($sphinx_conf)) @touch($sphinx_conf);
		file_put_contents($sphinx_conf, '');

		foreach ($projs as $proj) {
			if (!$proj) continue;

			list($proj_site_id, $proj_lab_id) = explode("\t", $proj);

			// 若该处有修改, 应同步修改打包时生成配置的 deploy/debian_package.php
			// (xiaopei.li@2013-06-21)

			// daemon
			$daemon_php = "$root_path/cli/get_daemon.php -r=/usr/share/lims2/";
			$daemon_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $daemon_php >> $daemon_conf";
			exec($daemon_cmd);

			// cron
			$cron_php = "$root_path/cli/get_cron.php -u=www-data -r=/usr/share/lims2/";
			$cron_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $cron_php >> $cron_conf";
			exec($cron_cmd);

			// sphinx
			$sphinx_php = "$root_path/cli/get_sphinx.php";
			$sphinx_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $sphinx_php >> $sphinx_conf";
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

	private function _check_labs_base_path() {
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
		$lab_config_content = <<<'LABCONFIG'
<?php
$config['name'] = '%lab_name';

$config['pi'] = '%pi_token';

$config['admin'][] = $config['pi'];

$config['currency_sign'] = '%currency';
LABCONFIG;

        if ($this->account->id) {
            $user_token = $this->account->admin_token ? : Config::get('accounts.admin_token', 'genee|database');
        }
        else {
            $user_token = Config::get('accounts.admin_token', 'genee|database');
        }

        if ($this->currency) {
            $all_currency = Config::get('accounts.currency');
            $currency = $all_currency[$this->currency]['sign'];
        }
        else {
            $currency = '¥';
        }

		$lab_config_content = strtr($lab_config_content, [
										'%lab_name' => $this->lab_name,
                                        '%pi_token'=> $user_token,
                                        '%currency'=> $currency,
										]);
		file_put_contents($lab_config_file, $lab_config_content);

		// 增加 equipment config key (xiaopei.li@2012-02-04)
		$eq_config_file = $config_path . 'equipment.php';
		$eq_config_content = <<<'EQCONFIG'
<?php
$config['private_key'] = "
%key
";
EQCONFIG;

		$key = shell_exec('openssl genrsa 2048 2>/dev/null'); // shell_exec return the complete output as a string
		$eq_config_content = strtr($eq_config_content, [
							   '%key' => $key,
							   ]);
		file_put_contents($eq_config_file, $eq_config_content);


		$system_config_file = $config_path . 'system.php';
		$system_config_content = <<<'CONF'
<?php
if (defined('CLI_MODE')) {
    $config['base_url']  = $config['script_url'] = '%url';
}

$config['locale'] = '%locale';
$config['timezone'] = '%timezone';
CONF;

		$system_config_content = strtr($system_config_content, [
							   '%url' => $this->url,
                               '%locale'=> $this->locale ? : 'zh_CN',
                               '%timezone' => $this->timezone ? : 'Asia/Shanghai',
							   ]);
		file_put_contents($system_config_file, $system_config_content);
	}

	function do_init_db() {
		$db = Database::factory();

		$SQL_TEMPLATES['create_database'] = <<<SQL
CREATE DATABASE IF NOT EXISTS `lims2_%lab_id` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
SQL;

		$SQL = strtr($SQL_TEMPLATES['create_database'],
					 ['%lab_id' => $this->code_id]);

		$db->query($SQL);
	}

	function undo_init_db() {
		$db = Database::factory();
		$SQL_TEMPLATES['drop_database'] = <<<SQL
DROP DATABASE `lims2_%lab_id`;
SQL;
		$SQL = strtr($SQL_TEMPLATES['drop_database'], ['%lab_id' => $this->code_id]);
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

			$SQL_TEMPLATES['insert_auth'] = <<<SQL
INSERT INTO `_auth`
(`token`, `password`)
values
('%token', '%md5_password');
SQL;

			// TODO add users(xiaopei.li@2012-01-18)
			// add genee
            if ($this->account->id) {
                $user_token = $this->account->admin_token ? : Config::get('accounts.admin_token', 'genee|database');
                $password = $this->account->admin_password ? : Config::get('accounts.admin_password', '83719730');
            }
            else {
                $user_token = Config::get('accounts.admin_token', 'genee|database');
                $password = Config::get('accounts.admin_password', '83719730');
            }

            list($auth_token, $backend) = Auth::parse_token($user_token);

			$query = strtr($SQL_TEMPLATES['insert_auth'], [
								 '%token' => $auth_token,
								 '%md5_password' => md5($password),
							   ]);
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
				$SQL_TEMPLATES['insert_user'] = <<<SQL
INSERT INTO `user`
(`token`, `email`, `name`, `atime`, `hidden`)
VALUES
('%token', '%email', '%name', 1, '%is_hidden');
SQL;

				/* add genee */
				$genee_name = Config::get('accounts_sync.genee_name');
				$query = strtr($SQL_TEMPLATES['insert_user'], [
								   '%token' => $user_token,
								   '%email' => 'support@geneegroup.com',
								   '%name' => $genee_name,
								   '%is_hidden' => 1,
								   ]);

				$db->query($query);
			}
		}
	}
}


if (!count(debug_backtrace())) { // like python's 'if (__name__ == "__main__" )'

	$usage = "Usage:\nphp lab.php -s=lab -l=demo -n=基理实验室 -u='http://g.labscout.cn/demo/' -a=1(account_id) -o={close,backup,delete,open,create} \n";

	$shortopts = "s:l:n:u:o:a:C";
	$longopts = [
		'site:',
		'lab:',
		'name:',
		'url:',
		'option:',
        'account_id:',
		'config',
		];

	$opts = getopt($shortopts, $longopts);

	if (isset($opts['C']) || isset($opts['config'])) {
		// 特殊用法, 一般以 root 用户 cron 运行,
		// 以按 proj_list 生成最新的配置
		Lab_Deployment::generate_etc();
		die;
	}

	if (isset($opts['s']) && $opts['s']) {
		$site = $opts['s'];
	}
	else if (isset($opts['site']) && $opts['site']) {
		$site = $opts['site'];
	}

	if (isset($opts['l']) && $opts['l']) {
		$lab = $opts['l'];
	}
	else if (isset($opts['lab']) && $opts['lab']) {
		$lab = $opts['lab'];
	}

	if (isset($opts['n']) && $opts['n']) {
		$name = $opts['n'];
	}
	else if (isset($opts['name']) && $opts['name']) {
		$name = $opts['name'];
	}

	if (isset($opts['u']) && $opts['u']) {
		$url = $opts['u'];
	}
	else if (isset($opts['url']) && $opts['url']) {
		$url = $opts['url'];
	}

	if (isset($opts['o']) && $opts['o']) {
		$action = $opts['o'];
	}
	else if (isset($opts['option']) && $opts['option']) {
		$action = $opts['option'];
	}

    if (isset($opts['a']) && $opts['a']) {
        $account_id = $opts['a'];
    }
    else if (isset($opts['account_id']) && $opts['account_id']) {
        $account_id = $opts['account_id'];
    }

    $account = O('lims_account', $account_id);
    if (!$account->id) $account = NULL;

	if (!($lab && $name && $url && $action)) {
		die($usage);
	}

	$lab = new Lab_Deployment($site, $lab, $name, $url, $account);
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
