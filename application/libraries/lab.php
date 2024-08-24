<?php

class Lab {

	static function forget_login() {
		$db = Database::factory();
		$session_id = $_COOKIE[session_name().'_lt'];
		if ($session_id) {
			$db->query('DELETE FROM `_remember_login` WHERE id = "%s"', $session_id);
		}
	}

	static function check_remember_login() {

		if ($_SESSION['remember_login.checked']) return;
		$_SESSION['remember_login.checked'] = TRUE;

		$session_id = $_COOKIE[session_name().'_lt'];
		if ($session_id) {
			$db = Database::factory();
			$uid = $db->value('SELECT uid FROM `_remember_login` WHERE id="%s"', $session_id);
			$user = O('user', $uid);
			if ($user->id) {
				Auth::login($user->token);
				Lab::remember_login($user);

				Log::add(strtr('[applicaiton] %user_name[%user_id]登入成功', [
							'%user_name' => $user->name,
							'%user_id' => $user->id,
				]), 'logon');
			}
		}

	}

	static function remember_login($user) {

		$now = time();

		//cookie有效期增加到30天
		setcookie(session_name().'_lt', session_id(), $now + 2592000, Config::get('system.session_path'), Config::get('system.session_domain'));

		//记录login状态
		$db = Database::factory();
		$db->prepare_table('_remember_login', [
			'fields' => [
				'id'=>['type'=>'char(40)', 'null'=>FALSE, 'default'=>''],
				'uid'=>['type'=>'bigint', 'null'=>FALSE, 'default'=>0],
				'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			],
			'indexes' => [
				'primary'=>['type'=>'primary', 'fields'=>['id']],
				'mtime'=>['fields'=>['mtime']],
			]
		]);

		$exp_time = $now - 2592000;
		$db->query('DELETE FROM `_remember_login` WHERE mtime < %d', $exp_time);

		$db->query('REPLACE INTO `_remember_login` (id, uid, mtime) VALUES ("%s", %d, %d)', session_id(), $user->id, $now);
	}

	const MESSAGE_NORMAL = 'success';
	const MESSAGE_WARNING = 'warning';
	const MESSAGE_ERROR = 'error';

	static $messages = [];
	static $confirms = [];
	static $enable_message = TRUE;

	static function confirm($type, $message, $url, $confirmText)
	{
		if (self::$enable_message) {
			$key = md5(json_encode([$message, $url, $confirmText]));
			if (!isset(self::$confirms[$type][$key])) {
				self::$confirms[$type][$key] = [
					'message'=> $message,
					'url'=> $url,
					'confirm'=> $confirmText
				];
			}
		}
	}

	static function confirms($type) {
		return self::$confirms[$type] ?: [] ;
	}

	static function message($type, $text) {
		if (self::$enable_message && !(self::$messages[$type] && in_array($text, self::$messages[$type]))) {
			self::$messages[$type][] = $text;
		}
	}

    static function messages($type) {
        //array_unique传入参数为空，会报错
        return self::$messages[$type] ? array_unique(self::$messages[$type]) : [];
    }

	static function enable_message($status = TRUE) {
		self::$enable_message = $status;
	}

	private static $cached = [];
	private static $local_config_fields = [
	    'sbmenu_categories',//边栏菜单-系统默认
	    'system.timezone',//区域设置-时区
        '@TAG',//仪器管理-送样设置
        'equipment.add_sample_earliest_limit',
        'equipment.add_sample_latest_limit',
        'equipment.modify_sample_latest_limit',
        'equipment.need_sample_description',
        'equipment.add_reserv_earliest_limit',//仪器管理-预约设置
        'equipment.add_reserv_latest_limit',
        'equipment.modify_reserv_latest_limit',
        'equipment.delete_reserv_latest_limit',
        'equipment.need_reserv_description',
        'equipment.max_allowed_miss_times',//仪器管理-黑名单设置
        'equipment.max_allowed_leave_early_times',
        'equipment.max_allowed_overtime_times',
        'equipment.max_allowed_late_times',
        'equipment.max_allowed_total_count_times',
        'equipment.max_allowed_violate_times',
        'eq.max_allowed_miss_times',
        'eq.max_allowed_leave_early_times',
        'eq.max_allowed_overtime_times',
        'eq.max_allowed_late_times',
        'eq.max_allowed_violate_times',
        'eq.max_allowed_total_count_times',
    ];

    static function get_local_config_fields()
    {
        //偏好设置部分字段+仪器管理(通知提醒,计费通知提醒,送样通知提醒)
        return array_merge(self::$local_config_fields,
            (array)Config::get('notification.equipments_conf'),
            (array)Event::trigger('admin.equipments.notification_configs', []),
            (array)Config::get('notification.eq_sample.templates'));
    }

	static function get($name, $default=NULL, $tag=NULL, $force=FALSE) {
		static $tags, $last_user;
        $table_name = in_array($name, self::get_local_config_fields())
            || Notification::is_local_notification_field($name) ? '_config_local' : '_config';
					
		if ($tag != NULL && $tag!='@' && $name != '@TAG' && 0 != strncmp($name, 'tag.', 4)) {

			if (isset(self::$cached['@TAG']) && !defined('CLI_MODE')) {
				$tagged = (array) self::$cached['@TAG'];
			}
			else {

                $ret = Cache::factory()->get('@TAG');

                if (!$ret) {
                    $db = Database::factory();
		
                    $ret = $db->value('SELECT `val` FROM `'.$table_name.'` WHERE `key`="@TAG"');
                    $ret = self::$cached['@TAG'] = (array) @unserialize($ret);
                    Cache::factory()->set('@TAG', $ret);
                }

				$tagged = $ret;
			}

			if ($tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
				else {
					if ($force) return NULL;
				}
			}

			$user = L('ME');
			if ($last_user->id != $user->id || $tags === NULL) {
				$group_root = Tag_Model::root('group');
				$last_user = $user;

				$tags = Q("$user tag_group[root=$group_root]")->to_assoc('id', 'name');

				if (Q("$user lab")->total_count()) {
					$tags += Q("$user lab tag_group[root=$group_root]")->to_assoc('id', 'name');
				}
			}

			foreach ((array) $tags as $tag) {
				$val = $tagged[$tag][$name];
				if ($val !== NULL) return $val;
			}
		}

		if (isset(self::$cached[$name]) && !defined('CLI_MODE')) {
			return self::$cached[$name];
		}

        $ret = Cache::factory()->get($name);

        if (!$ret || $fetch) {

            $db = Database::factory();
            $ret = $db->value('SELECT `val` FROM `'.$table_name.'` WHERE `key`="%s"', $name);

			if ($ret !== NULL) {
            	$value = self::$cached[$name] = @unserialize($ret);
            	Cache::factory()->set($name, $value);
            	return self::$cached[$name];
        	}
        }
        else {
        	self::$cached[$name] = $ret;
        	return self::$cached[$name];
        }

		return Config::get($name, $default);
	}

	static function set($name, $value=NULL, $tag=NULL) {
        $table_name = in_array($name, self::get_local_config_fields())
        || Notification::is_local_notification_field($name) ? '_config_local' : '_config';

		$db = Database::factory();
		if (!$db->table_exists($table_name)) {
			$fields=[
				'key'=>['type'=>'varchar(150)', 'null'=>TRUE, 'default'=>NULL],
				'val'=>['type'=>'text', 'null'=>TRUE, 'default'=>NULL],
			];
			$indexes=[
				'primary'=>['type'=>'primary', 'fields'=>['key']],
			];
			$db->create_table(
                $table_name,
				$fields, $indexes,
				Config::get('lab.config_engine')
			);
		}

		if ($tag) {

            $ret = Cache::factory()->get('@TAG');

            if (!$ret) {
                $ret = $db->value('SELECT `val` FROM `'.$table_name.'` WHERE `key`="@TAG"');
            }

            if (is_array($ret)){
                $ret = @serialize($ret);
            }

			$tagged = $ret ? (array) @unserialize($ret) : [];
			if ($tag == '*') {
				foreach ($tagged as & $t) {
					if($value === NULL) {
						unset($t[$name]);
						if (!$t) unset($t);
					}
					else {
						$t[$name]=$value;
					}
				}
			}
			else {
				$tagged[$tag][$name] = $value;
			}

			self::$cached['@TAG'] = $tagged;
			$db->query('REPLACE INTO `'.$table_name.'` (`key`, `val`) VALUES ("@TAG", "%s")', serialize($tagged));

            Cache::factory()->remove('@TAG');
		}
		else {
            //无论是否设置,  删除
            Cache::factory()->remove($name);

			if ($value === NULL) {
				unset(self::$cached[$name]);


				$db->query('DELETE FROM `'.$table_name.'` WHERE `key`="%s"', $name);
			}
			else {
				self::$cached[$name] = $value;
				Cache::factory()->set($name, $value);
				$db->query('REPLACE INTO `'.$table_name.'` (`key`, `val`) VALUES ("%s", "%s")', $name, serialize($value));
			}
		}
	}

	static function save_abbr($e, $object, $new_data) {
		if ($new_data['name'] && class_exists('PinYin')) {
			$schema = ORM_Model::schema($object);
			if (isset($schema['fields']['name_abbr']))
				$object->name_abbr = PinYin::code($new_data['name']);
		}
	}

	static function form($process=NULL) {
		$form = Input::form();
		$form_count = count($_POST);
		$old_form = Session::get_url_specific('form', []);

		if ($old_form['status'][0] == -1) {
			unset($old_form['status'][0]);
		}

        unset($old_form['st']);
        if (!isset($form['st'])) {
            unset($old_form['sort']);
            unset($old_form['sort_asc']);
        }

		if ($form['reset_search']) {
			$old_form = [];
		}

		if ($form['reset_field']) {
			$fields = explode(',', $form['reset_field']);
			foreach ($fields as $field) {
				unset($old_form[$field]);
			}
		}

		if ($process) {
			$process($old_form, $form);
		}
		$form += $old_form;
		Session::set_url_specific('form', $form);

		if ($form_count >0) {
			URI::redirect('');
		}

		return Session::get_url_specific('form', []);
	}

	static function store_form($form) {
		Session::set_url_specific('form', $form);
	}

	static function pagination(& $objects, $start, $per_page, $url=NULL, $token = '', $checkbox_url = '') {
		$start = $start - ($start % $per_page);

		if($start > 0) {
			$last = floor($objects->total_count() / $per_page) * $per_page;
			if ($last == $objects->total_count()) $last = max(0, $last - $per_page);
			if ($start > $last) {
				$start = $last;
			}
			$objects = $objects->limit($start, $per_page);
		} else {
			$objects = $objects->limit($per_page);
		}

		if (isset($_SESSION[$token])) {
            $checkboxs = join(',', $_SESSION[$token]);
        }
        else {
            $checkboxs = '';
        }

		$pagination = Widget::factory('pagination');
		$pagination->set([
            'token' => $token,
            'checkboxs' => $checkboxs,
            'checkbox_url' => $checkbox_url,
			'start' => $start,
			'per_page' => $per_page,
			'total' => $objects->total_count(),
			'url' => $url
		]);

		return $pagination;
	}
}
