<?php

class Search_Iterator extends ORM_Iterator {

	static protected $model_name = '';

	protected $sphinx;
	protected $sphinx_options;
	protected $sphinx_SQL;
	protected $sphinx_limit_SQL;
	protected $sphinx_option_SQL;

	function __construct($opt=NULL) {
		$this->sphinx = Database::factory('@sphinx');
	}

	private $_search_check_query = FALSE;
	protected function check_query($scope='fetch') {
		if ($this->isset_query($scope)) return $this;

		switch($scope) {
		// case '*':

		// 	break;
		case 'count':
			$SQL = $this->sphinx_SQL . ' LIMIT 1';
			$query = $this->sphinx->query($SQL);

			$meta = $this->sphinx->query('SHOW META');
			$total_found = 0;
			foreach ($meta->rows() as $row) {
				if ($row->Variable_name == 'total_found') {
					$total_found = (int) $row->Value;
					break;
				}
			}

			$this->count = $total_found;
			break;
		default:
			$SQL = $this->sphinx_SQL;
			// 增加 order by 子句(xiaopei.li@2012-04-26)
			// http://sphinxsearch.com/docs/2.0.4/sphinxql-select.html
			if ($this->sphinx_order_by_sql) $SQL .= ' '.$this->sphinx_order_by_sql;
			if ($this->sphinx_limit_SQL) $SQL .=  ' '.$this->sphinx_limit_SQL;
			if ($this->sphinx_option_SQL) $SQL .= ' '.$this->sphinx_option_SQL;
			$query = $this->sphinx->query($SQL);

			$this->objects = [];
			if ($query) while($row = $query->row()) {
				$object = O(static::$model_name, $row->id);

				if ($object->id) {
					$this->objects[$object->id] = $object;
				}
			}

			$meta = $this->sphinx->query('SHOW META');
			$total_found = 0;
			foreach ($meta->rows() as $row) {
				if ($row->Variable_name == 'total_found') {
					$total_found = (int) $row->Value;
					break;
				}
			}

			$this->count = $total_found;
			$this->length = count($this->objects);
			$this->current_id = key($this->objects);
		}

		$this->set_query($scope, TRUE);

		return $this;
	}

	function sphinx_results() {
		
		$SQL = $this->sphinx_SQL;
		$results = $this->sphinx->query($SQL)->rows();

		return $results;
	}

	function limit() {
		$args = func_get_args();
		$args = array_slice($args, 0, 2);
		$this->sphinx_limit_SQL = 'LIMIT '.implode(', ', $args);
		return $this;
	}

	function empty_index_of($index_name) {
		$sphinx = Database::factory('@sphinx');

		// error_log("deleting $index_name sphinx index");

		$SQL = 'select * from `' . $index_name . '` limit 1000';

		do {
			// http://sphinxsearch.com/docs/2.0.4/sphinxql-select.html
			// LIMIT ... an implicit LIMIT 0,20 is present by default. (xiaopei.li@2012-04-23)
			$query = $sphinx->query($SQL);

			$ids = [];
			if ($query) while ($row = $query->row()) {
				$ids[] = $row->id;
			}
			$DEL_SQL = 'DELETE FROM `' . $index_name . '` WHERE id IN (' . join(',', $ids) . ')';
			// error_log($DEL_SQL);
			$sphinx->query($DEL_SQL);
		}
        while (is_object($query) && $query->count());
	}

	static function get_index_name() {
		// 参考: http://php.net/manual/en/language.oop5.late-static-bindings.php
		if (!static::$model_name) {
			throw new Exception;
		}

		return SITE_ID . '_' . LAB_ID . '_' . static::$model_name;
	}

}
