<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function user($mode) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
		
		if ($s) {
			$s = Q::quote($s);
			switch ($mode) {
				case 'admin':
					// $selector = "user[name*={$s}|name_abbr*={$s}][!hidden][atime]:not(eq_banned[!object_name] user):limit({$start},{$n})";
					// break;
				case 'group':
				case 'eq':
					$selector = "user[name*={$s}|name_abbr*={$s}][!hidden][atime]:limit({$start},{$n})";
					break;
			}
		}
		else {
			switch ($mode) {
				case 'admin':
					// $selector = "user[!hidden][atime]:not(eq_banned[!object_name] user):limit({$start},{$n})";
					// break;
				case 'group':
				case 'eq':
					$selector = "user[!hidden][atime]:limit({$start},{$n})";
					break;
			}
		}
		$users = Q($selector);
		$users_count = $users->total_count();

		if ($start == 0 && !$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {			
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/user', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
				];
			}
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function equipment() {
		$me = L('ME');
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}

		$n = 5;
		if($start == 0) $n = 10;

		if($start >= 100) return;

		if ($s) {
			$s = Q::quote($s);
			$equipments = Q("$me<@(incharge|contact) equipment[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
		}
		else {
			$equipments = Q("$me<@(incharge|contact) equipment:limit({$start},{$n})");
		}
		$equipments_count = $equipments->total_count();

		if ($start == 0 && !$equipments_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($equipments as $equipment) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/equipment', ['equipment'=>$equipment]),
					'alt' => $equipment->id,
					'text' => $equipment->name,
					'data' => json_encode($equipment->contacts()->to_assoc('id', 'name'))
				];
			}

			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
}
