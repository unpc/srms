<?php

class Uno
{

    const ROLE_TYPE_USER = 'user';
    const ROLE_TYPE_INCHARGE = 'incharge';
    const ROLE_TYPE_ADMIN = 'admin';

    public static $roleStr = [
        self::ROLE_TYPE_USER => '当前用户',
        self::ROLE_TYPE_INCHARGE => '仪器负责人',
        self::ROLE_TYPE_ADMIN => '中心管理员',
    ];

    public static function login_view($e)
    {
        $e->return_value = V('uno:uno_login');
        return true;
    }

    static function rewrite_base_layout($e)
    {
        $me = L('ME');
        if (!$me->id) {
            $e->return_value = null;
            return true;
        }

        $e->return_value = V('uno:special_layout');
        return false;
    }

    public static function get_remote_user($e, $token)
    {
        list($token, $backend) = Auth::parse_token($token);
        $server = Config::get('gateway.server');
        $rest = new REST($server['url']);
        $data = $rest->get('current_user', ['gapper-oauth-token' => $_SESSION['gapper_oauth_token']]);
        $user = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'ref_no' => $data['ref_no'],
            'avatar' => $data['avatar'],
        ];
        if ($user) {
            $e->return_value = $user;
            return false;
        }
    }

    static function extra_roles($e, $user, $user_roles)
    {
        $me = L('ME');
        //只有登录是本人时候才会加载
        if ($me->id == $user->id) {
            $default_roles = (array)Config::get('roles.default_roles');
            $currentRole = $_SESSION["lab.admin.uno"];
            if ($currentRole == self::ROLE_TYPE_INCHARGE) {
                foreach ($default_roles as $rid => $r) {
                    if ($r['name'] == '仪器负责人') {
                        $role = O('role', ['weight' => $rid]);
                        if ($role->id) $user_roles[$role->id] = $role->id;
                        break;
                    }
                }
            }

            if ($currentRole == self::ROLE_TYPE_USER) {
                foreach ($default_roles as $rid => $r) {
                    if ($r['name'] == '目前成员') {
                        $role = O('role', ['weight' => $rid]);
                        if ($role->id) $user_roles[$role->id] = $role->id;
                        break;
                    }
                }
            }
        }
        $e->return_value = $user_roles;
        return FALSE;
    }

    static function on_enumerate_user_perms($e, $user, $perms)
    {
        //只有登录是本人时候才会加载
        if (!$user->id || L('ME')->id != $user->id) return;

        $me = L('ME');
        $currentRole = $_SESSION["lab.admin.uno"];
        $isAdmin = $currentRole == self::ROLE_TYPE_ADMIN;
        if ($isAdmin) {
            $perms['管理所有内容'] = 'on';
        }
    }


    public static function check_user_stat($e)
    {
        $me = L('ME');
        if ($me->id) {
            $form = Input::form();
            if ($form['uno'] && $form['role'] && array_key_exists($form['role'], self::$roleStr)) {
                $_SESSION["lab.admin.uno"] = $form['role'];
            } elseif($form['uno'] && !$form['role']) {
                $_SESSION["lab.admin.uno"] = self::ROLE_TYPE_USER;
            }

            $currentRole = $_SESSION["lab.admin.uno"];
            $rolename = $currentRole == 'user' ? '普通用户' : ($currentRole == 'admin' ? '中心管理员' : '仪器负责人');
            $role = O('role', ['name' => $rolename]);
            Switchrole::user_select_role($rolename);
            Switchrole::user_select_role_id($role->id);
            return false;
        }
    }

    public static function admin_setup($e, $tabs)
    {
        Event::bind('admin.index.content', 'Uno::_index_system_user_notification', 0, 'system_user_notification');
        $tabs
            ->add_tab('system_user_notification', [
                'title' => T('消息提醒'),
                'url' => URI::url('admin/system_user_notification'),
            ]);
    }

    public static function _index_system_user_notification($e, $tabs)
    {
        $form          = Input::form();
        $tabs->content = V('admin/notification/types', ['types' => Config::get('notification.classification')]);
    }

    public static function get_uno_entries()
    {
        if (count(Config::get('gateway.entries'))) {
            $entriesConfigs = Config::get('gateway.entries');
            foreach ($entriesConfigs as $key => &$entriesConfig) {
		        if (!defined('CLI_MODE') && Config::get("uno.join_group")) {
                    $me = L('ME');
                    $default_lab = Lab_Model::default_lab();
			        if ($me->id && $me->gapper_id && !Q("$me lab[id!=$default_lab->id]")->total_count()) {
				        $entriesConfig['redirect'] = Config::get("uno.join_group_url")."?gapper_id=$me->gapper_id";
			        }
		        }
            }
            return $entriesConfigs;
        } else {
            $entriesConfigs = Config::get('uno_entries');
            $unsetEntries = Config::get('gateway.unset_entries', []);
            foreach ($entriesConfigs as $key => &$entriesConfig) {
                if (isset($entriesConfig['redirect_url'])) {
                    $entriesConfig['redirect'] = $entriesConfig['redirect_url'];
                } else {
                    $entriesConfig['redirect'] = URI::url($entriesConfig['redirect']);
                }
		        if (!defined('CLI_MODE') && Config::get("uno.join_group")) {
                    $me = L('ME');
                    // $default_lab = Lab_Model::default_lab();
                    // if ($me->id && $me->gapper_id && !Q("$me lab[id!=$default_lab->id]")->total_count()) {
                    if ($me->id && $me->gapper_id && !Q("$me lab[gapper_id]")->total_count()) {
				        $entriesConfig['redirect'] = Config::get("uno.join_group_url")."?gapper_id=$me->gapper_id";
			        }
		        }
                if (in_array($key, $unsetEntries)) {
                    unset($entriesConfigs[$key]);
                }
            }
            return $entriesConfigs;
        }
    }

    public static function user_unique_info($user)
    {
        if ($user->email) {
            $o_user = o("user", ["email" => $user->email]);
            if ($o_user->id && $o_user->gapper_id != $user->gapper_id) {
                $o_user->email = "{$o_user->email}_{$o_user->gapper_id}";
                $o_user->save();
            }
        }
        if ($user->ref_no) {
            $o_user = o("user", ["ref_no" => $user->ref_no]);
            if ($o_user->id && $o_user->gapper_id != $user->gapper_id) {
                $o_user->ref_no = "{$o_user->ref_no}_{$o_user->gapper_id}";
                $o_user->save();
            }
        }
        if ($user->token) {
            $o_user = o("user", ["token" => $user->token]);
            if ($o_user->id && $o_user->gapper_id != $user->gapper_id) {
                $o_user->token = "{$o_user->token}_{$o_user->gapper_id}";
                $o_user->save();
            }
        }
    }
}
