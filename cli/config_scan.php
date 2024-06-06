#!/usr/bin/env php
<?php
/*
Author: 朱洪杰
Description：用于统计系统中调用的config配置信息
*/
require "base.php";


try {
	$path = ROOT_PATH;
	$scanner = new Config_Scanner($path);
	$scanner->show_type = isset($argv[1]) ? $argv[1] : 'cli';
	$scanner->scan();
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'Config_Scan');
}


class Config_Scanner {
	function __construct($path) {
		$this->path = $path;
		$this->result = [];
		$this->show_type = 'cli';
	}
	
	function scan() {
		File::traverse($this->path, [$this, '_scan_file']);
	    $this->show_result();
	}
	
	function show_result() {
	    $f = '_show_' . $this->show_type;
		if (method_exists($this, $f)) {
			$this->$f();
		}
		else {
            $this->_show_cli();
		}
	}

    private function _show_csv() {
		$arr = $this->result;
		$current = '';
		$all = '_all_';
		$dir = '~/csv/foo';
		if (!empty($arr)) {
			$ah = fopen($all.'.csv', 'w');
			foreach ($arr as $fname=>$finfo) {
				if ($current!==$fname) {
					$current = $fname;
					$file =  $fname . '.csv';
					$handle = fopen($file, 'w');
					if ($handle) foreach ($finfo as $key=>$info) {
						$ele = [$key, implode(", ", $info)];
						if ($ah) fputcsv($ah, $ele);
						fputcsv($handle, $ele);
					}
					@fclose($handle);
				}
			}
		}
	}

	private function _show_cli() {
    	$arr = $this->result;
		$current = '';
		if (!empty($arr)) foreach ($arr as $fname=>$finfo) {
			if ($current!==$fname) {
				$current = $fname;
            	echo "\n" . str_repeat('=', 80) . "\n" . $fname . "\n";
			}
			foreach ($finfo as $key=>$info) {
		   		echo $key . str_repeat("\t", 4) . implode("\t", $info) . "\n";
			}
		}
		echo "\n";
	}
	
	function _scan_file($path) {
		if(preg_match('/cli\//', $path)){
			return;
		}	

		$bool = is_file($path) && preg_match('/\.(php|phtml)$/', $path);

		if (preg_match('/\/config\//', $path) && $bool) {
			$config = NULL;
			include_once($path);
			// 通过include将引入变量$config
			if (isset($config) && !empty($config)) {
				$fname = basename($path, '.php');
				foreach ($config as $k=>$v) {
					$this->result[$fname][$fname.'.'.$k][] = $path;
				}
			}
		}
		elseif ($bool) {
			$relative_path = File::relative_path($path);
			$source = @file_get_contents($path);
			if (preg_match_all('/(?<=Config|Lab)::get\(([\'"]{1})([^\'"]*)\1/', $source, $matches, PREG_SET_ORDER)) {
				foreach($matches as $parts) {
					$val = $parts[2];
					$pref = strtok($val, '.');
					if (!is_array($this->result[$pref][$val])) $this->result[$pref][$val] = [];
					$this->result[$pref][$val][] = $relative_path;
				}
			}		
		}
	}
	
}

