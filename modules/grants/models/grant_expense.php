<?php

class Grant_Expense_Model extends Presentable_Model {
	function & links($mode='index') {
		$links = new ArrayIterator;		
		$me = L('ME');

		switch ($mode) {
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => '#',
					'text'  => I18N::T('grants', '修改'),
					'extra'=>'class="blue" q-object="edit_expense" q-event="click" q-static="'.H(['id'=>$this->id]).'" q-src="'.H(URI::url('!grants/grant')).'"',
				];
				$links['delete'] = [
					'url' => '#',
					'text'  => I18N::T('grants', '删除'),
					'extra'=>'class="blue" q-object="delete_expense" q-event="click" q-static="'.H(['id'=>$this->id]).'" q-src="'.H(URI::url('!grants/grant')).'"',
				];
			}
		}
		
		return (array) $links;
	}

	function save($overwrite=FALSE) {
		$ret = parent::save($overwrite);
		if ($ret && $this->id) {
			$this->_update_grant();
		}
		return $ret;
	}

	function delete() {
		$ret = parent::delete();
		if ($ret) {
			$this->_update_grant();
		}
		return $ret;
	}

	private function _update_grant() {
		$grant = $this->grant;
		if ($grant->id) {
			$grant->recalculate();
		}
	}

	function path($sep=':') {
		return $this->portion->path($sep);
	}

}

