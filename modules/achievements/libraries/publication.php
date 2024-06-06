<?php

class Publication {

	static function setup_profile() {
		Event::bind('user.view.general.sections', 'Publication::user_general_sections');
	}

	static function user_general_sections($e, $user, $sections) {
		
		$publications = Q("ac_author[user={$user}]<achievement publication");
		
		if (count($publications)) {
			$sections[] = V('achievements:publications/user.general.sections', ['publications'=>$publications]);
		}	
	}
}
