#!/usr/bin/env php
<?php

if (!$argv[1]) {
	die("Usage: run.php [ROOT_PATH]\n");
}

define('ROOT_PATH', str_replace('//', '/', realpath($argv[1]).'/'));

class ANSI {
	const COLOR_RED = "\033[31m";
	const COLOR_GREEN = "\033[32m";
	const COLOR_YELLOW = "\033[33m";
	const COLOR_RESET = "\033[0m";
	const COLOR_HIGHLIGHT = "\033[1m";
};

define(TAB, "    ");

$test_fails;
$php_fails;
$module_fails;

function glob_recursive($pattern, $flags = 0)
{
	$files = glob($pattern, $flags);
	
	foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
	}
	
	return $files;
}

function run_tests($site_id) {
	global $test_fails, $php_fails, $module_fails;

	printf("RUN_TESTS (SITE_ID=%s)\n", $site_id);

	$tests = glob_recursive(ROOT_PATH.'unit_test/tests/*.php');
	sort($tests);

	foreach ($tests as $test_no => $test) {
		$output = NULL;

		$test = str_replace(ROOT_PATH.'unit_test/tests/', '', $test);
		$name = str_replace('.php', '', $test);

		$cmd = 'Q_ROOT_PATH='.ROOT_PATH.' SITE_ID='.$site_id.' bin/test.php '.$name;
		$quiet_cmd = 'Q_ROOT_PATH='.ROOT_PATH.' SITE_ID='.$site_id.' '.dirname(__FILE__).'/test.php -q '.$name.' 2>&1';
		printf("%4d. test %-40s ", $test_no + 1, $name);
		exec($quiet_cmd, $output, $ret);

		/*	
		if ($output) {
			echo "\n";	
			foreach($output as $line) {
				echo TAB.$line."\n";
			}
		}
		*/

		switch($ret) {
		case 0:
			echo ANSI::COLOR_GREEN;
			echo "SUCCESS";
			break;
		case 1:	//ERROR_TEST_FAIL
			$test_fails[] = ['cmd'=>$cmd, 'output'=> $output];
			echo ANSI::COLOR_RED;
			echo "FAIL";
			break;
		case 2: //ERROR_MODULE_NOT_INSTALLED
			$module_fails[] = ['cmd'=>$cmd, 'output'=> $output];
			echo ANSI::COLOR_YELLOW;
			echo "NO MODULE";
			break;
		default:
			$php_fails[] = ['cmd'=>$cmd, 'output'=>$output];
			echo ANSI::COLOR_YELLOW;
			echo "PHP ERROR";
		}

		echo ANSI::COLOR_RESET;
		echo "\n";
	}
}

if ($argv[2]) {
	run_tests($argv[2]);
}
else {
	$sites = glob(ROOT_PATH.'sites/*/');
	foreach($sites as $site) {
		$site_id = basename($site);
		run_tests($site_id);
	}
}

echo "\n\n";
if (count($test_fails) > 0) {
	echo ANSI::COLOR_HIGHLIGHT;
	echo count($test_fails);
	echo ANSI::COLOR_RESET;
	echo " BUGS FOUND! 请修正代码或测试脚本!\n";
	foreach($test_fails as $n => $f) {
		printf("%4d. %s\n", $n + 1, $f['cmd']);
		foreach($f['output'] as $o) {
			echo TAB.$o."\n";
		}
	}
	echo "\n";
}

if (count($module_fails) > 0) {
	echo ANSI::COLOR_HIGHLIGHT;
	echo count($module_fails);
	echo ANSI::COLOR_RESET;
	echo " (NO MODULE) TEST FOUND! 请检查环境是否正确!\n";
	foreach($module_fails as $n => $f) {
		printf("%4d. %s\n", $n + 1, $f['cmd']);
		echo "\n";
		foreach($f['output'] as $o) {
			echo TAB.'  '.$o."\n";
		}
		echo "\n";
	}
	echo "\n";
}

if (count($php_fails) > 0) {
	echo ANSI::COLOR_HIGHLIGHT;
	echo count($php_fails);
	echo ANSI::COLOR_RESET;
	echo " PHP ERRORS FOUND! 请修正单元测试脚本!\n";
	foreach($php_fails as $n => $f) {
		printf("%4d. %s\n", $n + 1, $f['cmd']);
		echo "\n";
		foreach($f['output'] as $o) {
			echo TAB.'  '.$o."\n";
		}
		echo "\n";
	}
	echo "\n";
}

