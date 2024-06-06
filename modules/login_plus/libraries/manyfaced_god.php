<?php

class ManyFaced_God
{
    public static function setup($e, $controller, $method, $params)
    {
        if (Module::is_installed('uno')) return;
        Event::bind('admin.support.tab', ['ManyFaced_God', '_faceless_tab'], 15, 'faceless');
        Event::bind('admin.support.content', ['ManyFaced_God', '_faceless_content'], 15, 'faceless');
    }

    public static function _faceless_tab($e, $tabs)
    {
        if (Module::is_installed('uno')) return;
        $me = L('ME');
        $tabs->add_tab('faceless', [
            'url' => URI::url('admin/support.faceless'),
            'title' => I18N::T('support', '切换账号'),
            'weight' => 120,
        ]);
    }

    public static function _faceless_content($e, $tabs)
    {
        if (Module::is_installed('uno')) return;
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }
        // 强制使Lab::check_remember_login() 生效
        $_SESSION['remember_login.checked'] = false;
        $session_id = $_COOKIE[session_name().'_lt'];
        if (!$session_id) {
            Lab::message(LAB::MESSAGE_NORMAL, I18N::T('support', '登录时选择"记住登录", 才可使本功能生效'));
            return;
        }

        $select = $tabs->selected;
        $tabs->content = V('login_plus:manyfaced_god/faceless');
    }
}
