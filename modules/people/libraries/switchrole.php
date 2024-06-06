<?php

class Switchrole {
    static function get_session_id(){
        $session_name = Config::get('system.session_name');
        if (!Auth::token()) {
            $session_id = $_SERVER['HTTP_GINIROLECOOKIE'];
        } else {
            $session_id = $_COOKIE[$session_name];
        }
        return $session_id;
    }
    static function user_select_role($input_user_select_role = NULL) {
        $me = L('ME');
        $cache = Cache::factory('redis');
        $session_id = self::get_session_id();
        if ($input_user_select_role === NULL) {
            $input_user_select_role = $cache->get("{$session_id}_role")?:NULL;
            if (!$cache->get("{$session_id}_role") && $me->input_user_select_role) {
                $key = $me->input_user_select_role;
                $role_list = $me->get_switch_role();
                if (!isset($role_list[$key])) {
                    $input_user_select_role = NULL;
                    $me->input_user_select_role = NULL;
                    $me->save();
                } else {
                    $input_user_select_role = $key;
                }
            }
        } else {
            if (!$me->input_user_select_role) {
                $me->input_user_select_role = $input_user_select_role;
                $me->save();
            }
        }
        $cache->set("{$session_id}_role", $input_user_select_role, 60*60);
        return $cache->get("{$session_id}_role");
    }

    static function user_select_role_id($input_user_select_role_id = NULL) {
        if (PHP_SAPI == 'cli') {
            return;
        }
        $me = L('ME');
        $cache = Cache::factory('redis');
        $session_id = self::get_session_id();
        if ($input_user_select_role_id === NULL) {
            $input_user_select_role_id = $cache->get("{$session_id}_role_id")?:($me->input_user_select_role_id?:NULL);
        } else {
            if ($me->id && !$me->input_user_select_role_id) {
                $me->input_user_select_role_id = $input_user_select_role_id;
                $me->save();
            }
        }
        $cache->set("{$session_id}_role_id", $input_user_select_role_id, 60*60);
        return $cache->get("{$session_id}_role_id");
    }

    static function unset_switch_role(){
        $cache = Cache::factory('redis');
        $session_id = self::get_session_id();
        $cache->remove("{$session_id}_role");
        $cache->remove("{$session_id}_role_id");
    }

    static function isAdmin()
    {
        $me = L('ME');
        $admin_tokens = array_map("Auth::normalize", array_merge((array)Config::get('lab.admin', []), (array)Lab::get('lab.admin', [])));
        if (in_array($me->token, $admin_tokens)){
            return TRUE;
        }
        return FALSE;
    }

    static function is_display_select_role(){
        $me = L('ME');
        $is_display = FAlSE;
        if (self::user_select_role() === NULL &&  !self::isAdmin()) {
            $is_display = TRUE;
        }
        return $is_display;
    }
    
    static function switch_role($e, $controller, $method, $params){
        if(L('ME')->id && $controller instanceof Layout_Controller) {
            $path = (defined('MODULE_ID') ? '!' . MODULE_ID . '/' : '')
                . Config::get('system.controller_path')
                . '/'
                . Config::get('system.controller_method');

            if ($path !== '!people/index/password' && !strstr($path, '!labs/signup') && self::is_display_select_role()
                && !strstr($path, '!people/dashboard')
                && Input::arg(0) !== 'logout'
                && Input::arg(0) !== 'error'
            ) {
                URI::redirect('!people/dashboard');
            }
        }
    }
}
