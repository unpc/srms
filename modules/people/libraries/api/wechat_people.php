<?php

class API_Wechat_People extends API_Wechat{

	protected static $errors = [
        401 => 'Access Denied',
        404 => 'Can Not Find Item',
        500 => 'Internal Error',
        501 => 'Can Not Item'
    ];

	function getInfo($token) {
		if(!$this->checkToken($token)) {
			throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];

		$u = O('user', array('id'=>$criteria['id']));

		$tag = $u->group;
		$group = $tag->id ? array($tag->name) : null ;
		while($tag->parent->id && $tag->parent->root->id){
			array_unshift($group, $tag->parent->name);
			$tag = $tag->parent;
		}

		$roles = L('ROLES');
		$role_names = array();
		foreach ((array)$u->roles() as $rid) {
			$role = $roles[$rid];
			if ($role) {
				$role_names[$rid] = $role->name;
			}
		}

		$info = array(
				'username' => $u->token,
				'email' => $u->email,
				'name' => $u->name,
				'gender' => $u->gender,
				'card_no' => $u->card_no,
				'ctime' => $u->ctime,
				'atime' => $u->atime,
				'phone' => $u->phone,
				'address' => $u->address,
				'group' => $group,
				'member_type' => $u->member_type,
				'member_type_label' => $u->get_member_label($u->member_type),
				'creator' => $u->creator->name,
				'auditor' => $u->auditor->name,
				'ref_no' => $u->ref_no,
				'major' => $u->major,
				'organization' => $u->organization,
				'lab_owner' => Q("$u<pi lab")->total_count() ? TRUE : FALSE,
				'roles' => $role_names,
				'icon128_url' => $u->icon_url('128'),
				'follow_user' => $u->get_follows_count('user'),
				'follow_equipment' => $u->get_follows_count('equipment'),
				);

		return $info;
	}
}
