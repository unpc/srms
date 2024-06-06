<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function labs() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;

		if($start >= 100) return;

		/*
		NO.TASK#312(guoping.zhang@2011.01.07)
		查询限制数量：10
		*/
		if ($s) {
            $s = Q::quote($s);
            $selector = "lab[atime][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
        }else {
            $selector = "lab[atime]:limit({$start},{$n})";
        }

        $labs = Q($selector);
        $labs_count = $labs->length();

        if ($start == 0 && !$labs_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach($labs as $lab) {
                Output::$AJAX[] = [
                    'html'=>(string) V('eq_empower:autocomplete/lab',['lab'=>$lab]),
                    'alt'=>$lab->id,
                    'tip'=>I18N::T('entrance','%lab',['%lab'=>$lab->name]),
                ];
            }
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('eq_empower:autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }
}
