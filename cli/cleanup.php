#!/usr/bin/env php
<?php

//清理各种操作中可能造成的垃圾

require "base.php";

function repair_relation($oname1, $oname2) {
	$oname1 = ORM_Model::real_name($oname1);
	$oname2 = ORM_Model::real_name($oname2);
	if (!$oname1 || !$oname2) return;

	echo "检查 $oname1 - $oname2 表\n";
	$db = Database::factory();
	$rs = $db->query("SELECT * FROM _r_{$oname1}_{$oname2}");
	while ($row = $rs->row()) {
		$o1 = O($oname1, $row->id1);
		$o2 = O($oname2, $row->id2);
		if (!$o1->id || !$o2->id) {
			echo "发现不匹配: 关系 $oname1#{$row->id1} - $oname2#{$row->id2}\n";
			$db->query("DELETE FROM _r_{$oname1}_{$oname2} WHERE id1={$row->id1} AND id2={$row->id2}");
		}	
	}
}

function repair_properties($oname) {
	$oname = ORM_Model::real_name($oname);
	if (!$oname) return;
	$db = Database::factory();
	$rs = $db->query("SELECT * FROM _p_$oname");
	while ($row = $rs->row()) {
		if ($row->id < 0) continue;
		$object = O($oname, $row->id);
		if ($object->id != $row->id) {
			echo "发现不匹配: $oname#$row->id 扩展属性\n";
			$db->query("DELETE FROM _p_$oname WHERE id={$row->id}");
		}
	}
}

function repair_object($oname) {
	$oname = ORM_Model::real_name($oname);
	if (!$oname) return;

	$schema = ORM_Model::schema($oname);
	$oattrs = [];
	foreach ((array) $schema['fields'] as $key => $field) {
		if ($field['type'] == 'object') {
			$oattrs[] = $key;
		}
	}
	if (count($oattrs) > 0) {
		foreach (Q($oname) as $object) {
			foreach ($oattrs as $key) {
				$oattr = $object->get($key);
				if ($oattr->id != $object->get($key.'_id')) {
					echo "不匹配: $object 属性 $oattr\n";
				}
			}
		}
	}
}

try {

	$db = Database::factory();

	$tables = [];
	$rs = $db->query('SHOW TABLES');
	while ($r = $rs->row('num')) {
		$tables[$r[0]]=$r[0];
	}	

	foreach ($tables as $table) {
		if ($table[0] == '_') {
			$prefix = substr($table, 1, 2);
			if ($prefix == 'r_') {
				$units = explode('_', substr($table, 3));
				$count = count($units);
				if ($count > 2) {
					for ($i=1; $i<$count; $i++) {
						repair_relation(implode('_', array_slice($units, 0, $i)), implode('_', array_slice($units, $i)));
					}	
				}
				elseif ($count == 2) {
					repair_relation($units[0], $units[1]);
				}
			}
			elseif ($prefix == 'p_') {
				repair_properties(substr($table, 3));
			}
		}
		else {
			repair_object($table);
		}
	}

}
catch (Error_Exception $e) {
	echo $e->getMessage()."\n";
}

