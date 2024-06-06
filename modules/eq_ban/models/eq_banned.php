<?php

class EQ_Banned_Model extends Presentable_Model {

	const unsealing_on = 0;
    const unsealing_up = 1;

    static $status = [
        self::unsealing_on => '封禁中',
        self::unsealing_up => '已解封',
	];

	static $disabled = FALSE;

	function save($overwrite = FALSE) {
		$ret = parent::save($overwrite);
		if ($ret) {
			Log::add(strtr('[eq_ban] %user_name[%user_id]被%operator_name[%operator_id]加入黑名单[%id]: %reason', [
					'%user_name' => $this->user->name,
					'%user_id' => $this->user->id,
					'%operator_name' => L('ME')->name,
					'%operator_id' => L('ME')->id,
					'%reason' => $this->reason,
					'%id' => $this->id
				]), 'banned');
		}
		return $ret;
	}

	function delete() {
		$ret = parent::delete();
		if ($ret) {
			Log::add(strtr('[eq_ban] %user_name[%user_id]被%operator_name[%operator_id]移出黑名单[%id]', [
				'%user_name' => $this->user->name,
				'%user_id' => $this->user->id,
				'%operator_name' => L('ME')->name,
				'%operator_id' => L('ME')->id,
				'%id' => $this->id
				]), 'banned');
		}
		return $ret;
	}

	function & links($mode='eq') {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
			case 'admin':
				if($me->is_allowed_to('编辑全局', $this)) {
					$links['delete'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '解除封禁'),
						'tip' => I18N::T('eq_ban', '解除封禁'),
						'extra' => 'class="blue" q-event="click" q-object="del_ban_admin" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url("!eq_ban/index").'"',
						'weight' => 99,
					];
					$links['edit'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '修改'),
						'tip' => I18N::T('eq_ban', '修改'),
						'extra' => ' class="blue" q-event="click" q-object="edit_ban_admin" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url('!eq_ban/index').'"'
					];
				}
				break;
			case 'group':
				if($me->is_allowed_to('编辑机构', $this)) {
					$links['delete'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '解除封禁'),
						'tip' => I18N::T('eq_ban', '解除封禁'),
						'extra' => 'class="blue" q-event="click" q-object="del_ban_group" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url("!eq_ban/index").'"',
						'weight' => 99,
					];
					$links['edit'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '修改'),
						'tip' => I18N::T('eq_ban', '修改'),
						'extra' => ' class="blue" q-event="click" q-object="edit_ban_group" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url('!eq_ban/index').'"'
					];
				}
				break;
			case 'eq':
				if($me->is_allowed_to('编辑仪器', $this)) {
					$links['delete'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '解除封禁'),
						'tip' => I18N::T('eq_ban', '解除封禁'),
						'extra' => 'class="blue" q-event="click" q-object="del_ban_eq" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url("!eq_ban/index").'"',
						'weight' => 99,
					];
					$links['edit'] = [
						'url' => '#',
						'text'  => I18N::T('eq_ban', '修改'),
						'tip' => I18N::T('eq_ban', '修改'),
						'extra' => ' class="blue" q-event="click" q-object="edit_ban_eq" q-static="'.H(['banned_id'=>$this->id]).'" q-src="'.URI::url('!eq_ban/index').'"'
					];
				}
				break;
		}

		return (array)$links;
	}

}
