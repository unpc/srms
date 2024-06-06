<?php

class Exhibition {

    static function is_accessible($e, $name) {
		$me = L('ME');

		if (!in_array($me->token, Config::get('lab.admin'))) {
			$e->return_value = FALSE;
			return FALSE;
		}
	}
}