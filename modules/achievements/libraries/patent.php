<?php

class Patent {

	static function setup_profile() {
		Event::bind('user.view.general.sections', 'Patent::user_general_sections');
	}

	static function user_general_sections($e, $user, $sections) {
		
		$patents = Q("ac_author[user={$user}]<achievement patent");
		
		if (count($patents)) {
			$sections[] = V('achievements:patents/user.general.sections', ['patents'=>$patents]);
		}	
	}
}
