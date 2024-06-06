<?php

class Table_Widget extends Widget {

	public $columns;
    public $sub_columns; // 列的表头(用于制作双表头)
	public $rows;

	function __construct($vars){
		parent::__construct('table', $vars);
	}

	function add_columns(array $columns) {
		foreach ($columns as $key => $column) {
			$this->add_column($key, $column);
		}
	}

    function add_sub_columns(array $columns) {
        foreach($columns as $key => $column) {
            $this->add_sub_column($key, $column);
        }
    }

    function add_sub_column($key, $column) {
        $this->sub_columns[$key] = $column;
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
		$name = $this->vars['name'];
		$row = Event::trigger("{$name}_table_row.prerender table_row.prerender", $this, $row) ?: $row;
		$this->rows[] = (array) $row;
	}

	function add_row_withkey($row, $key) {
		$this->rows[$key] = (array) $row;
	}

	function columns_invisible() {
		$this->columns_invisible = TRUE;
	}

	function count_visible_filters() {
		$count = 0;
		foreach ((array) $this->columns as $key => $column) {
			if (!$column['invisible'] && (string) $column['filter']['value']) {
				$count++;
			}
		}
		return $count;
	}

	function count_filters() {
		$count = 0;
		foreach ((array) $this->columns as $key => $column) {
			if ((string) $column['filter']['value']) {
				$count++;
			}
		}
		return $count;
	}

    function count_visible_columns() {
        $count = 0;
        foreach ((array) $this->columns as $key => $column) {
            if (!$column['invisible']) {
                ++ $count;
            }
        }

        //如果存在sub_columns
        //需要重新进行计算
        foreach((array) $this->sub_columns as $key=> $columns) {
            if (array_key_exists($key, $this->columns)) $count += count($columns) - 1;
        }
        return $count;
    }

	/**
		* @brief 返回整行的视图
		*
		* @return
	 */
	function row($index) {
		//hook相应的事件 修改columns或者rows
		$name = $this->vars['name'];
		Event::trigger("{$name}_table.prerender table.prerender", $this);

		$this->extra_class = (string) $this->extra_class;
		$this->rows = (array) $this->rows;
		$this->form_url = $this->form_url ?: URI::url('');
		$this->visible_count = $this->count_visible_columns();
		// $this->filters_count = $this->count_filters();
		return V('widgets/table/row', ['table'=>$this, 'row'=>$this->rows[$index]]);
	}

	function __toString() {

		// 清楚上次的缓冲
		$this->ob_clean();
		// hook相应的事件 修改columns或者rows
		$name = $this->vars['name'];
		Event::trigger("{$name}_table.prerender table.prerender", $this);

		$output = parent::__toString();
		$new_output = Event::trigger("{$name}_table.postrender table.postrender", $this, $output);
		return $new_output?:$output;
	}

}
