#!/usr/bin/env php
<?php

require "base.php";

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


$scanner = new I18N_Scanner(ROOT_PATH);
$scanner->scan();

if ($scanner->errors > 0) {
	printf("发现 %d 处错误\n", $scanner->errors);
	exit(1);
}
