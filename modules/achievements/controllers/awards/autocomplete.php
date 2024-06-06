<?php

class Awards_Autocomplete_Controller extends AJAX_Controller {
		
	function tags() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		$n = 5;
		if($start == 0) $n = 10;
		if($start >= 100) return;
		$root = Tag_Model::root('achievements_award');
		
		if ($s) {
			$s = Q::quote($s);
			$all_tags = Q("tag_achievements_award[root={$root}][name*={$s}]");
			$tags = $all_tags->limit($start, $n);

			$all_tags_count = $all_tags->total_count();
			$tags_count = $tags->length();

			$all_tags = (array) $all_tags->to_assoc('name', 'id');
			$tags = (array) $tags->to_assoc('name', 'id');
		}
		else {
			$all_tags = Q("tag_achievements_award[root={$root}]");
			$tags = $all_tags->limit($start, $n);

			$all_tags_count = $all_tags->total_count();
			$tags_count = $tags->length();

			$all_tags = (array) $all_tags->to_assoc('name', 'id');
			$tags = (array) $tags->to_assoc('name', 'id');
		}
		
//		$rest = $all_tags_count - $tags_count;
		
		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag => $reserved){
				Output::$AJAX[] = [
					'html' => (string) V('achievements:publications/tags', ['tag'=>$tag]),
					'alt' => $tag,
					'text' => $tag,
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
}
