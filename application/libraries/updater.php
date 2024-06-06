<?php

class Updater {

	//Unix, Win32NT, Win32s, Win32Windows, WinCE, OSX
	static function available_update($name, $os, $version=NULL) {

		$software = (array) Config::get('updater.'.$name);
		
		switch ($os) {
		case 'Win32NT':
		case 'Win32s':
		case 'Win32Windows':
		case 'WinCE':
			$os = 'win';
			break;
		}

		$release = $software[$os];
		
		if (!isset($release)) return NULL;
		if ($version == $release['version']) return NULL;
		
		return (object) $release;
	}

}
