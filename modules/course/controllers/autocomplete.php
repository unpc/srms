<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function school_term() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $selector = 'school_term';
        if ($s) {
            $s = Q::quote($s);
            $selector .= "[year*={$s}]";
        }
        $school_terms = Q($selector)->limit($start, $n);
        $school_terms_count = $school_terms->total_count();
        
        if ($start == 0 && !$school_terms_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($school_terms as $school_term) {
                Output::$AJAX[] = [
                    'html'=>(string)V('autocomplete/school_term',['school_term'=>$school_term]),
                    'alt' => $school_term->id,
                    'tip'=>I18N::T('schedule','%name',['%name'=>$school_term->year."(".School_Term_Model::$TYPES[$school_term->term].")",]),
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