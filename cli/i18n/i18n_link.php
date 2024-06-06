#!/usr/bin/env php
<?php

require "cli/base.php";

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


$linker = new I18N_Linker;
$linker->link_to($argv[1]?:ROOT_PATH.'i18n');

