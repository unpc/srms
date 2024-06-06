<?php

class ManyFaced_God_AJAX_Controller extends AJAX_Controller
{
    public function index_faceless_submit()
    {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }
        $form = Input::form();

        $user = O('user', $form['id']);
        if (!$user->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的用户信息！'));
            return false;
        }

        $view = V('login_plus:manyfaced_god/faceless_detail', [
            'user' => $user,
        ]);
        JS::dialog($view, ['title' => I18N::T('support', '匹配用户')]);
    }

    public function index_faceless_exec()
    {
        $me = L('ME');
        if (!in_array($me->token, Config::get('lab.admin'))) {
            return;
        }
        $form = Input::form();
        $user = O('user', $form['id']);
        if (!$user->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的用户信息！'));
            return false;
        }

        $db = Database::factory();
        $db->prepare_table('_remember_login', [
            'fields' => [
                'id'=>['type'=>'char(40)', 'null'=>false, 'default'=>''],
                'uid'=>['type'=>'bigint', 'null'=>false, 'default'=>0],
                'mtime'=>['type'=>'int', 'null'=>false, 'default'=>0],
            ],
            'indexes' => [
                'primary'=>['type'=>'primary', 'fields'=>['id']],
                'mtime'=>['fields'=>['mtime']],
            ]
        ]);

        Log::add(strtr('[application] %user_name[%user_id] 切换角色 => %new_user_name[%new_user_id]', [
            '%user_name' => $me->name,
            '%user_id' => $me->id,
            '%new_user_name' => $user->name,
            '%new_user_id' => $user->id,
        ]), 'logon');
        Log::add(strtr('[application] %user_name[%user_id] 切换角色 => %new_user_name[%new_user_id]', [
            '%user_name' => $me->name,
            '%user_id' => $me->id,
            '%new_user_name' => $user->name,
            '%new_user_id' => $user->id,
        ]), 'journal');

        $session_id = $_COOKIE[session_name().'_lt'];
        $db->query('REPLACE INTO `_remember_login` (id, uid, mtime) VALUES ("%s", %d, %d)', $session_id, $user->id, Date::time());
        Lab::check_remember_login();

        Lab::message(LAB::MESSAGE_NORMAL, I18N::T('support', 'valar morghulis, 切换成功'));
        JS::Refresh();
    }
}
