<?php

class EQ_Current_Access {

	static function equipment_ACL($e, $me, $perm_name, $equipment, $options) {
		if (!$equipment->id) return;
		if (!$me->id) return;

		switch($perm_name) {
		case '修改能耗设置':
			if ($me->access('修改所有仪器的能耗设置')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		default:
		}
	
	}

}
