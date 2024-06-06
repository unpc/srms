<?php
class Lims_Account {
	static function comment_ACL($e, $user, $perm_name, $thing, $options) {
		$e->return_value =TRUE;
	}

	static function lims_account_saved($e, $account, $old_data, $new_data) {
		if ($new_data['mod_enable'] != $old_data['mod_enable']) {
			$account->mod_changed = TRUE;
			$account->save();
		}
	}
}
