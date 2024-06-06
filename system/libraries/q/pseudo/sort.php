<?php

class Q_Pseudo_Sort implements Q_Pseudo {

	static $guid = 0;
	
	const SORT_PATTERN = '/([\w\pL\._-]+)\s*(DESC|ASC|↓|D|A|↑)?(\s*,\s*)?/';

	private $_query;

	function __construct($query) {
		$this->_query = $query;
	}

	function process($selector) {
		$query = $this->_query;
		if (preg_match_all(self::SORT_PATTERN, $selector, $parts, PREG_SET_ORDER)) {
			$db = $query->db;
			$tables_sorts = [];
			foreach($parts as $part){
				$field_str = $part[1];
				$field_arr = explode('.', $field_str);
				$field = array_pop($field_arr);
				$table = count($field_arr) ? $query->alias[array_pop($field_arr)] : $query->table;
				$order = $part[2];
				$order=preg_match('/^↓|D|DESC$/', $order) ? 'DESC':'ASC';
				$query->order_by[] = $db->make_ident($table, $field).' '.$order;
				$tables_sorts[$table][] = $field;
			}
			foreach ($tables_sorts as $table => $field_arr) {
				$name = $query->table_name[$table];
				$fields = $query->fields($name);
				if (isset($fields['id']) && !in_array('id', $field_arr)) {
					$query->order_by[] = $db->make_ident($table, 'id').' DESC';
				}
			}
		}
	}
	
}
