#!/usr/bin/env php
<?php

$_SERVER['LAB_ID'] = 'ut';
require $_SERVER['Q_ROOT_PATH'].'cli/base.php';
require 'unit_test.php';

assert_options(ASSERT_BAIL, 1);
assert(LAB_ID=='ut');

$db = Database::factory();
assert($db->name() == 'ut');

$db->empty_database();

define('ERROR_TEST_FAIL', 1);
define('ERROR_MODULE_NOT_INSTALLED', 2);

class U extends Unit_Test {

	static $_quiet = FALSE;

	static function _shutdown_cb() {
		if (count(U::$fails) > 0) {
			if (U::$_quiet) {
				U::echo_fails();
			}
			exit(ERROR_TEST_FAIL);
		}
	}

	static function _traverse_cb($path, $params) {
		global $count;
		if (is_file($path) && File::extension($path)=='php') {
			$path = File::relative_path($path, $params['root']);
			$name = preg_replace('/.php$/', '', $path);
			if (U::$_quiet) {
				U::test($name, TRUE);
			}
			else {	
				U::echo_title("test $name");
				U::test($name);
				U::echo_endl();
			}
		}
	}

	static function run() {
		global $argv;
		
		register_shutdown_function('U::_shutdown_cb');

		$args = $argv;
		// 去掉第一个参数
		array_shift($args);	
		
		$root = U::test_root();

		$count = 0;

		foreach ($args as $name) {
			if ($name == '-q') {
				U::$_quiet = TRUE;
				continue;
			}

			$count++;

			$path = $root.$name;
			if (!is_dir($path)) {
				$path = $path .'.php';
			}

			File::traverse($path, 'U::_traverse_cb', ['root'=>$root]);
		}

		if ($count == 0) {
			File::traverse($root, 'U::_traverse_cb', ['root'=>$root]);
		}
	}
}

U::run();




