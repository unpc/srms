<?php

class User_Violation_Record_Model extends Presentable_Model {

	function save($overwrite = FALSE) {
		return parent::save($overwrite);
    }
    

    function & links($mode='eq') {
		$links = new ArrayIterator;
        $me = L('ME');
        switch ($mode) {
            case 'eq':
				if ($me->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
                    $links['delete'] = [
                        'url' => '#',
                        'text' => I18N::T('eq_ban', '删除违规记录'),
                        'extra' => 'class="blue" q-event="click" q-object="del_violate_eq" q-static="'.H(['violate_id'=>$this->id]).'" q-src="'.URI::url("!eq_ban/index").'"',
                        'weight' => 99,
                    ];
                    $links['edit'] = [
                        'url' => '#',
                        'text' => I18N::T('eq_ban', '修改'),
                        'extra' => ' class="blue" q-event="click" q-object="edit_violate_eq" q-static="'.H(['violate_id'=>$this->id]).'" q-src="'.URI::url('!eq_ban/index').'"'
                    ];
                }
				break;
		}

		return (array)$links;
	}
}
