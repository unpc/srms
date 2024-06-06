<?php
class Cers_Access {

	static function cers_ACL($e, $user, $perm, $object, $options) {
		switch ($perm) {
			case '管理':
				if($user->access('管理CERS')){
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
        }

        $e->return_value = FALSE;
        return TRUE;
	}

	static function is_accessible($e, $name) {
		if (!L('ME')->is_allowed_to('管理', 'cers')) {
			$e->return_value = false;
			return false;
		}
	}

}