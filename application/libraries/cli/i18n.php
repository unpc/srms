<?php
class CLI_I18N {

	static function scan() {
		$scanner = new I18N_Scanner(ROOT_PATH);
		$scanner->scan();

		if ($scanner->errors > 0) {
			printf("发现 %d 处错误\n", $scanner->errors);
			exit(1);
		}
	}

	static function local_fill() {
		Config::set('debug.i18n_nocache', TRUE);

		$db = Database::factory();
		foreach (Config::get('system.locales', []) as $locale => $foo) {
			Config::set('system.locale', $locale);
			
			I18N::shutdown();
			I18N::setup();

			$rs = $db->query('SELECT module, orig FROM i18n');
			if ($rs) while($row = $rs->row('assoc')) {
				if (in_array($row['module'], ['system', 'application'])) {
					$str = T($row['orig']);
				} 
				else {
					if (!Module::is_installed($row['module'])) {
						continue;
					}
					$str = I18N::T($row['module'], $row['orig']);
				}
				
				$str = addcslashes($str,"\n\r"); 

				if ($str != $row['orig']) {
					//$sql = $db->rewrite('UPDATE i18n SET `%s` = "%s" WHERE module="%s" AND orig="%s"', $locale, $str, $row['module'], $row['orig']);
					//echo $str."\n";
					//echo $row['orig']."\n";
					//echo "===========\n";

					$db->query('UPDATE i18n SET `%1$s` = "%2$s" WHERE module="%3$s" AND orig="%4$s" AND `%1$s`=""', $locale, $str, $row['module'], $row['orig']);
				}
			}	
		}
	}

	static function link($path=null) {
		$linker = new I18N_Linker;
		$linker->link_to($path?:ROOT_PATH.'i18n');
	}

	static function import() {
		$db = Database::factory();

		$loaded_path = [];

		foreach ((array) Config::get('system.locales') as $locale => $foo) {

			$dict = [];
			
			$rs = $db->query('SELECT * FROM i18n');
			if ($rs) while($row = $rs->row('assoc')) {
			
				switch($row['module']) {
				case 'application':
					$path = APP_PATH;
					break;
				case 'system':
					$path = SYS_PATH;
					break;
				default:
					$path = Core::module_path($row['module']);
				}

				if (!file_exists($path)) continue;
				$path .= I18N_BASE.$locale.EXT;
				if (!$loaded_path[$path]) {
					if (file_exists($path)) {
						$lang = [];
						include $path;
						foreach ($lang as $key => $value) {
							$name = addcslashes($key, '\'');
							if ($value === NULL || preg_match('/(phtml|php)$/', $value)) {
								if (!isset($dict[$path][$key]) && $locale != 'zh_CN') {
									$dict[$path][$key] = sprintf('$lang[\'%s\'] = NULL;',  $name);
								}
							}
							else {
								$dict[$path][$key] = sprintf('$lang[\'%s\'] = \'%s\';',  $name, addcslashes($value,'\''));
							}
						}
					}
					$loaded_path[$path] = TRUE;
				}

				$key = $row['orig'];
				$name = addcslashes($key, '\'');

				if ($row[$locale] && !preg_match('/(phtml|php)$/', $row[$locale])) {
					$dict[$path][$key] = sprintf('$lang[\'%s\'] = \'%s\';',  $name, addcslashes($row[$locale],'\''));
				}
				elseif (!preg_match('/NULL;$/', $dict[$path][$key]) && $locale != 'zh_CN') {
					$dict[$path][$key] = sprintf('$lang[\'%s\'] = NULL;',  $name);
				}

			}

			foreach ($dict as $path => $items) {

				File::check_path($path);
				echo "修改$path\n";
				if (count($items) > 0) {
					file_put_contents($path, "<?php\n\n".implode("\n", $items));
				}
				else {
					unlink($path);
				}
			}
			
		}
	}
}

class I18N_Scanner {
	
	public $errors = 0;
	
	private $path;
	private $mids;
	
	private static $spec_roots = ['system'=>'system', '*'=>'application', 'application'=>'application'];
	private static $valid_roots = ['application', 'system', 'modules', 'sites'];
	function __construct($path) {
		$this->path = $path;
		$mids = array_values(Core::module_paths());
		$mids = array_combine($mids, $mids);
		foreach ($mids as &$mid) {
			if (!Module::is_installed($mid)) {
				unset($mids[$mid]);
			}
		}
		$this->mids = $mids + self::$spec_roots;
	}
	
	function _scan_file($path, $params) {

		$rel_path = File::relative_path($path, $this->path);
		$curr_root = substr($rel_path, 0, stripos($rel_path, '/') );
		
		if ($curr_root && !in_array($curr_root, self::$valid_roots)) return FALSE;

		if (is_file($path) && preg_match('/\.(php|phtml)$/', $path)) {
			$source = @file_get_contents($path);
			if (preg_match('|config/perms.php$|', $rel_path)) {
				$config = [];
				include($path);
				foreach ($config as $mid => $perms) {
					if (!isset($mids[$mid])) continue;
					foreach ($perms as $perm => $default) {
						if ($perm[0] == '#') continue;
						if ($perm[0] == '-') $perm = substr($perm, 1);
						$params['results'][$mid][$perm][] = $rel_path;
						//echo "$mid: $perm\n";
					}
				}
				unset($config);
			}
			elseif (preg_match_all('/(?:\bI18N::[H]?T\(([\'"])(\w+?)\1\s*,\s*|[^:]\b[H]?T\s*\(\s*)(?:([\'"])(.+?)\3|([\$\w.]+)\))/u', $source, $matches, PREG_SET_ORDER)) {
				$mids = $this->mids;
				
				$default = self::$spec_roots[$curr_root] ?: self::$spec_roots['*'];
				
				foreach($matches as $parts) {
					if ($parts[4] == 'messages') var_dump($parts);
					$mid = $parts[2] ?: $default;
					if (!$parts[3]) {
						$this->errors ++;
						continue;
					}
					$str = $parts[4];
					if ($parts[2] && !$mids[$mid]) {
						// printf("文件:%s\n\tI18N::T('%s', '%s') 模块不存在\n", $path, $mid, $str);
						//$this->errors ++;
						continue;
					}
					$params['results'][$mid][$str][] = $rel_path;
				}
			}		
		}

	}
	
	function scan() {
	
		$this->errors = 0;

		$results = [];

		printf("开始扫描%s中的I18N字符串...\n", $this->path);

		File::traverse($this->path, [$this, '_scan_file'], ['system'=>'system', 'application'=>'application', 'results'=>&$results, 'mids'=>$mids]);
		
		$sql = 'INSERT IGNORE INTO `i18n`(`module`,`orig`,`url`) VALUES';
		
		$db = Database::factory();
		$tmp_sql = [];
		
		// $db->query('create table i18n (module varchar(20), orig varchar(250), url varchar(250), `en_US` varchar(250) not null default "") charset utf8');

		//$db->query('TRUNCATE TABLE `i18n`');


		foreach($results as $module=>$value){
			foreach($value as $orig=>$urls){
				if (mb_strlen($orig) > 250) {
					printf("error: %s\n", $orig);
					$this->errors ++;
				}
				$tmp_sql[] = '("'.$db->escape($module).'", "'.$db->escape($orig).'", "'.$db->escape(implode("\n", $urls)).'")';	
			}
		}
	
		$sql .= implode(',', $tmp_sql).';';

		$db->query($sql);

		printf("扫描完成，已导入%d条数据\n", $db->affected_rows());
	}
}

class I18N_Linker {

	private $_locales;

	function __construct() {
		$this->_locales = (array) Config::get('system.locales');
	}

	private function _link_to($target, $dir, $name) {
		foreach ($this->_locales as $locale=>$n) {
			$path = $target . I18N_BASE . $locale . EXT;
			$to = $dir.'/'.$name.'.'.$locale.EXT;
			if (file_exists($path)) {
				symlink($path, $to);
				echo "$path linked to $to!\n";
			}
			else {
				echo "$path error!\n";
			}
		}
	}

	function link_to($dir) {

		File::check_path($dir.'/foo', 0777);
		$this->_link_to(APP_PATH, $dir, 'application');
		$modules = Core::module_paths();
		foreach ($modules as $m=>$n) {
			$this->_link_to($m, $dir, $n);
		}
	}
}