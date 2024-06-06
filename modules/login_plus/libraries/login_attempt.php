<?php
class Login_Attempt {
    static function before_auth_verify($e, $user) {
        $limitCount = Config::get('login_attempt.limitCount', 0);
        $limitTime = Config::get('login_attempt.limitTime', []);
        if ($limitCount && $limitTime !== [] && $user->id) {
            self::_login_ban($user, $limitTime, $limitCount);
        }
    }

    static function login_field_submit($e, $user) {
        $limitCount = Config::get('login_attempt.limitCount', 0);
        $limitTime = Config::get('login_attempt.limitTime', []);
        if ($limitCount && $limitTime !== [] && $user->id) {
            $attempt = O('login_attempt');
            $attempt->user = $user;
            $attempt->save();
            self::_login_ban($user, $limitTime, $limitCount);
        }
    }

    static function login_success_submit($e, $user) {
        $limitCount = Config::get('login_attempt.limitCount', 0);
        $limitTime = Config::get('login_attempt.limitTime', []);
        if ($limitCount && $limitTime !== [] && $user->id) {
            self::_login_ban($user, $limitTime, $limitCount);
        }
        
        $attempts = Q("login_attempt[user=$user]");
        foreach ($attempts as $attempt) {
            $attempt->delete();
        }
    }

    static private function _login_ban($user, $limitTime, $limitCount){
        $interval = Date::convert_interval($limitTime['value'], $limitTime['format']);
        $time = time() - $interval + 1;
        $attempt_time = Q("login_attempt[user=$user][ctime>=$time]")->total_count();

        if ($attempt_time >= $limitCount) {
            $msgTime = $limitTime['value'] . Date::unit($limitTime['format']);
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('login_plus', '您连续输入错误密码%limitCount次，请在%limitTime后再次尝试', ['%limitCount'=> $limitCount, '%limitTime' => $msgTime]));
            URI::redirect('error/401');
            // throw new Error_Exception;
        }
        return;
    }
}
