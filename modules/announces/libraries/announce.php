<?php
class Announce{

	static function extract_users($announce, $type, $oid=NULL) {
		$now = Date::time();

		$start = 0;
		$per_page = 10;

		$id = $oid;
        $ret = [];

		switch ($type) {
		case 'user':  /* 个别用户 */
			$user = O('user', $id);
			self::extract_user($announce, $user);
			break;
		case 'all':
			for (;;) {
				$users = Q('user')->limit($start, $per_page);
				if (!count($users)) break;
				$start += $per_page;
				foreach ($users as $user) {
					self::extract_user($announce, $user);
				}
			}
			break;
		case 'group':   /*  组织结构  */
			for (;;) {
				$users = Q('(tag_group#'.$id.') user')->limit($start,$per_page);
				if (!count($users)) break;
				$start += $per_page;
				foreach($users as $user) {
					self::extract_user($announce, $user);
				}
			}
    		break;
		case 'role':  /* 角色 */

                $role = O('role', (int) $id);
				if ($id > 0 && $role->weight > 0) {
					for (;;) {
						$users = Q("role[id={$id}] user")->limit($start,$per_page);
						if (count($users) == 0) break;
						$start += $per_page;
						foreach($users as $user) {
							self::extract_user($announce, $user);
						}
					}
				} /* 默认角色 */
				else {

                    $default_roles = Config::get('roles.default_roles');

                    $default_roles_key_name = []; //存储默认role的key和name

                    foreach ($default_roles as $default_role) {
                        $default_roles_key_name[($default_role['key'])] = $default_role['name'];
                    }

                    $key = array_search($role->name, $default_roles_key_name);

                    $role_mts = [];    //角色与member_type的对应数组
                    $weight = 0;
                    foreach(L('ROLES') as $role) {
                        if($role->weight < 0) {
                            $r = $default_roles[$role->weight];
                            if ($r) {
                                if (in_array($r['key'], (array)Config::get('people.disable_member_type'))) {
                                    continue;
                                }
                                if (in_array($r['key'], ['current', 'past']) && ! $GLOBALS['preload']['people.enable_member_date']) {
                                    continue;
                                }
                                $mt_key = $r['member_type_key']?:$r['name'];
                                $role_mts[$r['key']] = User_Model::get_members()[$mt_key];
                            }
                        }
                        $weight ++;
                    }

					switch ($key) {
                        case 'current':
                            if($GLOBALS['preload']['people.enable_member_date']){
                                $selector = "user[dto=0,{$now}~]";
                            }
                            break;
                        case 'past':
                            if($GLOBALS['preload']['people.enable_member_date']){
                                $selector = "user[dto!=0][dto<{$now}]";
                            }
                            break;
                        case 'lab_pi':
                            $selector = "lab<pi user[atime][dto=0|dto<{$now}]";
                            break;
                        case 'equipment_charge':
                            $selector = "equipment<incharge user[atime][dto=0|dto<{$now}]";
                            break;
                        case 'need_help':
                            $selector = "role[id={$id}] user";
                            break;
                        default:
                            $mt = $role_mts[$key];

                            if ($mt) {
                                reset($mt);
                                $mt_min = key($mt);
                                end($mt);
                                $mt_max = key($mt);
                                $selector = "user[member_type>=$mt_min][member_type<=$mt_max]";
                            }
                            else {
                                $selector = 'user';
                            }
                            break;
					}
					for(;;) {
						$users = Q($selector)->limit($start,$per_page);
						if (count($users) == 0) break;
						$start += $per_page;
						foreach($users as $user) {
							self::extract_user($announce, $user);
						}
					}
				}
			break;
        }
 	}
 	static function extract_user($announce, $user) {
		$now = time();
		$user_announce = O('user_announce',['announce'=>$announce,'receiver'=>$user]);
		if($user_announce->id) return;
		$user_announce->announce = $announce;
		$user_announce->receiver = $user;
		$user_announce->sender = $announce->sender;
		$user_announce->is_read = 0;
		$user_announce->save();

        //如果需要send_sms
        if ($announce->send_sms
            &&
            Module::is_installed('sms')
            &&
            class_exists('Notification')
            )  {
            Notification_SMS::send($sender, $user, $announce->title, $announce->content);
        }
	}

	static function send($form, $type) {
		$me = L('ME');
		$announce = O('announce');
		$announce->title = H($form['title']);
		$announce->content = H($form['content']);
		$announce->ctime = time();

		if($form['must_read'] == 'on'){
			$announce->dtstart = Date::get_day_start($form['dtstart']);
			$announce->dtend = Date::get_day_end($form['dtend']);
			$announce->must_read = 1;
		}

        //发送sms
        if (Config::get('announces.send_sms', FALSE) && $form['send_sms']) $announce->send_sms = TRUE;

		$announce->sender = $me;

		switch ($type) {
			case 'all':
                $receiver = ['type'=>'all', 'scope'=>'*'];
				break;
			case 'group':
				$receiver = ['type'=>'group','scope'=>json_decode($form['receiver_group'],true)];
				break;
			case 'user':
				$receiver = ['type'=>'user','scope'=>json_decode($form['receiver_users'],true)];
				break;
			case 'role':
				$receiver = ['type'=>'role','scope'=>json_decode($form['receiver_role'],true)];
				break;
		}

		$announce->receiver = json_encode($receiver);
		if ($announce->save()) {
			return $announce;
		}
    }

    static function notif_callback() {
		$me = L('ME');
		if (!$me->id) return 0;
		// 此处不使用total_count, total_count会进行排序，影响性能
		return Q("user_announce[receiver={$me}][!is_read]")->current()->id ? '&nbsp' : false;
	}

	static function force_read($e, $controller, $method, $params) {
		if($controller instanceof Layout_Controller){
			$now = time();
			$me = L('ME');

			$path = (defined('MODULE_ID') ? '!'.MODULE_ID.'/' :'')
							.Config::get('system.controller_path')
							.'/'
							.Config::get('system.controller_method');

			$must_read = Q("announce[dtstart<={$now}][dtend>={$now}][must_read]<announce user_announce[receiver={$me}][is_read=0]")->total_count();

			if($path !== '!people/index/password' && !strstr($path, '!labs/signup') && (strpos($path,'glogon_action_submit') === false) && $path !== '!equipments/glogon/login' && $path !== '!equipments/glogon/logout'
				&& $must_read && MODULE_ID!= 'announces' 
				&& Input::arg(0) !== 'logout' && Input::arg(0) !== 'error'){
                if (!Switchrole::is_display_select_role()) {
                    URI::redirect('!announces');
                }
            }
		}

	}

    static function on_announce_saved($e, $announce, $old_data, $new_data) {
        if (!$old_data['id']) {

            $old_data = O('announce');
            $old_path = NFS::get_path($old_data, '', 'attachments', TRUE);

            $new_path = NFS::get_path($announce, '', 'attachments', TRUE);

            NFS::move_files($old_path, $new_path);
        }
    }

    static function delete_attachments($e) {
	    $object = O('announce');
        $object_path = NFS::get_path($object, '', 'attachments', TRUE);

        File::rmdir($object_path);
    }
}
