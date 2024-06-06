<?php
//'view'=>'!people/profile/%id.%name',
class User_Model extends Presentable_Model {

    private $info;

	static $genders = [-1 => '--', '男','女'];
	//static $members = array( '科研助理', '实验室管理员');
	static $members = [
		'学生'=>[0=>'本科生', '硕士研究生', '博士研究生','其他'],
		'教师'=>[10=>'课题负责人(PI)', '科研助理', 'PI助理/实验室管理员', '其他'],
		'其他'=>[20=>'技术员', '博士后', '其他']
    ];

    static $privacy = [
        '1'=>'自己可见',
        '2'=>'实验室可见',
        '0'=>'所有人可见'
    ];

    const Privacy_Me = 1;
    const Privacy_Lab = 2;
    const PRIVACY_ALL = 0;

	/**
	 * 由于members数组结构复杂，所以需要此函数按member_type输出member的可读类型
	 * (xiaopei.li@2011.03.25)
	 *
	 * @param member_type
	 *
	 * @return
	 */
	static function get_member_label($member_type) {
		$label = '';

		foreach (self::get_members() as $kind => $the_kind_of_members) {

			if (array_key_exists($member_type, $the_kind_of_members)) {
				$label = I18N::T('people', $kind) . ' - ' . I18N::T('people', $the_kind_of_members[$member_type]);
				break;
			}
		}

		return $label;
	}

    /**
	 * 由于members类型经常性的需要调整，所以给出来一个通用修复的钩子
	 *
	 * @param null
	 *
	 * @return Array members
	 */
    static function get_members()
    {
        if ($members = Event::trigger('user_model.get_members')) {
            return (array)$members;
        }
        return (array)self::$members;
    }


	protected $object_page = [
		'view'=>'!people/profile/index.%id[.%arguments]',
		'edit'=>'!people/profile/edit.%id[.%arguments]',
		'delete'=>'!people/profile/delete.%id[.%arguments]',
		'follow'=>'!people/list/follow.%id',
		'unfollow'=>'!people/list/unfollow.%id',
		'index'=>'!people/list'
	];

	function default_value($name) {
		switch($name) {
		case 'name':
			return T('--');
		}
	}

	private $_perms = NULL;
	private $_perms_timestamp = 0;
    private $_user_select_role = NULL;
	function perms() {

		$now = time();

        if ($now - $this->_perms_timestamp > 10 || $this->_user_select_role !== Switchrole::user_select_role()) {
            $this->_perms_timestamp = $now;
            $this->_user_select_role = Switchrole::user_select_role();

			$perms = new ArrayIterator;

			Event::trigger('user_model.perms.enumerates', $this, $perms);
			$perms = (array) $perms;
			$roles = L('ROLES');
            $user_roles = $this->roles();
            /* xian.zhou 2020.4.2 多角色切换 start */
            // 整理用户角色，区分普通用户和创建角色
            $role_list = $this->get_switch_role();
            // 当前用户需要应用的角色ID
            $key = "普通用户";
            if ($this->_user_select_role !== NULL){
                $key = $this->_user_select_role;
            }

            $user_select_role_id = [];
            if (isset($role_list[$key])) {
                $user_select_role_id = $role_list[$key];
            }
	    // 该名称角色在目前的角色表中不存在
            if (count($user_select_role_id) === 0 && $this->token === Auth::token()) {
                // 修正为普通用户
                $key = "普通用户";
                Switchrole::user_select_role($key);
                if (isset($role_list[$key])) {
                    Switchrole::user_select_role_id($role_list[$key]);
                }
            }
            // 该名称角色在目前的角色表中存在
            else {
                // 更新角色ID
                Switchrole::user_select_role_id($user_select_role_id);
            }


            /* xian.zhou 2020.4.2 多角色切换 end */
			foreach ($user_roles as $rid) {
                /* xian.zhou 2020.4.2 多角色切换 start */
                if (PHP_SAPI !== "cli" && !L('IS_API_REQUEST') && !in_array($rid, $user_select_role_id)) continue;
                /* xian.zhou 2020.4.2 多角色切换 end */
                $role = $roles[$rid];
				if ($role->id) $perms += (array)Q("role[id={$role->id}] perm:sort(weight A)")->to_assoc('name', 'name');
                $default_perms = !Q("role#{$role->id}")->current()->connect_perms_time ? config::get('perms.default_roles')[$role->name]['default_perms']: [];
                if(count((array) $default_perms) > 0) {
                    foreach ($default_perms as $value) {
                        $perms += ["$value" => 'on'];
                    }
                }
			}
			$this->_perms = $perms;

		}
		return $this->_perms;
	}

    function all_perms(){
        $perms = new ArrayIterator;
        Event::trigger('user_model.perms.enumerates', $this, $perms);
        $perms = (array) $perms;
        $roles = L('ROLES');
        $user_roles = $this->roles();
        foreach ($user_roles as $rid) {
            $role = $roles[$rid];
            if ($role->id) $perms += (array)Q("role[id={$role->id}] perm:sort(weight A)")->to_assoc('name', 'name');
        }
        return $perms;
    }

/*
	function roles() {
		$user_roles = new ArrayIterator;

		if ($this->id && $this->is_active()) {

			Event::trigger('user_model.roles.enumerates', $this, $user_roles);

			if (!count($user_roles)) {
				$user_roles = new ArrayIterator(Q("$this role")->to_assoc('id', 'id'));
			}
			//加载预定权限
			if ($GLOBALS['preload']['people.enable_member_date'] && $this->dto>0 && $this->dto < time()){
				$role = O('role', ['weight' => ROLE_PAST_MEMBERS]);
			}
			else {
				$role = O('role', ['weight' => ROLE_CURRENT_MEMBERS]);
			}
			if ($role->id) $user_roles[$role->id] = $role->id;

			foreach (self::$members as $k => $v) {
				if (array_key_exists($this->member_type, $v)) {
					$mt_name = $k;
					break;
				}
			}

		   	if ($mt_name) {
				$default_roles = (array) Config::get('roles.default_roles');
				foreach ($default_roles as $rid => $r) {
					$r_mt_key = $r['member_type_key'] ?: $r['name'];
					if ($mt_name == $r_mt_key) {
						$role = O('role', ['weight' => $rid]);
						if ($role->id) $user_roles[$role->id] = $role->id;
						break;
					}
				}
			}

		}
		else {
			$role = O('role', ['weight' => ROLE_VISITORS]);
			if ($role->id) $user_roles[$role->id] = $role->id;
		}

		$user_roles = Event::trigger('user_model.extra_roles', $this, $user_roles) ? : $user_roles;
		return iterator_to_array($user_roles, TRUE);
	}
*/
        function roles() {
		$user_roles = new ArrayIterator;

		if ($this->id && $this->is_active()) {

			Event::trigger('user_model.roles.enumerates', $this, $user_roles);

			if (!count($user_roles)) {
				$user_roles = new ArrayIterator(Q("$this role")->to_assoc('id', 'id'));
			}
			//加载预定权限
			if ($GLOBALS['preload']['people.enable_member_date'] && $this->dto>0 && $this->dto < time()){
				$role = O('role', ['weight' => ROLE_PAST_MEMBERS]);
			}
			else {
				$role = O('role', ['weight' => ROLE_CURRENT_MEMBERS]);
			}
			if ($role->id) $user_roles[$role->id] = $role->id;

			foreach (self::$members as $k => $v) {
				if (array_key_exists($this->member_type, $v)) {
					$mt_name = $k;
					break;
				}
			}

            $default_roles = (array) Config::get('roles.default_roles');
		   	if ($mt_name) {
				foreach ($default_roles as $rid => $r) {
					$r_mt_key = $r['member_type_key'] ?: $r['name'];
					if ($mt_name == $r_mt_key) {
						$role = O('role', ['weight' => $rid]);
						if ($role->id) $user_roles[$role->id] = $role->id;
						break;
					}
				}
			}

		   	// 是否为课题组负责人
            if ($this->is_lab_pi()) {
                foreach ($default_roles as $rid => $r) {
                    if ($r['name'] == I18N::T('labs', '实验室负责人')) {
                        $role = O('role', ['weight' => $rid]);
                        if ($role->id) $user_roles[$role->id] = $role->id;
                        break;
                    }
                }
            }

            // 是否为仪器负责人
            if ($this->is_equipment_charge()) {
                foreach ($default_roles as $rid => $r) {
                    if ($r['name'] == '仪器负责人') {
                        $role = O('role', ['weight' => $rid]);
                        if ($role->id) $user_roles[$role->id] = $role->id;
                        break;
                    }
                }
            }

            // 是否为科技部申报者
            if ($this->is_nrii_reporter()) {
                foreach ($default_roles as $rid => $r) {
                    if ($r['name'] == '科技部申报任务相关人员') {
                        $role = O('role', ['weight' => $rid]);
                        if ($role->id) $user_roles[$role->id] = $role->id;
                        break;
                    }
                }
            }

		}
		else {
			$role = O('role', ['weight' => ROLE_VISITORS]);
			if ($role->id) $user_roles[$role->id] = $role->id;
		}

		$user_roles = Event::trigger('user_model.extra_roles', $this, $user_roles) ? : $user_roles;
		return iterator_to_array($user_roles, TRUE);
	}


	function get_switch_role() {
        $default_roles = Config::get('roles.default_roles');
        $switch_roles = Config::get('switch_role.switch_role');
        $roles = L('ROLES');
        $user_roles = $this->roles();
        $role_list = [];
        foreach ($user_roles as $rid) {
            $role = $roles[$rid];
            if ($role->id) {
                $key = $role->name;
                if (
                    array_key_exists($role->weight, $default_roles)
                    && !in_array($role->name, $switch_roles)
                ) $key = "普通用户";
                $role_list[$key][] =  $rid;
            }
        }
        return $role_list;
    }

    function is_nrii_reporter() {

        if (Module::is_installed('summary')) {
            $db = Database::factory();
            $res = $db->query("
                select
                count(*) as count
                from
                role as r
                left join _r_user_role as rur on rur.id2 = r.id
                left join _r_role_perm as rrp on rrp.id1 = r.id
                left join perm as p on p.id = rrp.id2
                where p.id is not null and rur.id1 = {$this->id} 
                    and (p.name = '[大数据体系]管理申报任务' or p.name = '[大数据体系]填报仪器数据' or p.name = '[大数据体系]审核仪器数据');
            ");

            if ($res) $count = $res->value() ?: 0;

            if ($count) return true;

            // return $this->access('管理申报任务') || $this->access('填报仪器数据') || $this->access('审核仪器数据');
        }

        return false;
    }

    function is_lab_pi() {
        if (Q("{$this}<pi lab")->total_count()) {
			return true;
		}
        return false;
    }

    function is_equipment_charge() {
        if (Q("{$this}<incharge equipment")->total_count()) {
            return true;
        }
        return false;
    }


	function & links($mode = 'index') {
		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
		case 'view':
			if($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => '!people/profile/edit.'.$this->id,
					'tip' => I18N::T('people', '修改'),
					'text' => '<span class="after_icon_span">'.I18N::T('people', '修改').'</span>',
					'extra' =>'class="button button_edit"',
				];
			}
			break;
        case 'dashboard':
           if($me->is_allowed_to('修改', $this)) {
                $links['pass'] = [
                   'text' => I18N::T('people', '通过'),
                   'extra' => 'class="blue" q-event="click" q-object="people"' .
                                ' q-static="' . H(['user_id' => $this->id, 'approval' => 'pass']) .
                                '" q-src="' . URI::url("!people/approval") . '"',
                ];
                $links['reject'] = [
                    'text' => I18N::T('people', '驳回'),
                    'extra' => 'class="blue" q-event="click" q-object="people"' .
                                ' q-static="' . H(['user_id' => $this->id, 'approval' => 'reject']) .
                                '" q-src="' . URI::url("!people/approval") . '"',
                ];
           }
            break;
        case 'index':
		default:
			if($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => '!people/profile/edit.'.$this->id,
                    'tip' => I18N::T('people', '修改'),
                    'text' => I18N::T('people', '修改'),
                    'extra' =>'class="blue"',
				];
			}
		}

        Event::trigger('user.links', $this, $links, $mode);

		return (array)$links;
	}

	static function is_reserved_token($token){
        $token = strtolower($token);
		$reserved = Lab::get('people.reserved.token', Config::get('people.reserved.token'));
		if(is_array($reserved) && in_array($token, $reserved)){
			return TRUE;
		}
		return FALSE;
	}
	//添加关注
	function follow($object){
		if (!$object->id) {
			return FALSE;
		}

        $follow = O('follow', ['user'=>$this, 'object'=>$object]);
		if ($follow->id) return FALSE;

        if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('follow')) {
            $opt = Config::get('rpc.master');
            $rpc = new RPC($opt['url']);
            $rpc->set_header(
                [
                    "CLIENTID: {$opt['client_id']}",
                    "CLIENTSECRET: {$opt['client_secret']}"
                ]
            );
            try {
                $rpc->db_sync->follow($this->id, $object->id, $object->name());
            } catch (Exception $e) {

            }
        } else {
            $follow->user = $this;
            $follow->object = $object;
            $follow->save();
        }

		if ($object->name() == 'equipment' && $this->gapper_id) {
			$data = [
				'jsonrpc' => '2.0',
				'method' => 'YiQiKong/Follow/Bind',
				'params' => [
					'user' => $this->gapper_id,
					'source_name' => $object->name(),
					'source_id' => $object->yiqikong_id
				]
			];

			Debade_Queue::of('YiQiKong')->push($data, 'user');
		}

        if ($object->name() == 'equipment' && Config::get('lab.modules')['app']) {
            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new \Pheanstalk\Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
            $data = [];
            $data['path'] = 'user/follow';
            $data['method'] = "post";
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['header'] = ['x-yiqikong-notify' => true];
            $data['body'] = [
                'source_name' => $object->name(),
                'source_id' => $object->id,
                'uuid' => $object->yiqikong_id ?? '',
                'user_local' => $this->id,
                'user' => $this->yiqikong_id ?? 0,
            ];
            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }

		return TRUE;
	}
	//取消关注
	function unfollow($object) {
		if (!$object->id) {
			return FALSE;
		}

        $follows = Q("follow[object={$object}][user={$this}]");
        if ($follows->total_count()) {

            if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('follow')) {
                $opt = Config::get('rpc.master');
                $rpc = new RPC($opt['url']);
                $rpc->set_header(
                    [
                        "CLIENTID: {$opt['client_id']}",
                        "CLIENTSECRET: {$opt['client_secret']}"
                    ]
                );
                try {
                    $rpc->db_sync->unfollow($this->id, $object->id, $object->name());
                } catch (Exception $e) {
                    
                }
            } else {
                $follows->delete_all();
            }

			if ($object->name() == 'equipment' && $this->gapper_id) {
				$data = [
					'jsonrpc' => '2.0',
					'method' => 'YiQiKong/Follow/Unbind',
					'params' => [
						'user' => $this->gapper_id,
						'source_name' => $object->name(),
						'source_id' => $object->yiqikong_id
					]
				];

				Debade_Queue::of('YiQiKong')->push($data, 'user-unbind');
			}

            if ($object->name() == 'equipment' && Config::get('lab.modules')['app']) {
                $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
                $mq = new \Pheanstalk\Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
                $data = [];
                $data['path'] = 'user/follow';
                $data['method'] = "delete";
                $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
                $data['header'] = ['x-yiqikong-notify' => true];
                $data['body'] = [
                    'source_name' => $object->name(),
                    'source_id' => $object->id,
                    'uuid' => $object->yiqikong_id ?? '',
                    'user_local' => $this->id,
                    'user' => $this->yiqikong_id ?? 0,
                ];
                $mq->useTube('stark')->put(json_encode($data, TRUE));
            }

            return TRUE;
        }
        else {
            return FALSE;
        }
	}

	function followings($type) {
		return Q("follow[user=$this][object_name=$type]:sort(ctime A)");
	}

	function get_follows_count($type) {
		if (!$this->id || !$type) return 0;
		$follow_count = 0;
		if ($type == '*') {
			$follow_types = (array) Config::get('user.follow_type');
			foreach ($follow_types as $follow_type) {
				$follow_count +=  Q("follow[user=$this][object_name=$follow_type]")->total_count();
			}
			return $follow_count;
		}
        elseif ($type == 'equipment') {
            $extra_follows = Event::trigger('equipment.extra.follows', null, $this);
            if($extra_follows instanceof Q) {
                return Event::trigger('equipment.extra.follows', $this->followings('equipment'), $this)->total_count();
            }
            else {
                return Q("follow[user=$this][object_name=equipment]")->total_count();
            }
        }
		$follow_count = Q("follow[user=$this][object_name=$type]")->total_count();
		return $follow_count;
	}

	function & follow_links($object, $mode = 'index') {

        $me = L('ME');
		if (!$object->id
			|| !$this->id
			|| $object->name() == 'user' && $object->id == $me->id
		) {
			return [];
		}

		$links = new ArrayIterator;

		switch ($mode) {
		case 'view':
			$follow = O('follow', ['user'=>$this, 'object'=>$object]);
			$ajax_id = uniqid();
			$extra =  'q-event="click" q-static="'
				. H(['oname'=>$object->name(), 'oid'=>$object->id, 'ajax_id'=>$ajax_id, 'mode'=>$mode])
				. '" q-src="'.URI::url('!people/list').'"';
			if ($follow->id) {
				if ($me->is_allowed_to('取消关注', $object)) {
					$links['unfollow'] = [
						'prefix' => '<span id="' . $ajax_id . '">',
						'url' => $object->url(NULL, NULL, NULL, 'unfollow'),
						'tip' => I18N::T('people', '取消关注'),
						'text' => I18N::T('people', '取消关注'),
						'extra' => 'q-object="unfollow" '.$extra. ' class="button button_follow"',
						'suffix' =>'</span>',
						'weight' => -1,
					];
				}
			}
			else {
				if ($me->is_allowed_to('关注', $object)) {
					$links['follow'] =  [
						'prefix' => '<span id="' . $ajax_id . '">',
						'url' => $object->url(NULL, NULL, NULL, 'follow'),
						'tip' => I18N::T('people', '关注'),
						'text' => I18N::T('people', '关注'),
						'extra' => 'q-object="follow" '.$extra . ' class="button button_follow"',
						'suffix' => '</span>',
						'weight' => -1,
					];
				}
			}
			break;
		case 'index':
		default:
			$follow = O('follow', ['user'=>$this, 'object'=>$object]);
			$ajax_id = uniqid();
			$extra =  'q-event="click" q-static="'
				. H(['oname'=>$object->name(), 'oid'=>$object->id, 'ajax_id'=>$ajax_id, 'mode'=>$mode])
				. '" q-src="'.URI::url('!people/list').'"';
			if ($follow->id) {
				if ($me->is_allowed_to('取消关注', $object)) {
					$links['unfollow'] = [
						'prefix' => '<span id="' . $ajax_id . '">',
						'url' => $object->url(NULL, NULL, NULL, 'unfollow'),
						'tip' => I18N::T('people', '取消关注'),
						'text'  => I18N::T('people', '取消关注'),
						'extra' => 'q-object="unfollow" '.$extra. ' class="blue"',
						'suffix' =>'</span>',
						'weight' => -1,
					];
				}
			}
			else {
				if ($me->is_allowed_to('关注', $object)) {
					$links['follow'] =  [
						'prefix' => '<span id="' . $ajax_id . '">',
						'url' => $object->url(NULL, NULL, NULL, 'follow'),
						'text'  => I18N::T('people', '关注'),
						'tip' => I18N::T('people', '关注'),
						'extra' => 'q-object="follow" '.$extra . ' class="blue"',
						'suffix' =>'</span>',
						'weight' => -1,
					];
				}
			}
		}
		return (array)$links;
	}

	/*
	NO.TASK#236（guoping.zhang@2010.11.16)
	信权限判断规则：入口函数
	@default: 默认返回值
	*/
	function is_allowed_to($perm_name, $object, $options = NULL) {
		if (is_string($object)) {
			$object_name = $object;
			//$object = O($object_class_name);
		}
		elseif ($object instanceof _ORM_Model) {
			$object_name = $object->name();
		}
		else {
			return FALSE;
		}

		$ret = FALSE;
		if ($object_name) {
			$ret = Event::trigger("is_allowed_to[$perm_name].$object_name", $this, $perm_name, $object, $options);
		}
		if ($ret === NULL) return $options['@default'];
		return $ret;
	}

	function status_tags() {
		$output = '';
		if (!$this->id) return $output;

		if (!$this->is_active()) {
			$output .='<span class="status_tag status_tag_error">'.I18N::HT('people', '未激活').'</span> ';
		}

		if ($GLOBALS['preload']['people.enable_member_date'] && $this->dto && $this->dto < time()) {
			$output .='<span class="status_tag status_tag_disable">'.I18N::HT('people', '已过期').'</span> ';
		}

		$output .= Event::trigger('user.status_tag', $this);

		return $output;
	}

	//是否过期用户
	function is_expired()
    {
        if ($GLOBALS['preload']['people.enable_member_date'] && $this->dto && $this->dto < time()) {
            return true;
        }
        return false;
    }

	function save($overwrite=FALSE) {

		// 设置隐私设置
		if (!$this->id) {
			$this->privacy = Config::get('people.default_privacy');
		}

		// 设置IC卡
		if (!$this->card_no && !$this->card_no_s) {
			// 如果未手动设置IC卡号...
			if (Config::get('people.auto_link_card')) {
				// 且开启了自动关联卡号的开关...

				$ref_attr = Config::get('people.card_link_ref'); // 取得查找卡号的属性名

				$ref = $this->$ref_attr; // 取得查找卡号的值

				if ($ref_attr == 'token') {
					// 如果属性名是token，还需除去token的backend
					list($ref,) = Auth::parse_token($ref);
				}

				$card = O('card', ['ref' => $ref]); // 查找卡

				if ($card->id) {
					// 找得到的话，就设置卡号
					$this->card_no = $card->no;
				}
			}
		}

		$ret = parent::save($overwrite);
		// 设置默认实验室
        // if (Module::is_installed('labs') && !Q("$this lab")->total_count()) {
        //     $default_lab = Lab_Model::default_lab();
        //     $this->connect($default_lab);
        // }
        if ($ret){
            if (!$this->info()->user_id) $this->info()->user = $this;
            $this->info()->save();
        }
        return $ret;
	}

	function home_url() {

		$home = $this->home ?: Config::get('lab.default_home');

		switch ($home) {
		case 'me':
            return '!people/profile/index.'.$this->id;
        case 'dashboard':
            return '!people/dashboard';
		case 'sbmenu':
			break;
		default:
			$url = Event::trigger('people.user.home_url', $this->home);
			if ($url) return $url;
			return '!people/profile/index.'.$this->id; // 默认返回个人页
		}

	}

    function show_hidden_user() {
        //只有lab_admin 可以看到隐藏用户
        return in_array($this->token, Config::get('lab.admin'));
    }

    function show_hidden_lab() {
        //只有lab_admin 可以看到隐藏课题组
        $hide_lab = Config::get('labs.hide_lab');
        return $hide_lab ? FALSE : in_array($this->token, Config::get('lab.admin'));
    }

    function must_change_password() {

    	if ($this->must_change_password) {

    		$auth = new Auth($this->token);

            if (Event::trigger('ecard.need.change.password')) return TRUE;

    		if (!$auth->is_readonly()) return TRUE;

    		$this->must_change_password = NULL;
    		$this->save();
    	}

    	return FALSE;

    }

    function friendly_name() {
        $me = L('ME');
        if (!$this->id) return;

        if (Module::is_installed('labs')
            && !$GLOBALS['preload']['people.multi_lab']) {
            $ret = T('%user (%labs)', [
                '%user'=>$this->name,
                '%labs'=>join(' ', Q("$this lab")->to_assoc('id', 'name'))
			]);
        }
        else {
            $ret = T('%user', ['%user'=>$this->name]);
        }
        return $ret;
    }

    /**
     * @return array|string|void
     * 下拉内容为 - 姓名（学工号）
     * 学工号为空 不显示括号内容
     */
    function friendly_ref_no()
    {
        if (!$this->id) return;
        if ($this->ref_no) {
            $ret = T('%user (%ref_no)', [
                '%user' => $this->name,
                '%ref_no' => $this->ref_no
            ]);
        } else {
            $ret = T('%user', [
                '%user' => $this->name
            ]);
        }
        return $ret;
    }

    function icon_url($size=128) {
        $size = $this->normalize_icon_size($size);
		$icon_file = $this->icon_file($size);

        if (!$icon_file) {
            // 获取默认的用户图片
            if($this->gender == 1) $file = Core::file_exists(PRIVATE_BASE.'icons/head_portrait/woman/'.$size.'.png', '*');
            else $file = Core::file_exists(PRIVATE_BASE.'icons/head_portrait/man/'.$size.'.png', '*');
            if($file) return Config::get('system.base_url').Cache::cache_file($file).'?_='.$this->mtime;
            $identicon = new Identicon();
            return $identicon->getImageDataUri($this->name. $this->id, $size);
        }

        return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$this->mtime;
    }

    function icon_file($size=128, array $fields=['id']) {
        foreach($fields as $field){
            $file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'/'.$this->$field.'.png', '*');
            if($file) break;
        }
        return $file;
    }

    function unactive() {
        $this->atime = 0;
        return $this;
    }

    function replacement() {
		$new = clone $this;
		$new->replacement = $this->id;
		if ($new->uuid) {
			unset($new->uuid);
		}
		$new->yiqikong_id = 0;
    	return $new;
    }

    function remove_unique() {
    	list($token, $backend) = Auth::parse_token($this->token);
        $uniqid = uniqid();
        $new_token = $token.$uniqid;
        //更新token
        $this->token = Auth::make_token($new_token, $backend);
        //更新email
        $this->email = $user->email.$uniqid;
        //更新ref_no
        $this->ref_no = $user->ref_no. $uniqid;
        //还是更新掉card_no_s, 此处是无奈之举
		$this->card_no_s = NULL;
		$this->nfs_used = 0.0;
		
		if ($this->gapper_id) $this->gapper_id = NULL;

        $uniques = ORM_Model::schema('user')['indexes'];
		foreach ($uniques as $attr => $v) {
		    if ($v['type'] == 'unique') {
		    	switch ($attr) {
		    		case 'token':
		    		case 'email':
		    		case 'ref_no':
		    			//do nothing
		    			break;
		    		default:
		    			$this->$attr = NULL;
		    			break;
		    	}
		    }
		}

        //未激活
        $this->atime = 0;

        //如果允许用户成员时间范围, 修改该用户过期
        if ($GLOBALS['preload']['people.enable_member_date']) {
            $this->dto = Date::get_day_start();
        }

        Event::trigger('people.remove_unique', $this);

        return $this;

    }

    function move_img_to($object) {
    	$base = LAB_PATH.PRIVATE_BASE.'icons/'.$object->name().'/';

        foreach([16, 32, 36, 48, 64, 128] as $size) {
            $old_path = $this->icon_file($size);
            $new_path = $base.$size.'/'.$object->id.'.png';

            if (file_exists($old_path)) {
            	@copy($old_path, $new_path);
            }
        }
    }

    function move_training($user, $new_user) {
        $trainings = Q("ue_training[user={$user}]");
        if ($trainings->total_count()) {
            foreach ($trainings as $training) {
                $training->user = $new_user;
                $training->save();
            }
        }
    }

    function move_credit($user, $new_user) {
        if (!Module::is_installed('credit')) {
            return;
        }

        if (!$user->id || !$new_user->id) {
            return;
        }

        $db = Database::factory();
        
        $sql = "delete from credit_record where user_id = {$new_user->id}";
        $db->query($sql);

        $init_credit_rule = O('credit_rule', ['ref_no' => 'init_credit_score']);
        $new_credit_record = O('credit_record');
        $new_credit_record->user        = $new_user; // 触发计分用户
        $new_credit_record->credit_rule = $init_credit_rule; // 关联计分规则
        $new_credit_record->score       = $init_credit_rule->score; // 本次得分
        $new_credit_record->total       = $init_credit_rule->score;
        $new_credit_record->is_auto     = !$credit_rule->is_custom; // 是否是系统自动计分
        $new_credit_record->description = $credit_rule->name;
        $new_credit_record->ctime       = Date::time();
        $new_credit_record->save();

        $sql = "delete from credit where user_id = {$new_user->id}";
        $db->query($sql);

        $credit = O("credit", ['user' => $user]);
        if ($credit->id) {
            $new_credit = O('credit');
            $new_credit->user = $new_user;
            $new_credit->credit_level_id = $credit->credit_level_id;
            $new_credit->percent = $credit->percent;
            $new_credit->total = $credit->total;
            $new_credit->_extra = $credit->_extra;
            $new_credit->save();
        }
    }

    function delete_reserv($lab) {
        $now = Date::time();
        $cal_selector = "eq_reserv<component cal_component[organizer={$this}][dtstart>={$now}]";
        if ($lab->id) {
            $cal_selector = "$lab lab_project<project " . $cal_selector;
        }
        Q($cal_selector)->delete_all();
    }

    function __get($property) {
	    if (in_array($property, $this->get_info_fields())) {
            $data = $this->info()->$property;
        }else{
            //先获取真实存储值
            $data = parent::__get($property);
        }

        if (
            in_array($property, [
                'name', 'nfs_size', 'nfs_used', 'nfs_mtime'
            ])
           ) {
            //trigger进行校正
            $edata = Event::trigger("user_model.get.{$property}", $this, $data);
            $data = is_null($edata) ? $data : $edata;
        }

        //return
        return $data;
    }

    function __set($property, $value){
        if (
            in_array($property, [
                'name', 'nfs_size', 'nfs_used', 'nfs_mtime'
            ])
           ) {
            //trigger进行校正
            $edata = Event::trigger("user_model.set.{$property}", $this, $value);
            if ($edata) $value = $edata;
        }

        if (in_array($property, $this->get_info_fields())) {
            $this->info()->$property = $value;
        }else{
            parent::set($property, $value);
        }
    }

    function get_info_fields()
    {
        $user_info_schema = Config::get('schema.user_info')['fields'];
        unset($user_info_schema['user']);
        return array_keys($user_info_schema);
    }

	function connect($obj, $type = NULL, $approved = false) {
		if (!is_array($obj) && is_object($obj) && $obj->name() == 'lab') {
			$check = function ($user, $lab) {
				if ($GLOBALS['preload']['people.multi_lab']) return true;
				$db = $user->db();
				$count = $db->value('SELECT COUNT(*) FROM `_r_user_lab` WHERE `id1` = %d AND `id2` <> %d', $user->id, $lab->id);
				return $count == 0;
			};
		}

		if (is_array($obj)) foreach ($obj as $o) {
			$new_object = [];
			if (is_object($o) && $o->name() == 'lab') {
				if ($check($this, $o)) array_push($new_object, $o);
				$obj = $new_object;
			}
		}
		else if (is_object($o) && $obj->name() == 'lab') {
			if (!$check($this, $obj)) return false;
		}

		$ret = parent::connect($obj, $type, $approved);
	}

	function disconnect($obj, $type = NULL, $approved = false) {
		parent::disconnect($obj, $type, $approved);
        if (is_array($obj)) {
            foreach ($obj as $o) {
                if (is_object($o) && $o->name() == 'lab') {
                    Event::trigger('user_lab.disconnect', $this, $obj);
                }
            }
        }
        else {
            /**
             * 课题组更换PI时，仅disconnect PI，用户还在课题组中，不宜执行disconnect的trigger
             * 还会触发sync模块的_r_user_lab同步bug..
             */
            if ($obj->name() == 'lab' && $type == null) {
                Event::trigger('user_lab.disconnect', $this, $obj);
            }
        }
    }

    function get_active_labs ($equipment = NULL) {
        if (!Module::is_installed('labs')) return Q(':empty');

        $pre_selectors = [];
        $selector = "$this lab";
        if (count($pre_selectors)) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }
        $new_selector = Event::trigger('user.get.labs.selector', $selector, $pre_selectors, $this, $equipment);
        if ($new_selector) {
            $selector = $new_selector;
        }
        $labs = Q($selector);
        $labid2Name = [];
        foreach ($labs as $lab){
            $labid2Name[$lab->id] = $lab->name;
        }
        //获取黑名单里的可用课题组
        $unBannedLabs = EQ_Ban::get_eq_unbanned_lab($this,$equipment,['labs'=>$labs]);
        return @array_intersect($unBannedLabs,$labid2Name);
    }

    public function info()
    {
        if (!($this->info instanceof User_Info_Model)) {
            $this->info = O('user_info', ['user'=>$this]);
            if (!$this->info->id) {
                $this->info->user = $this;
                $this->info->save();
            }
        }

        return $this->info;
    }

    public function uno_perms($group)
    {
        if (
            !People::perm_in_uno()
            || !$this->gapper_id
            || !$group->gapper_id
        ) {
            return [];
        }

        $default_perms = new ArrayIterator;
        Event::trigger('user_model.perms.enumerates', $this, $default_perms);
        $default_perms = (array) $default_perms;
        $default_perms_format = [];
        foreach ($default_perms as $permname => $switch){
            if (strtolower($switch) == 'on'){
                $default_perms_format[] = $permname;
            }
        }

        $cache_key = "perm_in_uno@{$group->gapper_id}#{$this->gapper_id}";
        $cache = L($cache_key);
        if (!$cache) {
            $remote = Gateway::getRemoteUserPermissions([
                'user_id' => $this->gapper_id,
                'group_id' => $group->gapper_id
            ]);
            $cache = array_column($remote['permissions'], "name");
            $cache = array_merge($cache,$default_perms_format);
            Cache::L($cache_key, $cache);
        }
        return $cache;
    }

    public function disconnect_all($type = 'group'){
	    if ($type == 'group'){
            $connects = Q("{$this} tag_group");
            foreach ($connects as $connect){
                $this->disconnect($connect);
            }
        }
        if ($type == 'lab'){
            $connects = Q("{$this} lab");
            foreach ($connects as $connect){
                $this->disconnect($connect);
            }
        }
    }
}
