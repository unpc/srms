<?php

class EQ_Announce_Model extends Presentable_Model {
	function & links ($mode = 'index') {
		switch ($mode) {
		case 'index':
		default:
			$me = L('ME');
			if ($me->is_allowed_to('修改公告', $this->equipment)) {
				$links['edit'] = [
					'url' => NULL,
					'tip' => I18N::T('equipments', '修改'),
					'text'  => I18N::T('equipments', '修改'),
					'extra' => 'class="blue" q-event="click" q-object="edit_announce"'.
					' q-static="'.H(['a_id'=>$this->id]).
					'" q-src="'.URI::url("!equipments/announce").'"',
						];
			}
			if ($me->is_allowed_to('删除公告', $this->equipment)) {
				$links['delete'] = [
					'url' => NULL,
					'tip' => I18N::T('equipments', '删除'),
					'text'  => I18N::T('equipments', '删除'),
					'extra' => 'class="blue" q-event="click" q-object="delete_announce"'.
					' q-static="'.H(['a_id'=>$this->id]).
					'" q-src="'.URI::url("!equipments/announce").'"',
						];
			}
			break;
		}
		return (array) $links;

	}

	function delete() {
		if ($this->id) {
			foreach (Q("$this<read user") as $user) {
				$user->disconnect($this, 'read');
			}
		}
		return parent::delete();
	}
}
