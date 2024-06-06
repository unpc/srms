<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function equipment() {
		
		$me = L('ME');
		$is_admin = $me->access('管理所有内容');
		$is_group_admin = $me->access('添加/修改下属机构的仪器');
		
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$no_inserver = EQ_Status_Model::NO_LONGER_IN_SERVICE;
		
		if ($s) {
			$s = Q::quote($s);
			$selector = "equipment[name*={$s}|name_abbr*={$s}][status!={$no_inserver}]:limit({$start},{$n})";
		}
		else {
			$selector = "equipment[status!={$no_inserver}]:limit({$start},{$n})";
		}

		if($is_group_admin && !$is_admin) $selector = "{$me->group} {$selector}";

		$equipments = Q($selector);
		$equipments_count = $equipments->total_count();
		
		if ($start == 0 && !$equipments_count) {
			Output::$AJAX[] = array(
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			);
		}
		else {
			foreach ($equipments as $equipment) {
				$users = Q("{$equipment} user.incharge");
				$incharge_arr = array();
				foreach ($users as $user) {
					$incharge_arr[] = H($user->name);
				}
				$incharges = join(', ', $incharge_arr);

				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/equipment', array('equipment'=>$equipment)),
					'alt' => $equipment->id,
					'text' => H($equipment->name),
					'data' => array(
						'incharges' => $incharges,
					),
				);
			}


			if ($start == 95) {
				Output::$AJAX[] = array(
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				);
			}
		}
	}

}
