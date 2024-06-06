<?php

class Eq_Wait_Join_Model extends Presentable_Model {

	const NO_USE = 0;
	const COMPLETE = 1;

	static $status = [
		self::NO_USE => '未使用',
		self::COMPLETE => '已完成',
	];

	static $ref_status = [
		self::NO_USE => 'no_use',
		self::COMPLETE => 'complete',
	];

	function & links($mode = 'index') {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'index':
		default:
			if ($me->is_allowed_to('管理预约等待', $this->equipment) &&
				$this->status == Eq_Wait_Join_Model::NO_USE) {
				$links['mark'] = [
					'url' => '#',
					'text'  => I18N::T('eq_wait_join', '标记完成'),
					'extra' => 'class="blue" q-object="mark_waiter" q-event="click" q-src="'.URI::url('!eq_wait_join').'" q-static="'.H(['id' => $this->id]).'"',
				];
				$links['delete'] = [
					'url' => '#',
					'text'  => I18N::T('eq_wait_join', '删除'),
					'extra' => 'class="blue" q-object="delete_waiter" q-event="click" q-src="'.URI::url('!eq_wait_join').'" q-static="'.H(['id' => $this->id]).'"',
				];
			}
			break;
		}

		return (array) $links;
	}

}
