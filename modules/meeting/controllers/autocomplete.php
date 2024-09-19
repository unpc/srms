<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function meeting() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $selector = 'meeting';
        if ($s) {
            $s = Q::quote($s);
            $selector .= "[name*={$s}]";
        }

        $meetings = Q($selector)->limit($start, $n);
        $meetings_count = $meetings->total_count();
        
        if ($start == 0 && !$meetings_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($meetings as $meeting) {
                Output::$AJAX[] = [
                    'html'=>(string)V('autocomplete/meeting',['meeting'=>$meeting]),
                    'alt' => $meeting->id,
                    'tip'=>I18N::T('schedule','%name',['%name'=>$meeting->name,]),
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
    
    function user_tags($id = 0) {
        $object = O('meeting', $id);
        if (!$object->id || !($object->tag_root instanceof Tag_Model)) return;

        $tag_root = $object->get_root();
        $root = Tag_Model::root('meeting_user_tags');
        
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));

        $start = $st ? : 0;
        if ($start >= 100) return;

        $n = $start == 0 ? 10 : 5;
        
        $selector = "tag_meeting_user_tags[root={$root}|root={$tag_root}]";
        if ($s) {
            $s = Q::quote($s);
            $selector .= "[name*={$s}]";
        }
        $selector .= ":limit({$start},{$n})";
        $tags = Q($selector);

        if ($start == 0 && !$tags->total_count()) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($tags as $tag){
                Output::$AJAX[] = [
                    'html' => (string) V('meeting:autocomplete/user_tag', ['tag' => $tag]),
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

    function tag_room() {
        $s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;	
		$n = 5;
		if($start == 0) $n = 10;
		$root = Tag_Model::root('room', '空间分组');
		if ($s) {
			$s = Q::quote($s);
			$tags = Q("tag_room[root={$root}][name*={$s}]:limit({$start},{$n})");
		}
		else {
			$tags = Q("tag_room[root={$root}]:limit({$start},{$n})");
		}
		
		$tags_count = $tags->total_count();
		if ($start == 0 && !$tags_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach($tags as $tag){
				Output::$AJAX[] = [
					'html' => (string) V('meeting:autocomplete/user_tag', ['tag' => $tag]),
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
						'html' => (string) V('autocomplete/vidcam', ['vidcam' => $vidcam]),
						'alt' => $vidcam->id,
						'tip' => I18N::T('meeting', '%vidcam', ['%vidcam' => $vidcam->name]),
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
						'html' =>(string) V('autocomplete/vidcam', ['vidcam' => $vidcam]),
						'alt' => $vidcam->id,
						'tip' => I18N::T('meeting', '%vidcam', ['%vidcam' => $vidcam->name]),
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

}