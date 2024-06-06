#!/usr/bin/env php
<?php

require "base.php";


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


