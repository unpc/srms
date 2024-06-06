<?php

class Module {

	private static $_is_installed;
	static function is_installed($name) {
		$is_installed = self::$_is_installed[$name];
		if (!isset($is_installed)) {
			$path = Core::module_path($name);
			$is_installed = file_exists($path);
			if ($is_installed) {
				$modules = (array) Config::get('lab.modules');
				$is_installed = count($modules)>0 ? array_key_exists($name, $modules) : TRUE;
			}
			self::$_is_installed[$name] = $is_installed;
		}
		return $is_installed;
	}

	private static $_is_accessible;
	static function is_accessible($name, $sub_name = "") {

		if (!self::is_installed($name)) return FALSE;

		$key = $sub_name ? $sub_name : $name;
		$is_accessible = self::$_is_accessible[$key];
		if (!isset($is_accessible)) {

			$is_accessible = Event::trigger("module[{$key}].is_accessible", $key);
			if ($is_accessible === NULL) {
				$requires = Config::get('access.!'.$name);
				if ($requires === TRUE) {
					$is_accessible  = TRUE;
				}
				else {
					if ($requires === NULL) $requires = Config::get('access.*');

					$is_accessible = TRUE;
					if (is_array($requires)) {
						$me = L('ME');
						foreach($requires as $require){
							if (!$me->access($require)) {
								$is_accessible = FALSE;
								break;
							}
						}
					}
				}

			}

			self::$_is_accessible[$key] = $is_accessible;
		}

		return $is_accessible;
	}

}
