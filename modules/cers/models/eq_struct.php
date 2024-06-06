<?php

class EQ_Struct_Model extends Presentable_Model {

	function delete() {
		$users = Q("$this<incharge user");
		foreach ($users as $user) {
			$this->disconnect($user, 'incharge');
		}

		parent::delete();
	}

}