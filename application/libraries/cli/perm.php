<?php
class CLI_Perm {
	static function scan($arg=null){
		try {
			$scanner = new Perm_Scanner(ROOT_PATH);
			$scanner->to = isset($arg) ? $arg : 'db';
			$scanner->scan();
		}
		catch (Error_Exception $e) {
			Log::add($e->getMessage(), 'error');
		}
	}
}


class Perm_Scanner {
	static $scanned_names = [];
	static $module_perms = [];
	static $modules = [];

	private $path;
	private $mids;
	
	public static $to='db';
	
	private static $spec_roots = ['system'=>'system', '*'=>'application'];
	
	function __construct($path) {
		$this->path = $path;
		$this->mids = Q("module")->to_assoc('mid', 'id');
	}
	
	function scan() {
		File::traverse(ROOT_PATH, [$this, '_scan_file']);
		if ($this->to == 'db') {
			$this->update_db();
		}
		else {
			$this->show_result();
		}
	}
	
	function show_result() {
		foreach($this->scanned_names as $name => $paths){
			if (count($paths)>1) {
				printf("%s: \n", $name);
				foreach ($paths as $path) {
					printf("\t%s;\n", $path);
				}
			}
			else {
				printf("%s: %s\n", $name, $paths[0]);
			}	
		}
	}
	
	function update_db() {
		/*
		$db = Database::factory();
		
		$db->query('TRUNCATE TABLE `perm`');
		$db->query('INSERT IGNORE INTO `perm` (`name`, `mid`) VALUES ("管理所有内容", "")');
		*/
		
		foreach($this->scanned_names as $name => $mid) {
			$mid = $mid ?: 'application';
			$this->module_perms[$mid][] = $name;
			printf("导入 [%s] %s\n", $mid ?: '*', $name);
			// $db->query('INSERT IGNORE INTO `perm`(`name`, `mid`) VALUES ("%s", "%s")', $name, $mid);	
		}
		
		foreach((array)$this->module_perms as $mid=>$perms) {
			switch ($mid) {
			case 'application':
				self::generate_perms(APP_PATH, $mid, $perms);
				break;
			default:
				self::generate_perms(MODULE_PATH.$mid.'/', $mid, $perms);
			}
		}
	}
	
	static function generate_perms($base, $mid, $perms) {
	
		$content = ['<?php'];
		foreach ($perms as $perm) {
			$content[] = '$config[\''.$mid.'\'][\''.addcslashes($perm,'\'').'\'] = FALSE;';
		}
		echo $content."\n\n";
		//file_put_contents($base.CONFIG_BASE.'perms.php', implode("\n", $content));	
	}

	function _scan_file($path) {
	
		preg_match('/modules\/([^\/]*)\/(.*)/', $path, $matches);
		if ($matches[1]) {
			if(!isset($this->mids[$matches[1]])){
				return;
			}
			$mid = $matches[1];
		}
		
		if(preg_match('/cli\//', $path)){
			return;
		}
		
		if (is_file($path) && preg_match('/\.(php|phtml)$/', $path)) {
			$source = @file_get_contents($path);
			if (preg_match_all('/->access\(([\'"])(.+?)\1/', $source, $matches, PREG_SET_ORDER)) {
				foreach($matches as $parts) {
					$perm_string = $parts[2];
					if ($this->to == 'db') {
						$value = $mid;
					}
					else {
						$value = (array)$this->scanned_names[$perm_string];
						array_push($value, $path);
					}
					$this->scanned_names[$perm_string] = $value;
				}
			}		
		}
	}
}