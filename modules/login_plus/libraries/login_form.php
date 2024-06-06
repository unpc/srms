<?php

class Login_Form
{
    static function layout_after_call ($e, $controller) {
        if (!Config::get('login_form.encode_aes', FALSE)) return;
        $controller->add_js('login_plus:aes/crypto');
        $controller->add_js('login_plus:aes/aes');
    }

    static function login_form_extra($e) {
        if (Config::get('login_form.encode', false)) {
            if (Config::get('login_form.encode_aes', false)) {
                $e->return_value .= V('login_plus:login_form/login_aesencode',
                    ["aes_key" => Config::get('login_form.aes_key', ''),
                     "aes_iv" => Config::get('login_form.aes_iv', '')]);
            } else {
                $e->return_value .= V('login_plus:login_form/login_base64encode');
            }
        }
    }

    static function login_form_submit($e, $form) {
       if (Config::get('login_form.encode', false)) {
           if (Config::get('login_form.encode_aes', false)) {
               $form['token'] = self::decrypt_aes($form['token']);
               $form['password'] = self::decrypt_aes($form['password']);
           } else {
               $form['token'] = base64_decode($form['token']);
               $form['password'] = base64_decode($form['password']);
           }
       }
    }

    static function decrypt_aes($str) {
       $response = str_replace(array('-','_'),array('+','/'), $str);
       $mod4 = strlen($response) % 4;
       if ($mod4) {
           $response .= substr('====', $mod4);
       }
       $encrypted = @openssl_decrypt($response, 'AES-128-CBC', Config::get('login_form.aes_key', ''), OPENSSL_ZERO_PADDING, Config::get('login_form.aes_iv', ''));
       return trim($encrypted);
    }
}
