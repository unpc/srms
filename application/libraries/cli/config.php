<?php
class CLI_Config {
	static function scan($arg) {
		try {
			$path = ROOT_PATH;
			$scanner = new Config_Scanner($path);
			$scanner->show_type = isset($arg) ? $arg : 'cli';
			$scanner->scan();
		}
		catch (Error_Exception $e) {
			Log::add($e->getMessage(), 'Config_Scan');
		}
	}

	static function get_preload_or_config() {
		// 获得所有或指定的 预加载(preload)和配置(config), 以 json 输出
		// 支持 无参数/1个参数/多个参数
		// (xiaopei.li@2012-08-01)

		//cli.php config get_preload_or_config config:lab.pi

		$info = Application::info();

		if (func_num_args() > 0) {

			$k_v_pairs = [];
			$args = func_get_args();

			foreach ($args as $key) {
				$key_parts = explode(':', $key, 2);

				$type = $key_parts[0];
				if ('config' == $type) {
					$foo = explode('.', $key_parts[1], 2);
					$file = $foo[0];
					$config = $foo[1];

					$k_v_pairs[$key] = $info['config'][$file][$config];
				}
				else if ('preload' == $type) {
					$config = $key_parts[1];

					$k_v_pairs[$key] = $info['preload'][$config];
				}

			}

			$output = $k_v_pairs;
		}
		else {
			$output = $info;
		}

		echo json_encode($output);
	}
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
			$config = [
				'modules' => [],
				'stat_opts'=> [],
				'export_columns.eq_stat'=> [],
				];
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