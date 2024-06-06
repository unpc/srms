#!/usr/bin/env php
<?php

require "base.php";

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


