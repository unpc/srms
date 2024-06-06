<?php

class Autocomplete_Controller extends AJAX_Controller { 

	function struct() {
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
			$structs = Q("eq_struct[name*={$s}]:limit({$start},{$n})");
		}
		else {
			$structs = Q("eq_struct:limit({$start},{$n})");
		}
		$struct_count = $structs->total_count();

		if ($start == 0 && !$struct_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($structs as $struct) {
				Output::$AJAX[] = [
					'html' => (string) V('eq_struct:autocomplete/struct', ['struct'=>$struct]),
					'alt' => $struct->id,
					'text' => $struct->name
				];
			}
//			$rest = $structs->total_count() - $struct_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
}
