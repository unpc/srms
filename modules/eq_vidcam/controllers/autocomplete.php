<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function vidcams() {
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
			$selector = "vidcam[name*={$s}]:limit({$start},{$n})";

			$vidcams = Q($selector);
			$vidcams_count = $vidcams->total_count();
		
			if ($start == 0 && !$vidcams_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($vidcams as $vidcam) {
					Output::$AJAX[] = [
						'html'=>(string) V('eq_vidcam:autocomplete/vidcam', ['vidcam'=>$vidcam]),
						'alt'=>$vidcam->id,
						'tip'=>I18N::T('eq_vidcam', '%vidcam', ['%vidcam'=>$vidcam->name]),
					];
				}
//				$rest = $vidcams->total_count() - $vidcams_count;
				if ($start == 95) {
					Output::$AJAX[] = [
						'html' => (string) V('autocomplete/special/rest'),
						'special' => TRUE
					];
				}
			}
		}
		else {
			$selector = "vidcam:limit({$start},{$n})";
			$vidcams = Q($selector);
			$vidcams_count = $vidcams->total_count();
		
			if ($start == 0 && !$vidcams_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($vidcams as $vidcam) {
					Output::$AJAX[] = [
						'html'=>(string) V('eq_vidcam:autocomplete/vidcam', ['vidcam'=>$vidcam]),
						'alt'=>$vidcam->id,
						'tip'=>I18N::T('eq_vidcam', '%vidcam', ['%vidcam'=>$vidcam->name]),
					];
				}
//				$rest = $vidcams->total_count() - $vidcams_count;
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
