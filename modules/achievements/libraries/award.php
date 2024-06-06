<?php

class Award {

	static function setup_profile() {
		Event::bind('user.view.general.sections', 'Award::user_general_sections');
	}

	static function user_general_sections($e, $user, $sections) {
		
		$awards = Q("ac_author[user={$user}]<achievement award");
		
		if (count($awards)) {
			$sections[] = V('achievements:awards/user.general.sections', ['awards'=>$awards]);
		}	
	}
}
