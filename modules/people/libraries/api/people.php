<?php 

class API_People {

	//验证失败返回错误信息
	const AUTH_FAILED = 0;

    private function _checkAuth()
    {
        $people = Config::get('rpc.servers')['people'];
        if ((!isset($_SESSION['people.client_id']) ||
            $people['client_id'] != $_SESSION['people.client_id'])
			&& !Scope_Api::is_authenticated('people')) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    function authorize($clientId, $clientSecret)
    {
        $people = Config::get('rpc.servers')['people'];
        if ($people['client_id'] == $clientId && 
            $people['client_secret'] == $clientSecret) {
            $_SESSION['people.client_id'] = $clientId;
            return session_id();
        }
        return false;
    }

	function get_user($user, $keys=null){
		$this->_checkAuth();

		$info = [];

		if (!$user) return FALSE;

		if (is_int($user)) {
			$u = O('user', $user);
		}

		if(!$u->id){
			$u = O('user', ['token'=>$user]);
		}

        // 不知道为嘛电子门牌gpps走到这里来了, 讲道理应该去让gpps接gapper, 所以这里先临时处理了
        if (!$u->id) {
            $u = Event::trigger('get_user_from_sec_card', $user) ? : O('user', ['card_no' => $user]);
        }

		if(!$u->id) return FALSE;

		$tag = $u->group;
		$group = $tag->id ? [$tag->name] : null ;
		while($tag->parent->id && $tag->parent->root->id){
			array_unshift($group, $tag->parent->name);
			$tag = $tag->parent;
		}

		$roles = L('ROLES');
		$role_names = [];
		foreach ((array)$u->roles() as $rid) {
			$role = $roles[$rid];
			if ($role) {
				$role_names[$rid] = $role->name;
			}
		}

		$info = [
			'id' => $u->id,
			'username' => $u->token,
			'email' => $u->email,
			'name' => $u->name,
			'gender' => $u->gender,
			'card_no' => $u->card_no,
			'ctime' => $u->ctime,
			'atime' => $u->atime,
			'dto' => $u->dto,
			'phone' => $u->phone,
			'address' => $u->address,
			'group' => $group,
			'group_id' => $u->group->id ? : 0,
			'member_type' => $u->member_type,
			'member_type_label' => $u->get_member_label($u->member_type),
			'creator' => $u->creator->name,
			'auditor' => $u->auditor->name,
			'ref_no' => $u->ref_no,
			'major' => $u->major,
			'organization' => $u->organization,
			'lab_owner' => Q("$u<pi lab")->to_assoc('id', 'name'),
			'lab_id' => $GLOBALS['preload']['people.multi_lab'] ? Q("$u lab")->to_assoc('id', 'name') : Q("$u lab")->current()->id,
			'is_admin' => $u->access('管理所有内容') ? TRUE : FALSE,
			'roles' => $role_names,
			'source' => LAB_ID, 
		];
		$info = new ArrayIterator($info);
		Event::trigger('people.extra.keys', $u, $info);

		if(!$keys){
			return $info;
		}
		else{
			if(is_array($keys)){
				$data = [];
				foreach ($keys as $key) {
					if(array_key_exists($key, $info)){
						$data[$key] = $info[$key];
					}
				}
				return $data;
			}
			else{
				if(array_key_exists($keys, $info)){
					return $info[$keys];
				}
			}
		}
	}

	// 对外接口
    public function userStatus () {
        $data = [];

		$root = Tag_Model::root('group');
        $group = Q("tag_group[name*=校外][root={$root}]")->current();

        $data['total'] = Q('user')->total_count();
        $data['outer'] = Q("$group user")->total_count();
        $data['inner'] = $data['total'] - $data['outer'];
        $data['incharge'] = Q("equipment user.incharge")->total_count();

        return $data;
    }
}

