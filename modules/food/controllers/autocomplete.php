<?php

class Autocomplete_Controller extends AJAX_Controller {

	function food() {
		$s = Input::form('s');
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if ($s) {
			$s = Q::quote($s);
			$foods = Q("food[name*={$s}]:limit({$start},{$n})");
			$foods_count = $foods->length();
			if ($start == 0 && !$foods_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($foods as $food) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/food', ['food' => $food]),
						'alt' => $food->id,
						'text' => $food->name,
					];
				}
//				$rest = $foods->total_count() - $foods_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest', ['rest' => $rest]),
						'special' => TRUE
					];
				}
			}
		}
	}
}
