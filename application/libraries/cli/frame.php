<?php
class CLI_Frame {

	private $rollback_methods = [];
	
	function __call($name, $args) {
		$do = "do_{$name}";
		$undo = "undo_{$name}";
		if (method_exists($this, $do)) {
			if (is_callable([$this, $undo])) $this->register_rollback_method($undo, $args);
			return call_user_func_array([$this, $do], $args);
		}
	}

	protected function register_rollback_method($method, $args) {
		array_push($this->rollback_methods, [$method, $args]);
	}
	
	protected function random_string($length=6) {
		list($usec, $sec) = explode(' ', microtime());
		srand((float) $sec + ((float) $usec * 100000));
		
		$validchars = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		
		$password  = "";
		$counter   = 0;
		$max_length = strlen($validchars)-1;
		
		while ($counter < $length) {
			$actChar = substr($validchars, rand(0, $max_length), 1);
				$password .= $actChar;
				$counter++;
		}
		
		return $password;
	}

	protected function rollback() {
		foreach (array_reverse($this->rollback_methods) as $rollback) {
			$method = $rollback[0];
			$args = $rollback[1];
			call_user_func_array([$this, $method], $args);
		}
		exit();
	}

	protected function fatal_error($str) {
		$str = "致命错误：{$str}";
		$this->show_error($str);
		$this->rollback();
	}

	protected function warning_error($str) {
		$str = "警告：{$str}";
		$this->show_error($str);
	}

	protected function show_error($str) {
		$this->show_message($str);
	}
	
	protected function show_message($str, $endline=TRUE) {
		echo $str;
		if ($endline) echo "\n";
	}
}
