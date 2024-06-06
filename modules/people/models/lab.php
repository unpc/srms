<?php

class Lab_Model extends Presentable_Model {
	
	//'view'=>'!labs/lab/%id.%name',
	protected $object_page = [
		'view'=>'!labs/lab/index.%id[.%arguments]',
		'edit'=>'!labs/lab/edit.%id[.%arguments]',
		'delete'=>'!labs/lab/delete.%id',
		'autocomplete_owner'=>'!labs/autocomplete/user.%id',
		'autocomplete_tags'=>'!labs/autocomplete/tags.%id',
	];
	
	function default_value($field) {
		switch($field) {
		case 'name':
			return Config::get('lab.name');
		case 'pi':
			return Config::get('lab.pi');
		}
	}

	static function default_lab() {
		$db = Database::factory();
	    $defaultLabID = 0;
	    $findDefaultLabSQL = "SELECT `val` FROM `_config` WHERE `key` = 'default_lab_id'";
	    $ret = $db->value($findDefaultLabSQL);
	    $ret and $defaultLabID = @unserialize($ret);
		
		$lab = O('lab', $defaultLabID);
		if (!$lab->id && !defined('CLI_MODE')) {
			header("Status: 423 Locked");
			die;
		}

		return $lab;
		
	}

	function update_group($group_root=NULL) {
		if ($group_root === NULL) {
			$group_root = Tag_Model::root('group');
		}

		$groups = Q("$this tag_group[root=$group_root]");
		if ($groups->total_count() > 0) {
			$g_ids = $groups->to_assoc('id', 'id');
			foreach ($groups as $g) {
				unset($g_ids[$g->parent->id]);
			}
			$this->group = $groups[reset($g_ids)];
		}
		
		return $this;
	}
	
	function has_user($user) {
		return Q("$user $this")->total_count();
	}
	
	function delete() {
        if(Q("$this user")->total_count()){
        	Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '请确认该实验室不包含任何成员， 然后再进行删除操作。'));
            return FALSE;
        }

        /*
		* 禁止删除默认实验室
		*/
		$db = Database::factory();
	    $defaultLabID = @unserialize($db->value("SELECT `val` FROM `_config` WHERE `key` = 'default_lab_id'"));
	    $equipTempLabID = @unserialize($db->value("SELECT `val` FROM `_config` WHERE `key` = 'equipment.temp_lab_id'"));
	    if ($defaultLabID && $defaultLabID == $this->id) {
	    	Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '默认实验室不允许删除!'));
	    	return false;
	    }
	    if ($equipTempLabID && $equipTempLabID == $this->id) {
	    	Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '默认实验室不允许删除!'));
	    	return false;
	    }

		return parent::delete();		
	}
	
	function & member_links($user, $mode='index') {
		$me = L('ME');
		$links = new ArrayIterator;
		switch ($mode) {
		case 'index';
		default:
			if ($GLOBALS['preload']['people.multi_lab']
				&& !Q("$user<pi $this")->total_count() // 移除成员不可是当前课题组pi
				&& Q("$user lab")->total_count() > 1
				&& $me->is_allowed_to('删除成员', $this)) {
				$links['remove'] = [
					'url'=> $this->url(),
					'text'=> null,
					'tip'=> I18N::HT('labs','移除'),
					'weight' => -2,
					'extra'=>'class="icon-trash" q-event="click" q-object="remove_member" q-static="'.H(array('id'=>$this->id, 'uid'=>$user->id)).'"',
				];
			}
			/*
            2013.3.21，
            经过确认，该处是更具南开的需求，修改用户课题组的操作只能管理员来做。
            课题组pi不可以修改用户课题组，只能删除没有使用记录的成员
			if ($me->is_allowed_to('删除成员', $this) && $this->owner->id != $user->id && $user->id != $me->id) {
				$links['remove'] = array(
					'url'=> $this->url(),
					'text'=> I18N::HT('labs','删除'),
					'extra'=>'class="blue" q-event="click" q-object="remove_member" q-static="'.H(array('id'=>$this->id, 'uid'=>$user->id)).'"',
				);
			}
			*/
		}
		return (array)$links;
	}
	
	function & links($mode='index') {
		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
            case 'dashboard':
                if ($me->is_allowed_to('修改', $this)) {
					$links['pass'] = [
						'text' => I18N::T('labs', '通过'),
						'extra' => 'class="blue" q-event="click" q-object="lab"' .
									 ' q-static="' . H(['lab_id' => $this->id, 'approval' => 'pass']) .
									 '" q-src="' . URI::url("!labs/approval") . '"',
					 ];
					 $links['reject'] = [
						 'text' => I18N::T('labs', '驳回'),
						 'extra' => 'class="blue" q-event="click" q-object="lab"' .
									 ' q-static="' . H(['lab_id' => $this->id, 'approval' => 'reject']) .
									 '" q-src="' . URI::url("!labs/approval") . '"',
					 ];
                }
                break;
            case 'view':
                if ($me->is_allowed_to('修改', $this) || $me->is_allowed_to('管理经费', $this)) {
                    $links['edit'] = [
                        'url'=> $this->url(NULL, NULL, NULL, 'edit'),
                        'text'=> '<span class="after_icon_span">'.I18N::T('labs', '修改').'</span>',
                        'tip' => I18N::T('labs', '修改'),
                        'extra'=>'class="button icon-edit"',
                    ];
                }
                $links = Event::trigger('lab.download.receipt', $links, $this->id) ? : $links;
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $this) || $me->is_allowed_to('管理经费', $this)) {
                    $links['edit'] = [
                        'url'=> $this->url(NULL, NULL, NULL, 'edit'),
                        'text'  => I18N::T('labs', '修改'),
                        'tip' => I18N::T('labs', '修改'),
                        'extra'=>'class="blue"',
                    ];
                }
		}
		return (array)$links;
	}
	
	function status_tags() {
		$output = '';
		if (!$this->id) return $output;

		if (!$this->is_active()) {
			$output .='<span class="status_tag status_tag_error">'.I18N::HT('labs', '未激活').'</span> ';
		}
		
		$output .= Event::trigger('lab.status_tag', $this);

		return $output;
	}
	
	function is_active() {
		return $this->atime > 0;
	}

    //注册时候需要验证的字段，登录后不需要验证token和密码
	static function register_require_fields() {

        $requires = Config::get('form.user_signup')['requires'];

        if (Auth::token()) {
            unset($requires['token']);
            unset($requires['passwd']);
            unset($requires['confirm_passwd']);
        }

        return $requires;

	}

    //添加修改用户的时候需要验证的字段，需要验证token和密码
	static function add_require_fields() {

        $requires = Config::get('form.user_signup')['requires'];

        return $requires;

	}

	static function edit_require_fields() {

        $requires = Config::get('form.user_signup_edit')['requires'];

        return $requires;
	}

    function __get($property) {
        //先获取真实存储值
        $data = parent::__get($property);

        if (
            in_array($property, [
                'name', 'nfs_size', 'nfs_used', 'nfs_mtime'
            ])
           ) {
            //trigger进行校正
            $edata = Event::trigger("lab_model.get.{$property}", $this, $data);
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
            $edata = Event::trigger("lab_model.set.{$property}", $this, $value);
            if ($edata) $value = $edata;
        }

        parent::set($property, $value);
    }
	
	function connect($object, $type=NULL, $approved=FALSE){
		$check = function ($user, $lab) {
			if ($GLOBALS['preload']['people.multi_lab']) return true;
			$db = $user->db();
			$count = $db->value('SELECT COUNT(*) FROM `_r_user_lab` WHERE `id1` = %d AND `id2` <> %d', $user->id, $lab->id);
			return $count == 0;
		};

		if (is_array($object)) foreach ($object as $o) {
			$new_object = [];
			if (is_object($o) && $o->name() == 'user') {
				if ($check($o, $this)) array_push($new_object, $o);
			}
			$object = $new_object;
		}
		else if (is_object($o) && $object->name() == 'user') {
			if (!$check($object, $this)) return false;
		}

		return parent::connect($object, $type, $approved=FALSE);
	}
}

