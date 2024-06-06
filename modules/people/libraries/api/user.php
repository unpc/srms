<?php

class API_User extends API_Common {

    private function get_user_labs($user) {
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab_ids = [];
            foreach (Q("$user lab") as $lab) {
                $lab_ids[$lab->id] = $lab->name;
            }
            return $lab_ids;
        }
        else {
            $lab = Q("$user lab")->current();
            return [$lab->id => $lab->name];
        }
    }

    function get_user($id) {
        $this->_ready('user');

        if (is_numeric($id)) {
            $user = O('user', $id);
        }
        elseif (strpos($id, '|')) {
            $user = O('user', ['token' => $id]);
        }

        if (!$user->id) {
            $user = Event::trigger('get_user_from_sec_card', $id) ? : O('user', ['card_no' => $id]);
        }

        if (!$user->id) {
            $user = O('user', ['ref_no' => $id]);
        }

        if (!$user->id) return FALSE;

        $tag = $user->group;
        $group = $tag->id ? [$tag->name] : NULL ;
        while($tag->parent->id && $tag->parent->root->id){
            array_unshift($group, $tag->parent->name);
            $tag = $tag->parent;
        }

        $roles = L('ROLES');
        $role_names = [];
        foreach ((array)$user->roles() as $rid) {
            $role = $roles[$rid];
            if ($role) {
                $role_names[$rid] = $role->name;
            }
        }

        $info = new ArrayIterator([
            'id' => $user->id,
            'token' => $user->token,
            'avatar' => $user->icon_url('128'),
            'name' => $user->name,
            'name_abbr' => $user->name_abbr,
            'gender' => $user->gender,
            'member_type' => $user->member_type,
            'member_type_label' => $user->get_member_label($user->member_type),
            'group' => $group,
            'group_path' => $user->group->path,
            'group_id' => $user->group->id ? : 0,
            'ref_no' => $user->ref_no,
            'major' => $user->major,
            'organization' => $user->organization,
            'dto' => $user->dto,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'card_no' => $user->card_no,
            'labs' => $this->get_user_labs($user),
            'roles' => $role_names,
            'is_admin' => $user->access('管理所有内容') ? TRUE : FALSE,
            'equipments' => Q("$user<incharge equipment")->to_assoc('id', 'id'),
            'creator' => $user->creator->name,
            'auditor' => $user->auditor->name,
            'atime' => $user->atime,
            'ctime' => $user->ctime,
            'source' => LAB_ID,
        ]);
		Event::trigger('user.extra.keys', $user, $info);

        return (array)$info;
    }

    function get_users($start = 0, $step = 100) {
        $this->_ready('user');

		$users = Q('user:sort(id A)')->limit($start, $step);
		$info = [];

		if (count($users)) {
			foreach ($users as $user) {
                $tag = $user->group;
                $group = $tag->id ? [$tag->name] : NULL ;
                while($tag->parent->id && $tag->parent->root->id){
                    array_unshift($group, $tag->parent->name);
                    $tag = $tag->parent;
                }

                $roles = L('ROLES');
                $role_names = [];
                foreach ((array)$user->roles() as $rid) {
                    $role = $roles[$rid];
                    if ($role) {
                        $role_names[$rid] = $role->name;
                    }
                }

                $equipment_user_tags = [];
                foreach (Q("{$user} tag[root=".Tag_Model::root('equipment_user_tags')."]") as $item) {
                    $equipment_user_tags[] = [
                        "id" => $item->id,
                        "source" => LAB_ID
                    ];
                }

                $data = new ArrayIterator([
					'id' => $user->id,
                    'token' => $user->token,
                    'avatar' => $user->icon_url('128'),
                    'name' => $user->name,
                    'gender' => $user->gender,
                    'member_type' => $user->member_type,
                    'member_type_label' => $user->get_member_label($user->member_type),
                    'group' => $group,
                    'group_path' => $user->group->path,
					'group_id' => $user->group->id ? : 0,
                    'ref_no' => $user->ref_no,
                    'major' => $user->major,
                    'organization' => $user->organization,
                    'dto' => $user->dto,
                    'email' => $user->email,
					'phone' => $user->phone,
                    'binding_phone'=>$user->binding_phone,
                    'address' => $user->address,
					'card_no' => $user->card_no,
					'lab_id' => $this->get_user_labs($user),
                    'privacy' => $user->privacy,
                    'lab_pi' => Q("$user<pi lab")->to_assoc('id', 'id'),
                    'roles' => $role_names,
                    'is_admin' => $user->access('管理所有内容') ? TRUE : FALSE,
					'creator' => $user->creator->name,
					'auditor' => $user->auditor->name,
                    'atime' => $user->atime,
                    'ctime' => $user->ctime,
					'source' => LAB_ID,
                    'icon16_url' => $user->icon_url(16),
                    'icon32_url' => $user->icon_url(32),
                    'icon48_url' => $user->icon_url(48),
                    'icon64_url' => $user->icon_url(64),
                    'icon128_url' => $user->icon_url(128),
                    'eq_user_tag' => $equipment_user_tags,
                    'yiqikong_id' => $user->yiqikong_id
                ]);
                Event::trigger('user.extra.keys', $user, $data);
                $info[] = $data->getArrayCopy();
			}
        }
        return $info;
	}

    function get_user_by_gapperId($id) {
        $this->_ready('user');
        $user = O('user');

        if (is_numeric($id)) {
            $user = O('user', ['gapper_id' => $id]);
        }

        if (!$user->id) return FALSE;

        $tag = $user->group;
        $group = $tag->id ? [$tag->name] : NULL ;
        while($tag->parent->id && $tag->parent->root->id){
            array_unshift($group, $tag->parent->name);
            $tag = $tag->parent;
        }

        $roles = L('ROLES');
        $role_names = [];
        foreach ((array)$user->roles() as $rid) {
            $role = $roles[$rid];
            if ($role) {
                $role_names[$rid] = $role->name;
            }
        }

        $info = new ArrayIterator([
            'id' => $user->id,
            'token' => $user->token,
            'avatar' => $user->icon_url('128'),
            'name' => $user->name,
            'name_abbr' => $user->name_abbr,
            'gender' => $user->gender,
            'member_type' => $user->member_type,
            'member_type_label' => $user->get_member_label($user->member_type),
            'group' => $group,
            'group_path' => $user->group->path,
            'group_id' => $user->group->id ? : 0,
            'ref_no' => $user->ref_no,
            'major' => $user->major,
            'organization' => $user->organization,
            'dto' => $user->dto,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'card_no' => $user->card_no,
            'labs' => $this->get_user_labs($user),
            'roles' => $role_names,
            'is_admin' => $user->access('管理所有内容') ? TRUE : FALSE,
            'equipments' => Q("$user<incharge equipment")->to_assoc('id', 'id'),
            'creator' => $user->creator->name,
            'auditor' => $user->auditor->name,
            'atime' => $user->atime,
            'ctime' => $user->ctime,
            'source' => LAB_ID,
        ]);
        
		Event::trigger('user.extra.keys', $user, $info);

        return (array)$info;
    }
}
