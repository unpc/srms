<?php

class EQ_Glogon {

    static function setup() {
        Event::bind('profile.edit.tab', "EQ_Glogon::edit_glogon");
    }

    static function edit_glogon($e, $tabs) {
        $user = $tabs->user;

        Event::bind('profile.edit.content', "EQ_Glogon::edit_glogon_content", 0, 'glogon');
        $tabs->add_tab('glogon', [
            'weight' => 80,
            'url' => $user->url('glogon', NULL, NULL, 'edit'),
            'title' => I18N::T('eq_glogon', '客户端密码设置')
        ]);
    }

    static function edit_glogon_content($e, $tabs) {
        $user = $tabs->user;

        $form = Form::filter(Input::form());

        if (Input::form('submit')) {
            $form->validate('glogon_pass', 'not_empty', I18N::T('eq_glogon', '客户端密码不能为空！'));
            $form->validate('glogon_pass', 'length(6, 24)', I18N::T('eq_glogon', '填写的密码不能小于6位, 最长不能大于24位!'));

            if ($form->no_error) {
                $glogon_pass = $form['glogon_pass'];
                $user->glogon_pass = crypt($glogon_pass, Config::get('eq_glogon.salt'));
                if ($user->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_glogon', '更新密码成功'));
                }
            }
        }

        $tabs->content = V('eq_glogon:edit.glogon', [
            'form'=>$form
        ]);
    }

    static function verify($token, $password){
        $user = O('user', ['token' => $token]);
        $glogon_pass = $user->glogon_pass;
        
        if ($glogon_pass) {
            return $glogon_pass == crypt($password, Config::get('eq_glogon.salt'));
        }

        return FALSE;
    }

    static function save_extra_field($e, $user, $form) {
        if (isset($form['glogon_pass']) && $form['glogon_pass']) {
            $glogon_pass = $form['glogon_pass'];
            $user->glogon_pass = crypt($glogon_pass, Config::get('eq_glogon.salt'));
        }
    }
}