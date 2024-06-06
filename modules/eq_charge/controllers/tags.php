<?php

class Tags_Controller extends AJAX_Controller {
	
	function index($eid) {
		$equipment = O('equipment', $eid);
		if ($equipment->id) {
			$tags = (array) $equipment->charge_tags;
			if(count($tags) > 0) {
				foreach($tags as $tag){
					Output::$AJAX[] = [
						'html' => (string) V('equipments:autocomplete/tag', ['tag'=>$tag]),
						'alt' => $tag,
						'text' => $tag,
					];				
				}
			}
		}
	}
	
}
