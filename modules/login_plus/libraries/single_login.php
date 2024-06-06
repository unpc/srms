<?php
class Single_Login {
    static function auth_login ($e, $token) {
        if (!Lab::get('login.single_login', FALSE)) return;
        $session_id = session_id();
        $cache = Cache::factory('redis');
        // 每次登录，记录token最后一次登录的session_id，供heartbeat对比用
        $cache->set('user_session_' . $token, $session_id, 60);
    }

    static function layout_after_call ($e, $controller) {
        if (!Auth::logged_in() || !Lab::get('login.single_login', FALSE)) return;
        $controller->add_js('login_plus:bind_single_login', FALSE);
    }

    static function single_login_heartbeat () {
        if (!Lab::get('login.single_login', FALSE)) return;

        $me = L('ME');
        $cache = Cache::factory('redis');
        $token = $me->token;
        if (!$token) return;
        $current_session_id = $cache->get('user_session_' . $token);
        if (!$current_session_id) return;
        $session_id = session_id();


        if ($current_session_id != $session_id) {
            // 记录异常登录，当后登陆用户heartbeat时弹出提醒，仅记录一个heartbeat的时间，以防常驻内存导致每登录一次弹出一次提醒
            $cache->set('user_login_warning_'.$current_session_id, TRUE, 31);
            Auth::logout();
            JS::run(JS::smart()->jQuery->propbox((string)V('login_plus:single_login/alert', [
            ]), 150, 300, 'right_bottom'));
        }
        elseif ($cache->get('user_login_warning_'.$session_id)) {
            $cache->set('user_login_warning_'.$current_session_id, FALSE);
            JS::run(JS::smart()->jQuery->propbox((string)V('login_plus:single_login/warning', [
            ]), 150, 300, 'right_bottom'));
        }
    }
}
