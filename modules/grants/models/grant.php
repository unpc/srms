<?php

class Grant_Model extends Presentable_Model {
	

	protected $object_page = [
		'view'=>'!grants/grant/index.%id[.%arguments]',
		'edit'=>'!grants/grant/edit.%id[.%arguments]',
		'delete'=>'!grants/grant/delete.%id',
	];
	
	function & links($mode='index') {
		$links = new ArrayIterator;		
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('grants', '修改'),
					'extra'=>'class="button button_edit"',
				];
			}
			break;
		default:
			if ($me->is_allowed_to('修改', $this)) {	
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('grants', '修改'),
					'extra'=>'class="blue"',
				];
			}
		}
		
		return (array) $links;
	}

	function recalculate(){
	
		$amount = 0;
		$expense = 0;
		
		foreach($this->children() as $child){
			$child->recalculate();
			$amount += $child->amount;
			$expense += $child->amount - $child->balance;
		}
	
		$db = ORM_Model::db($this->name());	
		$no_portion_expense = (float) $db->value('SELECT SUM(amount) FROM grant_expense WHERE portion_id=0 AND grant_id=%d', $this->id);
		$this->expense = $expense + $no_portion_expense; 
		$this->balance = $this->amount - $this->expense;
		$this->avail_balance = $this->amount - $amount - $no_portion_expense;
		$this->save();
	}
	
	function delete(){
		
		Q("grant_portion[grant={$this}]")->delete_all();
		return parent::delete();
	}

	function children() {
		if ($this->id) {
			return Q("grant_portion[grant={$this}][!parent]");
		}
	}

}

