<?php

class Grant_Portion_Model extends ORM_Model {
	
	
	// 递归计算
	/*
	amount = 设置
	expense = Transaction 花费之和
	avail_balance = amount - (expense + 子 amount 之和);
	balance = amount - (expense + (子 amount - 子 balance));
	*/
	
	function recalculate(){
	
		$expense = 0;
		$amount = 0;

		foreach($this->children() as $child){
			$child->recalculate();
			$expense += ($child->amount - $child->balance);
			$amount += $child->amount;
		}

		$db = ORM_Model::db($this->name());	
		$this->expense = (float) $db->value('SELECT SUM(amount) FROM grant_expense WHERE portion_id=%d', $this->id);
		$this->balance = $this->amount - $this->expense - $expense;
		$this->avail_balance = $this->amount - $this->expense - $amount;

		$this->save();
	}

	function delete() {
		if (!$this->id) return FALSE;
		Q("grant_expense[portion={$this}]")->delete_all();
		return parent::delete();
	}
	
	function children() {
		if ($this->id) {
			return Q("grant_portion[parent={$this}]");
		}
	}

	function childrens($has_self = TRUE) {
		$children = $this->children();
		$childs = [];
		foreach($children as $child) {
			$childs = array_merge($childs, $child->childrens());
		}
		
		if ( $has_self ) {
			return array_merge($childs, [$this->id]);
		}
		else {
			return $childs;
		}
	}

	function has_children() {
	
		if ($this->id > 0) {
			$count = Q("grant_portion[parent={$this}]")->total_count();
		}
		return $count > 0;
	}

	function get_render_tree($grant_width) {
		$portion = $this;
		$tree = [];
		$current = $portion;
	   	$parent = $portion->parent;
		while ($parent->id) {
			$children = Q("grant_portion[parent={$parent}]:sort(id A)");
			$current->tmp_prev_amount = 0;
			foreach ($children as $child) {
				if ($child->id===$current->id) break;
				$current->tmp_prev_amount += $child->amount;
			}
			$tree[] = $current;
			$current = $parent;
			$parent = $parent->parent;
		}
		$tops = Q("grant_portion[grant={$portion->grant}][!parent]:sort(id A)");

		$current->tmp_prev_amount = 0;
		foreach ($tops as $top) {
			if ($top->id === $current->id) break;
			$current->tmp_prev_amount += $top->amount;
		}
		$tree[] = $current;
		$tree = array_reverse($tree);
		return $tree;
	}

	function path($sep=':') {
		$path = '';
		if ($this->parent->id) {
			$path .= $this->parent->path($sep) . $sep;
		}
		return $path . $this->name;
	}

}

