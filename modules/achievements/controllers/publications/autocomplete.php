<?php

class Publications_Autocomplete_Controller extends AJAX_Controller {
		
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
		$root = Tag_Model::root('achievements_publication');

		if ($s) {
			$s = Q::quote($s);

			$all_tags = Q("tag_achievements_publication[root={$root}][name*={$s}]");
		}
		else {
			$all_tags = Q("tag_achievements_publication[root={$root}]");
		}

		$tags = $all_tags->limit($start, $n);
		$tags_count = $tags->length();
		
		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag){
				$tag_name = join(' >> ', array_column($tag->path, '1'));
				Output::$AJAX[] = [
					'html' => (string) V('achievements:publications/tags', ['tag'=>$tag_name]),
					'alt' => $tag->id,
					'text' => $tag->name,
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
