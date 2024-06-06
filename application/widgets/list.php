<?php
class List_Widget extends Widget {

	public $columns;
	public $rows;
	//Item_Widget array
	public $items;
	

	function __construct($vars){
		parent::__construct('list', $vars);
	}

	function add_columns(array $columns) {
		foreach ($columns as $key => $column) {
			$this->add_column($key, $column);
		}
	}
	function add_rows(array $rows) {
		foreach ($rows as $row) {
			$this->add_row($row);
		}
	}

	function add_column($key, $column) {
		$this->columns[$key] = (array) $column;
	}

	function add_row($row) {
		$this->rows[] = (array) $row;
	}

	function add_row_withkey($row, $key) {
		$this->rows[$key] = (array) $row;
	}
	function add_item($item)
	{
		if(!$item instanceof Item_Widget)
		{
			throw Exception('illegal type');
		}
		$this->items[]=$item;
	}
	function __toString() {
		// 清楚上次的缓冲
		$this->ob_clean();
        $output = parent::__toString();
		return $output;
	}

}
