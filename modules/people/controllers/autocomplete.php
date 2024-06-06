<?php
class Autocomplete_Controller extends AJAX_Controller {
	
	function timezone($lab_id=0) {
		$key = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}

		$n = 5;
		if($start == 0) $n = 10;

		if($start >= 100) return;

		if ($key) {	
			$timezones = timezone_identifiers_list();

			$key = addcslashes($key, '/');

		    $timezones =  array_filter($timezones, function($value) use($key) {return preg_match('/'.$key. '/i', $value);});

			if ($start == 0 && !count($timezones)) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				$total_count = count($timezones);

				$timezones = array_slice($timezones,$start, $n);

				foreach ($timezones as $timezone) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/timezone', ['timezone'=>$timezone]),
						'alt' => $timezone,
						'text' => $timezone,
					];
				}
			
				if ($start== 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest', ['rest' => $rest]),
						'special' => TRUE
					];
				}
			}
		}
		else{
			$timezones = timezone_identifiers_list();
			$total_count = count($timezones);
			$timezones = array_slice($timezones,$start, $n);
			foreach ($timezones as $timezone) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/timezone', ['timezone'=>$timezone]),
					'alt' => $timezone,
					'text' => $timezone,
				];
			}
			
			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest', ['rest' => $rest]),
					'special' => TRUE
				];
			}
		}		
	}
}
