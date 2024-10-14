<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function material($type=0) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $selector = "material[type={$type}]";
        if ($s) {
            $s = Q::quote($s);
            $selector .= "[name*={$s}]";
        }

        $materials = Q($selector)->limit($start, $n);
        $material_count = $materials->total_count();
        
        if ($start == 0 && !$material_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($materials as $material) {
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/material', ['material' => $material]),
                    'alt' => $material->id,
                    'tip' => I18N::T('notice','%name', ['%name' => $material->name,]),
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