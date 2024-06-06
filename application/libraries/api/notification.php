<?php

/**
应用级别错误代码:
1000: 请求来源非法!
1001: 收件人不存在!
1002: 发件人不存在!
1003: 信息保存失败!
 **/
class API_Notification
{

    public function _auth($signed_token)
    {
        $msg_server = Config::get('messages.server');
        $rpc_token  = Config::get('messages.rpc_token');

        return $signed_token === $rpc_token;
    }

    public function extract_users($signed_token, $receiver, $range)
    {
        if (!self::_auth($signed_token)) {
            throw new API_Exception('请求来源非法!', 1000);
        }

        list($start, $per_page) = $range;

        $ret = [];

        $id = $receiver['id'];
        switch ($receiver['type']) {
            case 'user':
                if (is_array($id)) {
                    if (is_array($id)) $id = implode(',', $id);
                    $ret = array_values(Q("user[id={$id}]")->limit($start, $per_page)->to_assoc('id', 'id'));
                }else {
                    $user = O('user', $id);
                    if ($user->id) {
                        $ret = [$user->id];
                    }
                }
                break;
            case 'all':
                $ret = array_values(Q('user')->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
            case 'group':
                $ret = array_values(Q('(tag#' . $id . ') user')->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
            case 'role':
                if (is_numeric($id)) $role = O('role', ['id' => (int) $id]);
                if (is_numeric($id) || $role->weight > 0) {
                    if (is_array($id)) $id = implode(',', $id);
                    $ret = array_values(Q("role[id={$id}] user")->limit($start, $per_page)->to_assoc('id', 'id'));
                } else {
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
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                $selector = "user[dto=0,{$now}~]";
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'past':
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                $selector = "user[dto!=0][dto<{$now}]";
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'lab_pi':
                            $selector = "lab<pi user[atime][dto=0|dto<{$now}]";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'equipment_charge':
                            $selector = "equipment<incharge user[atime][dto=0|dto<{$now}]";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        case 'need_help':
                            $selector = "role[id={$id}] user";
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                        default:
                            $mt = $role_mts[$key];

                            if ($mt) {
                                reset($mt);
                                $mt_min = key($mt);
                                end($mt);
                                $mt_max = key($mt);
                                $selector = "user[member_type>=$mt_min][member_type<=$mt_max]";
                            } else {
                                $selector = 'user';
                            }
                            $ret = array_values(Q($selector)->limit($start, $per_page)->to_assoc('id', 'id'));
                            break;
                    }
                }
                break;
            case 'lab':
                $lab = O('lab', $id);
                $ret = array_values(Q("$lab user")->limit($start, $per_page)->to_assoc('id', 'id'));
                break;
        }

        return $ret;
    }

    public function send($signed_token, $receiver_id, $noti_form)
    {

        if (!self::_auth($signed_token)) {
            throw new API_Exception('请求来源非法!', 1000);
        }

        $receiver = O('user', $receiver_id);
        if (!$receiver->id) {
            throw new API_Exception('收件人不存在!', 1001);
        }

        /*
        // notification 允许无发件人
        $sender = O('user', $message_form['sender']);
        if (!$sender->id) {
        throw new API_Exception('发件人不存在!', 1002);
        }
         */

        $params        = $noti_form['params'];
        $template_name = $noti_form['conf_key'];
        $sender        = $noti_form['sender'];

        //如果有定制内容的消息就接管出去 不走下面的通用了
        if (Event::trigger("custom_notification.{$template_name}", $receiver, $noti_form)) {
            return true;
        }

        //由于使用api进行消息发送, 故需要考虑locale问题
        $locale = $noti_form['locale'];

        if ($locale) {
            Config::set('system.locale', $locale);
            I18N::shutdown();
            I18N::setup();
        }

        $arr      = explode('|', $template_name);
        $template = Notification::get_template($template_name);
		if (!Config::get('notification.'.$template_name) && !Config::get('notification.'.$arr[0])) {
			$params = array_map(function($v){
				if (preg_match('/\[\[Q:(\w+?)#(\d*)\]\]/', $v, $matches)) {
					return O($matches[1], $matches[2]);
				}
				return $v;
			}, $params);
            call_user_func(['notification_email', 'send'], $sender, [$receiver], $params['#TITLE#'], (string)V($arr[1], $params));
		}
		else {
	        $i18n = $template['i18n_module'] ?: 'application';
	        list($title, $body) = Notification::symbol_to_markup(
				[
					I18N::T($i18n, $template['title']),
					I18N::T($i18n, $template['body'])
					],
				$params,$receiver);

	        $handlers = Notification::get_handlers();
	        foreach ($handlers as $handler) {

	            //如果不允许发送，则跳过当前handler
	            if (!Notification::enable_send($template_name, $handler)) continue;

	            //当前handler用户不接收，跳过当前handler
	            if (!Notification::enable_receive($template_name, $handler, $receiver)) continue;

	            $handler_info = Notification::get_handler_info($handler);

                if($handler_info['class'] != 'Notification_Email' || !Event::trigger('notification.'.$template_name.'.is_already_send_email'))
	            call_user_func([$handler_info['class'], 'send'], $sender, [$receiver], $title, $body);
	        }
		}

		return TRUE;
	}

}
