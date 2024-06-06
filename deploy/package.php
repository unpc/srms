#!/usr/bin/env php
<?php

error_reporting(E_ALL ^ E_NOTICE);

require_once './base.php';
// deploy/base 中定义了一些通用方法

class Package extends Base {
	public $program_path;

	function __construct($site_id, $lab_id, $to, $is_test = FALSE, $escape = FALSE) {
		$this->lims_root = '..';	// TODO 根目录应用更严谨的方式指定(xiaopei.li@2011.12.04)

		$this->site_id = $site_id;	// SITE_ID
		$this->lab_id = $lab_id;	// LAB_ID
        $this->escape = $escape;    //escape

		$this->lab_path = "{$this->lims_root}/sites/{$site_id}/labs/{$lab_id}"; // lab 目录
		$this->to = $to;			// 打包后放在哪儿

		$this->encode = !$is_test;
	}

	function make() {
		$modules = $this->check();
		$this->init_env();
		$this->prepare_files($modules);
		$this->zip();
	}

	// 检查各文件权限, 检查settings.ini
	function check() {
		if (!is_writable($this->to)) {
			$this->fatal_error("对目录［{$this->to}］丢失“写”权限");
		}

		if (!is_readable($this->lims_root)) {
			$this->fatal_error("对目录［{$this->lims_root}］丢失“读”权限");
		}

		$this->to = realpath($this->to);
		$this->lims_root = realpath($this->lims_root);
		if (!is_dir($this->lab_path)) {
			$this->fatal_error("希望打包的实验室［{$this->lab_id}］不存在");
		}

		$modules = array_keys(Config::get('lab.package_modules', []));
		if (!$modules) {
			$modules = array_keys(Config::get('lab.modules'));
		}
		// TODO whether modules' TRUE not checked(but it's always T) (xiaopei.li@2011-12-15)

		return $modules;
	}

	// 创建临时包目录
	function do_init_env() {
		$this->program_path = "{$this->to}/{$this->lab_id}";
		if (is_dir($this->program_path)) {
			$this->fatal_error("{$this->to}目录下已经存在名为{$this->lab_id}的目录");
		}
		if (!@mkdir($this->program_path)) {
			$this->fatal_error("创建实验室目录{$this->lab_id}");
		}
	}

	// 准备文件
	function prepare_files($modules) {
		$files = $this->compress($modules);
		$this->move($files);
		$this->copy();
	}

	// 压缩各模块
	function do_compress($modules) {
		$need_phar = ['system', 'application']; // 需要的模块
		$ms = [];
		foreach ($modules as $module) {
			$module = strtolower($module);
			$dir = "modules/{$module}";
			if (is_dir("{$this->lims_root}/{$dir}"))
				$ms[] = $dir;
			/**
			 * deprecated(xiaopei.li@2012-11-16)
			$dir = "labs/{$this->lab_id}/modules/{$module}";
			if (is_dir("{$this->lims_root}/{$dir}"))
				$ms[] = $dir;
			*/
			$dir = "sites/{$this->site_id}/modules/{$module}";
			if (is_dir("{$this->lims_root}/{$dir}"))
				$ms[] = $dir;
			/**
			 * 此种情况下, 由于要 cp lab 目录, 而 cp 时未剔除 lab/modules,
			 * 所以会造成既有原码, 又有 phar 的问题, 故先注释此段不对 lab/modules 打包
			 * (xiaopei.li@2012-11-16)
			$dir = "sites/{$this->site_id}/labs/{$this->lab_id}/modules/{$module}";
			if (is_dir("{$this->lims_root}/{$dir}"))
				$ms[] = $dir;
			*/
		} // 确定模块路径
		$need_phar = array_merge($need_phar, $ms);

		//生成压缩包文件
		require_once "includes/php_encoder.php";

		if ($this->encode) {
			$files = [];
			foreach ($need_phar as $f) {
				$file = $f.'.phar';
				$tmp_file = "{$this->lims_root}/{$file}";

				// TODO 若 file_exists, 可能是老包, 也应重新打包
				// 应检查md5(但检查就得记录之前的值...)
				if (!file_exists($tmp_file)) {
					$encoder = new PHP_Encoder("{$this->lims_root}/{$file}", $this->escape);
					$encoder->add("{$this->lims_root}/{$f}");
				}

				$files[] = $file;

			}
		}
		else {
			// TODO 此处 mv 了, 应 cp -r(xiaopei.li@2011.12.05)
			$files = $need_phar;
		}

		return $files;
	}

	// 转移 phar
	function do_move($files) {
		if (!empty($files)) {
			foreach ($files as $file) {
				$this->cp_or_mv('copy', $file);
			}
		}
	}

	private function cp_or_mv($operate, $file) {
		$from = $this->lims_root;
		$to = $this->program_path;

		$pwd = getcwd();
		if (chdir($from)) {

			if (is_file($file) || is_dir($file)) {

				$cmd = 'cp -r --parents "' . $file . '" "' . $to . '"';
				// error_log($cmd . "\n");
				exec($cmd);
				// error_log("== done ==\n");

				if ($operate == 'move') {
					exec('rm -r "' . $file . '"');
				}
			}

		}
		chdir($pwd);
	}

	// copy 一些需要 copy 的文件
	function do_copy() {
		$lab_path = "sites/{$this->site_id}/labs/{$this->lab_id}";
		$lab_copy = [];
		$dir = "{$this->lims_root}/{$lab_path}";
		if (is_dir($dir)) {
			if ($handle=opendir($dir)) {
				while (($file=readdir($handle))!==false) {
					if (strpos($file, '.')===0) continue;
					$f = "{$lab_path}/{$file}";
					if ($file=='modules' && is_dir($f)) {
					}
					else {
						$lab_copy[] = $f;
					}
				}
			}
			closedir($handle);
		}

		// 增加 sites/SITE_ID 下的 config/ 和 globals.php(xiaopei.li@2011.12.04)
        $need_copy = [
            'cli',
            'public/index.php',
            'public/images',
            'vendor',
            'get_snapshot.php',
            'maintain',
            'public/favicon.ico',
            "sites/{$this->site_id}/config",
            "sites/{$this->site_id}/controllers",
            "sites/{$this->site_id}/libraries",
            "sites/{$this->site_id}/views",
            "sites/{$this->site_id}/private",
            "sites/{$this->site_id}/public",
            "sites/{$this->site_id}/globals.php",
        ];
		$need_copy = array_merge($need_copy, $lab_copy);
		foreach ($need_copy as $file) {
			$this->cp_or_mv('copy', $file);
			// error_log("===== done ====== \n");
		}
		$dir = __DIR__;
	}

	// 压缩
	function do_zip() {
		$output = [];
		exec("tar -zcf {$this->lab_id}.tar.gz -C {$this->to} {$this->lab_id} 2>&1", $output);
		if (!empty($output)) {
			$this->warning_error('文件打包失败！');
		}
	}
}

if (!count(debug_backtrace())) {

	$shortopts = 's:l:d:te';
	$longopts = [
		'site:',
		'lab:',
		'dest:',
		'test',
        'escape',
		];

	$opts = getopt($shortopts, $longopts);

	$site_id = $opts['s'] ? : $opts['site'];
	$lab_id = $opts['l'] ? : $opts['lab'];

	if (!$site_id || !$lab_id) {
		die("usage: php package.php  -s|--site SITE_ID -l|--lab LAB_ID [-d|--dest somewhere] [-t|--test] [-e|--escape] \n");
	}

	$dest = $opts['d'] ? : ($opts['dest'] ? : '.');

    $escape = (bool) ($opts['e'] ? : $opts['escape']);

	$is_test = isset($opts['t']) || isset($opts['test']);
	$is_escape  = isset($opts['e']) || isset($opts['escape']);

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
			require_once '../cli/base.php';

			$package = new Package($site_id, $lab_id, $dest, $is_test, $is_escape);

			$package->make();

			if ($db_exists != $exec_true && $db_created == $exec_true) {
				// drop
				exec("mysql -ugenee -e 'drop database $db_name'");
			}
		}
	}
}
