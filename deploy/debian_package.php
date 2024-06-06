#!/usr/bin/env php
<?php
  // TODO 增加 rsync 机制(xiaopei.li@2012-01-06)
  // TODO 根据 config 中的端口自动生成 xinetd 配置(xiaopei.li@2012-01-06)
  // TODO 每个lab都需要 openssl genrsa 2048 到 config/equipment.php, 但切记切记更新时保留此配置(xiaopei.li@2012-01-06)
require_once './package.php';

define('PROGRAM_PATH', 'usr/share/lims2');
define('CACHE_PATH', 'var/cache/lims2');
define('USER_DATA_PATH', 'var/lib/lims2');
define('ETC_PATH', 'etc');
define('LIMS2_ETC_PATH', 'etc/lims2');

class Debian_Package extends Package {
	private static $mkdir_default_perm = 0755;

	function make($pkg_id) {
		$modules = $this->check();
		$this->init_env($pkg_id);
		$this->prepare_files($modules);

		// 重新组织程序的目录结构
		$this->render_program_struct();
		// 增加配置文件
		$this->add_etc_files();
		// 增加控制文件
		$this->add_control_files();

		// 按客户修改配置/控制文件
		$this->update_etc_and_control_files();
		// 转移备份脚本及备份清单
		$this->add_to_proj_list();

		// 添加通过 lims2 程序生成的 etc 配置
		$this->generate_etc();

		// 删除可能包进的日志文件
		$this->delete_logs();

		// 指明配置文件, 安装时(至少在 prerm 后),
		// 若这些文件被用户修改过, 会提示
		$this->add_conffiles();

		// 打包
		$this->dpkg_build();
	}

	function prepare($pkg_id) {
		$modules = $this->check();

		// echo 1;

		$this->init_env($pkg_id);

		// echo 2;
		$this->prepare_files($modules);

		// echo 3;
		// 增加配置文件
		$this->add_etc_files();

		// echo 4;
		// 按客户修改配置/控制文件
		$this->update_etc_and_control_files();
		// 转移备份脚本及备份清单
		$this->add_to_proj_list();

		// prepare 不打包
	}

	// 按包中的项目生成 etc
	function generate_etc() {
		// TODO
		// 0. modify get_* scripts, add fakeroot option
		// 1. read projs from proj_list
		// 2. foreach run get_* script and write to fakeroot

		$proj_list = file_get_contents("{$this->fakeroot}/etc/lims2/proj_list");
		$projs = explode("\n", $proj_list);

		$root_path = ROOT_PATH;

		foreach ($projs as $proj) {
			if (!$proj) continue;

			list($proj_site_id, $proj_lab_id) = explode("\t", $proj);

			// 若该处有修改, 应同步修改自动创建 lab 的 sites/lab/labs/admin/cli/lab.php
			// (xiaopei.li@2013-06-21)

			// daemon
			$daemon_php = "$root_path/cli/get_daemon.php -r=/usr/share/lims2/";
			$daemon_conf = $this->fakeroot . '/etc/lims2/daemon.conf';
			$daemon_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $daemon_php >> $daemon_conf";
			echo "$daemon_cmd\n";
			exec($daemon_cmd);

			// cron
			// $cron_php = $this->fakeroot . '/usr/share/lims2/cli/get_all_cron.php' . " -u=www-data -r=/usr/share/lims2/ {$this->fakeroot}/usr/share/lims2 {$this->fakeroot}/var/lib/lims2";
			$cron_php = "$root_path/cli/get_cron.php -u=www-data -r=/usr/share/lims2/";
			$cron_conf = $this->fakeroot . '/etc/cron.d/lims2';
			$cron_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $cron_php >> $cron_conf";
			echo "$cron_cmd\n";
			exec($cron_cmd);

			// sphinx
			// $sphinx_php = $this->fakeroot . '/usr/share/lims2/cli/get_all_sphinx.php' . " {$this->fakeroot}/usr/share/lims2";
			$sphinx_php = "$root_path/cli/get_sphinx.php";
			$sphinx_conf = $this->fakeroot . '/etc/sphinxsearch/conf.d/lims2.conf';
			$sphinx_cmd = "SITE_ID=$proj_site_id LAB_ID=$proj_lab_id Q_ROOT_PATH=$root_path php $sphinx_php >> $sphinx_conf";
			echo "$sphinx_cmd\n";
			exec($sphinx_cmd);

		}

	}

	function add_to_proj_list() {
		$proj_list = "{$this->fakeroot}/etc/lims2/proj_list";
		exec("cp debian_backup {$this->fakeroot}/etc/lims2/backup");
		file_put_contents($proj_list, "$this->site_id\t$this->lab_id\n", FILE_APPEND);

		$nfs_list = "{$this->fakeroot}/etc/lims2/nfs_list";
		$nfs_root = Config::get('nfs.root');
		file_put_contents($nfs_list, "$nfs_root\n", FILE_APPEND);
	}

	function delete_logs() {
		$pwd = getcwd();
		if (chdir($this->fakeroot)) {
			exec('find . -type f -name "*.log" -delete');
		}
		chdir($pwd);
	}

	function add_conffiles() {
		$pwd = getcwd();
		if (chdir($this->fakeroot)) {
			exec('find `find usr/ -type d -name config` -type f >> DEBIAN/conffiles');
			exec('find etc/ -type f >> DEBIAN/conffiles');
			exec('find var/ -type f >> DEBIAN/conffiles');
		}
		chdir($pwd);
	}

	// 创建临时包目录
	function init_env($pkg_id) {

		$this->fakeroot = "/tmp/lims_debian_package_fakeroot_" . $pkg_id;

		echo "======= fakeroot: {$this->fakeroot} ======\n";

		$this->program_path =  "{$this->fakeroot}/" . PROGRAM_PATH;

		if (!is_dir($this->program_path) && !mkdir($this->program_path, self::$mkdir_default_perm, TRUE)) {
			$this->fatal_error("创建打包临时目录失败 {$this->program_path}");
		}
	}

	function update_etc_and_control_files() {
		exec("sed -i 's/%site_id%/{$this->site_id}/' `grep -lrs '%site_id%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");
		exec("sed -i 's/%lab_id%/{$this->lab_id}/' `grep -lrs '%lab_id%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");

		// TODO it's not very fit to assign $version here(xiaopei.li@2011-12-16)
		$version = Config::get('system.version');

		if (preg_match('/(\d.*$)/', $version, $matches)) {
			$this->version = $matches[1];
			preg_match('/([\d.]*)/', $this->version, $matches);
			$this->base_version = $matches[1];
		}
		else {
			$this->version = 0;
		}

		exec("sed -i 's/%VERSION%/{$this->version}/' `grep -lrs '%VERSION%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");
		exec("sed -i 's/%BASE_VERSION%/{$this->base_version}/' `grep -lrs '%BASE_VERSION%' {$this->fakeroot}/etc {$this->fakeroot}/DEBIAN` 2>/dev/null");

	}

	function dpkg_build() {
		exec("dpkg -b {$this->fakeroot} {$this->lab_id}-{$this->version}.deb");
	}

	function add_control_files() {
		exec("cp -r DEBIAN {$this->fakeroot}");
	}

	function add_etc_files() {
		// mkdirs
		$this->etc_path = "{$this->fakeroot}/" . ETC_PATH;
		$this->lims2_etc_path = "{$this->fakeroot}/" . LIMS2_ETC_PATH;
		if (!is_dir($this->etc_path)) {
			mkdir($this->etc_path, self::$mkdir_default_perm, TRUE);
		}
		if (!is_dir($this->lims2_etc_path)) {
			mkdir($this->lims2_etc_path, self::$mkdir_default_perm, TRUE);
		}

		// render etc

		// 默认 etc
		exec("cp -r etc/* {$this->etc_path}");
		$user_etc_path = $this->user_data_path . "/sites/{$this->site_id}/labs/{$this->lab_id}" .  '/etc' ;

		// 项目重载的 etc
		if (is_dir($user_etc_path)) {
			exec("cp -r {$user_etc_path}/* {$this->etc_path}");
		}

		// other etc
		// logrotate
		$this->logrotate_path = "{$this->etc_path}/logrotate.d";

		// dateext 会使用日期为后缀, 当 rotate 时已有该后缀的文件时, rotate 不会执行, 日志保持原样
		$logrotate_conf = '/var/lib/lims2/sites/%site_id%/labs/%lab_id%/logs/*.log {
	weekly
	dateext
	missingok
	rotate 5000
	notifempty
	noolddir
	copytruncate
	compress
}
';
		// weekly + rotate 5000 可以保存快 100 年的备份
		$this->logrotate_file = "{$this->logrotate_path}/lims2_{$this->site_id}_{$this->lab_id}";
		file_put_contents($this->logrotate_file, strtr($logrotate_conf, [
									'%site_id%' => $this->site_id,
									'%lab_id%' => $this->lab_id,
									]));

	}

	function render_program_struct() {
		// mkdirs
		$this->user_data_path = "{$this->fakeroot}/" . USER_DATA_PATH;
		if (!is_dir($this->user_data_path)) {
			@mkdir($this->user_data_path, self::$mkdir_default_perm, TRUE);
		}


		$this->cache_path = "{$this->fakeroot}/" . CACHE_PATH;
		if (!is_dir($this->cache_path)) {
			@mkdir($this->cache_path, self::$mkdir_default_perm, TRUE);
		}

		// mv user data
		$pwd = getcwd();
		if (chdir($this->program_path)) {
			// 由于加入多 site/lab 打包机制, 该处换为以下支持多 site 的方法
			$site_labs = glob('sites/*/labs');
			foreach ($site_labs as $labs) {
				exec('cp -r --parents "' . $labs . '" "' . $this->user_data_path . '"');
				exec('rm -r "' . $labs . '"');

				// link
				$link = realpath(dirname($labs)) . '/labs';
				$target = '/' . USER_DATA_PATH . "/{$labs}";

				symlink($target, $link);

			}
		}
		chdir($pwd);

		// link cache
		$target = '/' . CACHE_PATH;
		$link = realpath($this->fakeroot) . '/usr/share/lims2/public/cache';
		@symlink($target, $link);
	}
}

if (!count(debug_backtrace())) { // like python's 'if (__name__ == "__main__" )'

	$shortopts = "s:l:p:d:tPM";
	$longopts = [
		'site:',
		'lab:',
		'pkg-id:',
		'dest:',
		'test',
		'prepare',
		'make',
        'escape'
		];

	$opts = getopt($shortopts, $longopts);

	$site_id = $opts['s'] ? : $opts['site'];
	$lab_id = $opts['l'] ? : $opts['lab'];

	if (!isset($opts['p'])) {
		$opts['p'] = '';
	}
	if (!isset($opts['pkg-id'])) {
		$opts['pkg-id'] = '';
	}

	$pkg_id = $opts['p'] ? : $opts['pkg-id'];
	if (!$pkg_id) {
		$pkg_id = uniqid();
	}

	if (!$site_id || !$lab_id) {
		die("usage: php debian_package.php  -s|--site SITE_ID -l|--lab LAB_ID [-p|--pkg-id 123] [-d|--dest somewhere] [-t|--test] [-P|--prepare] [-M|--make] [-e|--escape]\n");
		// 默认为 -M, 即 make
		// 可选 -P, 即 prepare
	}

	if (!isset($opts['d'])) {
		$opts['d'] = '';
	}
	if (!isset($opts['dest'])) {
		$opts['dest'] = '';
	}
	$dest = $opts['d'] ? : ($opts['dest'] ? : '.');

	$is_test = isset($opts['t']) || isset($opts['test']);
	$prepare_only = isset($opts['P']) || isset($opts['prepare']);
    $is_escape = isset($opts['e']) || isset($opts['escape']);

	$_SERVER['SITE_ID'] = $site_id;
	$_SERVER['LAB_ID'] = $lab_id;

	if ($_SERVER['SITE_ID'] && $_SERVER['LAB_ID']) {
		$db_name = 'lims2_' . $lab_id;

		exec("mysql -ugenee $db_name -e 'exit'", $foo, $db_exists);
		$exec_true = 0;

		if ($db_exists != $exec_true) {
			// 数据库不存在
			$db_created = 1;
			exec("mysql -ugenee -e 'create database $db_name'", $foo, $db_created);
		}

		if ($db_exists == $exec_true || $db_created == $exec_true) {

			// 引入 cli/base, 以读取待打包 lab 的配置 (xiaopei.li@2011-12-13)
			require('../cli/base.php');

			$package = new Debian_Package($site_id, $lab_id, $dest, $is_test, $is_escape);

			if ($prepare_only) {
				$package->prepare($pkg_id);
				echo $package->fakeroot;
			}
			else {
				$package->make($pkg_id);
			}

			if ($db_exists != $exec_true && $db_created == $exec_true) {
				// drop
				exec("mysql -ugenee -e 'drop database $db_name'");
			}
		}
	}
}
