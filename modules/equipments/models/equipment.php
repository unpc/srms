<?php

class Equipment_Model extends Presentable_Model {

    const SHARE_STATUS_NO_SHARED = 0;
    const SHARE_STATUS_SHARED = 1;

    static $share_status = [
        self::SHARE_STATUS_SHARED => '是',
        self::SHARE_STATUS_NO_SHARED => '否',
	];
	
	protected $object_page = [
		'view'=>'!equipments/equipment/index.%id[.%arguments]',
		'edit'=>'!equipments/equipment/edit.%id[.%arguments]',
		'delete'=>'!equipments/equipment/delete.%id[.%arguments]',
		'report'=>'!equipments/equipment/report.%id',
		'capture'=>'!equipments/capture/index.%id[.%arguments]',
		'follow'=>'!equipments/index/follow.%id',
		'unfollow'=>'!equipments/index/unfollow.%id',
		'access_code' => '!equipments/equipment/access_code.%id[.%arguments]',
        'sample_time' => '!equipments/equipment/sample_time.%id[.%arguments]',
        'extra_setting'=> '!equipments/equipment/extra_setting.%id[.%arguments]',
		'empower_setting'=> '!equipments/equipment/empower_setting.%id[.%arguments]',
		'time_counts_setting'=> '!equipments/equipment/time_counts_setting.%id[.%arguments]'
	];

	function & links($mode = 'index') {
		if (!$this->id) return [];

		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				// 管理仪器设备 => 修改仪器设备
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('equipments', '修改'),
					'tip' => I18N::T('equipments', '修改'),
					'extra' =>'class="button button_edit fa-lg"',
				];
			}

			if($this->status == EQ_Status_Model::IN_SERVICE && $me->id && !$this->is_mirror){
				$links['error_report'] = [
					'url' => $this->url(NULL, NULL, NULL, 'report'),
					'text'  => I18N::T('equipments', '故障报告'),
					'tip' => I18N::T('equipments', '故障报告'),
					'extra'=>'class="button button_talk"',
				];
			}

			break;
		case 'control':
			$is_admin = $me->is_allowed_to('修改仪器使用记录', $this);
			if ($this->control_mode == 'computer' || $this->control_mode == 'veronica') {
				if ($this->connect) {
					//普通用户不能通过网页的打开关闭按钮来控制仪器
					if (!$is_admin) {
						$can_turn_on = $can_turn_off = FALSE;
					}
					else {
						$can_turn_on = !$this->is_using
						&& $this->status == EQ_Status_Model::IN_SERVICE
						&& ($is_admin || $me->is_allowed_to('管理使用', $this) || !$this->cannot_access($me, $now));

						$can_turn_off = $this->is_using && ($is_admin || Q("eq_record[dtstart>0][dtstart<$now][dtend=0][equipment={$this}][user={$me}]")->total_count()>0);
					}
				}
				else {
					$can_turn_on = $can_turn_off = FALSE;
				}
			}
			// 蓝牙控制暂时没有远程开关机, 先注释掉
			// elseif ($this->control_mode == 'bluetooth') {
			// 	if ($this->connect) {
			// 		//普通用户不能通过网页的打开关闭按钮来控制仪器
			// 		if (!$is_admin) {
			// 			$can_turn_on = $can_turn_off = FALSE;
			// 		}
			// 		else {
			// 			$can_turn_on = !$this->is_using
			// 			&& $this->status == EQ_Status_Model::IN_SERVICE
			// 			&& ($is_admin || $me->is_allowed_to('管理使用', $this) || !$this->cannot_access($me, $now));

			// 			$can_turn_off = $this->is_using && ($is_admin || Q("eq_record[dtstart>0][dtstart<$now][dtend=0][equipment={$this}][user={$me}]")->total_count()>0);
			// 		}
			// 	}
			// 	else {
			// 		$can_turn_on = $can_turn_off = FALSE;
			// 	}
			// }
			else {
				$connected = preg_match('/^gmeter/', $this->control_address) ? $this->connect : $this->is_monitoring;
				if ($connected) {
					//普通用户不能通过网页的打开关闭按钮来控制仪器
					if(!$is_admin){
						$can_turn_on = $can_turn_off = FALSE;
					}
					else{
						$can_turn_on = !$this->is_using && $this->status==EQ_Status_Model::IN_SERVICE && ($is_admin || $me->is_allowed_to('管理使用', $this) || !$this->cannot_access($me, $now));
						$can_turn_off = $this->is_using && ($is_admin || Q("eq_record[dtstart>0][dtstart<$now][dtend=0][equipment={$this}][user={$me}]")->total_count()>0);
					}
				}
				else {
					$can_turn_on = $can_turn_off = FALSE;
				}
			}

			$rel = '#'.$this->control_container;

			if ($can_turn_off) {
				$links['turn_off'] = [
                    'text' => "<label for='switch'></label><div class='title'>".I18N::HT('equipments', '关闭')."</div>",
                    'title' => "<label for='switch'></label><div class='title'> ".I18N::HT('equipments', '关闭')."</div>",
                    'url' => '#',
					'extra' => 'class="switch-checkbox switch-checkbox-checked" q-object="use" q-event="click" q-static="'.H(['state'=>'off', 'rel'=>$rel]).'"',
				];
			}
			elseif ($can_turn_on) {
				$links['turn_on'] = [
					'text' => "<label for='switch'></label><div class='title'>".I18N::HT('equipments', '打开')."</div>",
					'title' => "<label for='switch'></label><div class='title'> ".I18N::HT('equipments', '打开')."</div>",
					'url' => '#',
					'extra' => 'class="switch-checkbox" q-object="use" q-event="click" q-static="'.H(['state'=>'on', 'rel'=>$rel]).'"',
				];
			}
			elseif ($this->is_using && $is_admin) {
				$links['force_off'] = [
                    'text' => "<label for='switch'></label><div class='title'>".I18N::HT('equipments', '强制关闭')."</div>",
                    'title' => "<label for='switch'></label><div class='title'> ".I18N::HT('equipments', '强制关闭')."</div>",
					'url' => '#',
					'extra' => 'class="switch-checkbox switch-checkbox-checked" q-object="use" q-event="click" q-static="'.H(['state'=>'force_off', 'rel'=>$rel]).'"',
				];
			}
			break;
		case 'index':
		//default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'tip' => I18N::T('equipments', '修改'),
					'text'  => I18N::T('equipments', '修改'),
					'extra' =>'class="blue"',
				];
			}
            if ($me->access('管理所有内容') && Config::get('equipments.placed_at_the_top')) {
                $extra =  'q-object="placed_top" q-event="click" q-src="' . URI::url('!equipments/index') . '"';
                if ($this->is_top) {
                    $links['top'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'top'),
                        'tip' => I18N::T('equipments', '取消置顶'),
                        'text'  => I18N::T('equipments', '取消置顶'),
                        'extra' => 'q-static="' . H(['equipment_id'=>$this->id, 'type' => 'reset']) . '"' . $extra. ' class="blue"',
                    ];
                } else {
                    $links['top'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'top'),
                        'tip' => I18N::T('equipments', '置顶'),
                        'text'  => I18N::T('equipments', '置顶'),
                        'extra' => 'q-static="' . H(['equipment_id'=>$this->id, 'type' => 'top']) . '"' . $extra. ' class="blue"',
                    ];
                }
			}
			break;
		}

		Event::trigger('equipment.links', $this, $links, $mode);

		return (array) $links;
	}

	static function & locations(){

		$db = Database::factory();
		$result = $db->query('SELECT DISTINCT `location` FROM `equipment` WHERE `location` != ""');

		$locations = [H('--')];
		if ($result) while($row = $result->row()){
			$locations[] = H($row->location);
		}

		return $locations;

	}

	function current_user() {
		// NO.BUG#252(xiaopei.li@2010.12.18)
		$now = round(time() / 60) * 60;
		$records = Q("eq_record[equipment={$this}][dtstart<=$now][dtend=0]:sort(dtend D)");
		return $records->total_count() > 0 ? $records->current()->user : O('user');
	}

	function last_user() {
		$now = round(time() / 60) * 60;
		$records = Q("eq_record[equipment={$this}][dtend<$now]:sort(dtend D)");
		return $records->total_count() > 0 ? $records->current()->user : O('user');
	}

	function get_root() {
		$root = $this->tag_root;
		if (!$root->id) {
			$root = O('tag', ['name' => (string) $this, 'readonly' => 1]);
			if (!$root->id) {
				$root->name = (string) $this;
				$root->readonly = 1;
				$root->save();
			}
			if ($root->id && $this->name) {
				$this->tag_root = $root;
				$this->save();
			}
		}
		return $root;
	}

	function status_tags() {
		$output = '';
		if (!$this->id) return $output;
		$output .= Event::trigger('equipment.status_tag', $this);
		return $output;
	}

	function get_followers_count() {
		if (!$this->id) {
			return 0;
		}
		$followers_count = Q("follow[object={$this}] user")->total_count();
		return $followers_count;
    }

    function contacts() {
        return  Q("{$this} user.contact");
	}

	function support_device_plugin($plugin_name, $version=NULL) {
		return $this->id && (
			isset($this->device['plugins'][$plugin_name])
			|| in_array($plugin_name, (array) $this->device['plugins'])
		);
	}

	function get_free_access_cards() {
        $cards = [];
		$free_access_users = $this->get_free_access_users();

		foreach ($free_access_users as $user) {
			if ($user->card_no) {
				$cards[(string)$user->card_no] = $user;
			} elseif ($card_no = $user->get_user_card()) {
				$cards[(string)$card_no] = $user;
			}
		}

		return $cards;
	}

	function get_free_access_users() {
        $users = [];
		$free_access_tokens = array_unique(array_merge((array)Config::get('lab.admin'), (array)Config::get('equipment.free_access_users')));
		foreach ($free_access_tokens as $token) {
			$token = Auth::normalize($token);
			$user = O('user', ['token'=>$token]);
			$users[$user->id] = $user;
		}

		foreach (Q("$this user.incharge") as $incharge) {
			$users[$incharge->id] = $incharge;
		}

		//管理所有内容的用户也应该写入离线卡号
		$roles = clone L('ROLES');
		$admin_all_roles = [];
		foreach ( $roles as $key => $value) {
			$role_perm = (array) Q("{$value} perm")->to_assoc('name', 'id');
			if ( (int)$key<0 || empty($role_perm) ) continue;
			if ( array_key_exists('管理所有内容', $role_perm)  ) {
				$admin_all_roles[] = $key;
			}
		}
		if ( !empty($admin_all_roles) ) {
			$selector = 'role[id=' . implode(',', $admin_all_roles) . '] user[atime>0]';
			$admins = Q($selector)->limit($free_access_cards);
			foreach($admins as $admin) {
				if ( $admin->id && $admin->card_no ) {
					$users[$admin->card_no] = $admin;
				}
			}
		}

		return $users;
	}

    function cannot_access($user, $dtstart, $dtend=0) {
        //用户不存在
        //用户未激活
        //用户设定了过期时间并且当前时间在过期时间之后（用户过期）
        //则用户无法使用仪器 return true
    	if (!$user->id || !$user->is_active()) {
    		Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您不是合法用户.'));
    		return TRUE;
    	}

    	if ($user->dto && Date::time() > Date::get_day_end($user->dto)) {
    		Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '过期用户无法使用仪器.'));
    		return TRUE;
    	}

        return (bool) Event::trigger('equipment_model.call.cannot_access', $this, [$user, $dtstart, $dtend]);
    }

	function save_real_icon ($image, $base = '') {
		$base = $base ? : LAB_PATH . PRIVATE_BASE . 'icons/' . $this->name() . '/';

		$path = $base . 'real/' . $this->id . '.png';
		File::check_path($path);
		$image->save('png', $path);
		Cache::cache_file($path, TRUE);

		return $path;
	}

    function icon_url ($size = 128) {
        $size = $this->normalize_icon_size($size);
        $icon_file = $this->icon_file($size);

        if (!$icon_file) {
            // 获取默认的仪器图片
            $file = Core::file_exists(PRIVATE_BASE.'icons/image/'.$size.'.png', '*');
            if($file) return Config::get('system.base_url').Cache::cache_file($file).'?_='.$this->mtime;
            $identicon = new Identicon();
            return $identicon->getImageDataUri($this->name. $this->id, $size);
        }

        return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$this->mtime;
    }

    function icon_file($size = 128, array $fields = ['id']) {
        foreach($fields as $field){
            $file =
                Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'/'.$this->$field.'.png',
                        '*');
            if($file) break;
        }
        return $file;
	}
	
	function save($overwrite = FALSE) {
		if (!$this->name) return false;
		$result = parent::save($overwrite);
		return $result;
	}

    //为了避免影响通用功能调用方式，新开一个方法
    function cannot_access_with_door($user, $dtstart, $dtend = 0, $obj = 'door')
    {
        //用户不存在
        //用户未激活
        //用户设定了过期时间并且当前时间在过期时间之后（用户过期）
        //则用户无法使用仪器 return true
        if (!$user->id || !$user->is_active()) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您不是合法用户.'));
            return TRUE;
        }

        if ($user->dto && Date::time() > Date::get_day_end($user->dto)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '过期用户无法使用仪器.'));
            return TRUE;
        }

        return (bool)Event::trigger('equipment_model.call.cannot_access', $this, [$user, $dtstart, $dtend, $obj]);
    }

}
