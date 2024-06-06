#!/usr/bin/env php
<?php

require 'base.php';

$schemas = Config::$items['schema'];

$name = $_SERVER['LAB_ID'];

$url = Config::get('database.'.$name.'.url');
if (!$url) {
	$dbname = Config::get('database.'.$name.'.db');
	if (!$dbname) $dbname = Config::get('database.prefix') . $name;
	$url = strtr(Config::get('database.root'), ['%database' => $dbname]);
}
$url = parse_url($url);

$info['handler'] = $url['scheme'];	
$info['host']= urldecode($url['host']);
$info['port'] = (int)$url['port'];
$info['db'] = substr(urldecode($url['path']), 1);
$info['user'] = urldecode($url['user']);
$info['password']  = isset($url['pass']) ? urldecode($url['pass']) : NULL;

$mysql = new Database_MySQL($info);

$mysqli = new mysqli($info);

function standardize_field_type($type) {
	$type = strtolower($type);
	// 移除多余空格
	$type = preg_replace('/\s+/', ' ', $type); 
	// 去除多级整数的长度说明
	$type = preg_replace('/\b(tinyint|smallint|mediumint|bigint|int)\s*\(\s*\d+\s*\)/', '$1', $type);
	
	return $type;
}


function quote_ident() {
	return function($s) use($mysqli) {
		if (is_array($s)) {
			foreach($s as &$i){
				$i = $quote_ident($i);
			}
			return implode(',', $s);
		}		
		return '`'.$mysqli->escape($s).'`';
	};
};

function quote($s) {
	return function($s) use($mysqli) {
		if(is_array($s)){
			foreach($s as &$i){
				$i=quote($i);
			}			
			return implode(',', $s);
		}
		elseif (is_bool($s) || is_int($s) || is_float($s)) {
			return $s;
		}
		return '\''.$mysqli->escape($s).'\'';
	};
};

function field_sql($key, $field) {
	return sprintf('%s %s%s%s%s'
				, quote_ident($key)
				, $field['type']
				, $field['null']? '': ' NOT NULL'
				, isset($field['default']) ? ' DEFAULT '.quote($field['default']):''
				, $field['auto_increment'] ? ' AUTO_INCREMENT':''
				);
};

$mail = new Email();
$mail->subject("数据结构检测结果报告");
//'maintain@geneegroup.com'
$mail->to(['maintain@geneegroup.com']);
$error = [];

foreach ($schemas as $name => $schema) {
	if ($mysql->table_exists($name)) {
		$schema = ORM_Model::schema($name);
		$fields = (array) $schema['fields'];
		$indexes = (array) $schema['indexes'];
		$field_sql = [];

		$real_fields = $mysql->table_fields($name, TRUE);
		$missing_fields = array_diff_key($fields, $real_fields);
		foreach($missing_fields as $key=>$field) {
			$field_sql[]= '['.$key .'] '.'ADD '.field_sql($key, $field);
		}

		foreach($real_fields as $key=>$curr_field) {
			$field = $fields[$key];
			if ($field) {
				$curr_type = standardize_field_type($curr_field['type']);
				$type = standardize_field_type($field['type']);
				if ( $type !== $curr_type
					|| $field['null'] != $curr_field['null']
					|| $field['default'] != $curr_field['default']
					|| $field['auto_increment'] != $curr_field['auto_increment']) {
					$field_sql[$key] = sprintf('%sCHANGE %s %s'
						, '['.$key .'] '
						, quote_ident($key)
						, field_sql($key, $field));
				}
			}
			elseif ($remove_nonexistent) {
				$field_sql[$key] = sprintf('%sDROP %s', '['.$key .'] ', quote_ident($key) );
			}
		}

		if (count($field_sql)) {
			$error[$name] = $field_sql;
		}

	}
}

$body = sprintf("[%s]站点:SITE_ID=%s, LAB_ID=%s\n\n", Config::get('page.title_default'), $_SERVER['SITE_ID'], $_SERVER['LAB_ID']);

if (count($error)) {
	$content = ['数据结构不正常!'];
	foreach ($error as $name => $field_sql) {
		$content[] = sprintf("Table[%s]目前有以下数据需要处理:\n%s\n", $name, join("\n", $field_sql));
	}
	$body .= join("\n", $content);
}
else {
	$body .= "数据结构正常!";
}

$mail->body($body);
$mail->send();