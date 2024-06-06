<?php

class Assets {

    static function is_accessible($e, $name) {
		
		$me = L('ME');

		if (!in_array($me->token, Config::get('lab.admin')) && !$me->access('管理资产同步')) {
			$e->return_value = FALSE;
			return FALSE;
		}
	}
}

