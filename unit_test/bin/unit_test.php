<?php

class Unit_Test {

	const ANSI_RED = "\033[31m";
	const ANSI_GREEN = "\033[32m";
	const ANSI_RESET = "\033[0m";
	const ANSI_HIGHLIGHT = "\033[1m";
	
	static $fails=[];

	static function test($name, $return_output = FALSE) {
		if ($return_output) {
			ob_start();
			@include (self::test_path($name));
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
		else {
			@include (self::test_path($name));
		}
	}
	
	static function root() {
		return ROOT_PATH.'unit_test/';
	}

	static function test_root() {
		return self::root().'tests/';
	}
	
	static function test_path($name) {
		return self::test_root()."$name.php";
	}
	
	static function echo_title() {
		echo self::ANSI_HIGHLIGHT;
		$args = func_get_args();
		if (count($args) > 0) call_user_func_array('printf', $args);
		echo self::ANSI_RESET;
		echo "\n\n";
	}
	
	static function echo_text() {
		$args = func_get_args();
		call_user_func_array('printf', $args);
		echo Unit_Test::ANSI_RESET;
		echo "\n";
	}
	
	static function reset() {
		self::$fails = [];
	}

	static function assert($name, $expr, $debug=NULL) {
		
		echo self::ANSI_RESET;
		
		echo "ASSERT ($name) ... ";
		if ($expr) {
			echo self::ANSI_GREEN;
			echo "SUCCESS";
			echo self::ANSI_RESET;
		}
		else {
			echo self::ANSI_RED;
			echo "FAILED";
			echo self::ANSI_RESET;
			if ($debug) {
				echo "\n";
				echo $debug;
			}

			self::$fails[] = ['name'=>$name, 'debug'=>$debug];
		}

		echo "\n";
	}

	static function echo_fails() {
		foreach (self::$fails as $fail) {
			echo 'ASSERT '.$fail['name'].' ... ';
			echo self::ANSI_RED;
			echo "FAILED";
			echo self::ANSI_RESET;
			if ($fail['debug']) {
				echo "\n";
				echo $fail['debug'];
			}
			echo "\n";
		}
	}
	
	static function echo_endl() {
		echo "\n";
	}

    static function data_path($name) {
        return self::root(). '/data/'. $name;
    }
}
