<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function user() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if ($s) {
			$users = Q("user[name*={$s}|name_abbr*={$s}][atime]:limit({$start},{$n})");
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
						'html' => (string) V('application:autocomplete/user', ['user'=>$user]),
						'alt' => $user->id,
						'text' => $user->friendly_name(),
					];
				}
//				$rest = $users->total_count() - $users_count;				
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		}
	}
}
