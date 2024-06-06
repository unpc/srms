<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function role() {
		$query = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
		if ($query) {
			$query = Q::quote($query);
			$selector = "role[name*={$query}][id>0][weight>=0]";
        }
        else {
            $selector = 'role[id>0][weight>=0]';
        }
			
        $me = L('ME');

        $roles = Q($selector);

        $filter_roles = [];
        foreach($roles as $role) {
            if ($me->is_allowed_to('查看', $role)) {
                $filter_roles[] = $role;
            }
        }

        $roles = $filter_roles;

        $roles_total_count = count($roles);

        if ($start == 0 && !$roles_total_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
			$roles = array_slice($roles, $start, $n);
            foreach ($roles as $role) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/role', ['role'=>$role]),
					'alt' => $role->id,
					'text'  => I18N::T('role', '%role', ['%role'=>H($role->name)]),
				];
            }

           // $rest = $roles_total_count - $roles_count;
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
	}
}
