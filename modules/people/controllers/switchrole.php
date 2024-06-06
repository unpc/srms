<?php
class Switchrole_AJAX_Controller extends AJAX_Controller
{
    function get_user_role_list () {
        $me = L('ME');
        return $me->get_switch_role();
    }

    function index_switch_role_view() {
        if(L('ME')->id && Switchrole::is_display_select_role()) {
            $role_list = $this->get_user_role_list();
            if (count($role_list) <= 1) {
                foreach ($role_list as $role => $value) {
                    Switchrole::user_select_role($role);
                    Switchrole::user_select_role_id($value);
                    JS::redirect("!people/dashboard");
                }
            } else {
                $view = V('switchrole/switch', []);
                JS::dialog($view, ['title' => I18N::T('people', '切换角色'), 'no_close'=>TRUE]);
            }
        }
    }

    public function index_switch_role_click()
    {
        if (L('ME')->id) {
            $view = V('switchrole/switch', []);
            JS::dialog($view, ['title' => I18N::T('people', '切换角色')]);
        }
    }
    public function index_switch_role_submit()
    {
        JS::close_dialog();
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $form->validate('user_select_role', 'not_empty', I18N::T('people', '请选择切换角色!'));
            if ($form['user_select_role']) {
                // 获取用户已有的角色
                $role_list = $this->get_user_role_list();;
                $user_select_role_id = [];
                // 根据用户选择的角色获取想要的角色ID， 并验证用户选择的角色是否合法
                foreach ($role_list as $role => $value) {
                    if ($role === $form['user_select_role']) {
                        $user_select_role_id = $value;
                    }
                }
                if (count($user_select_role_id) === 0) {
                    $form->set_error('user_select_role', '您选择的角色不合法！');
                }
            }
            if ($form->no_error) {
                Switchrole::user_select_role($form['user_select_role']);
                Switchrole::user_select_role_id($user_select_role_id);
                JS::redirect("!people/dashboard");
            }
        }
    }

    public function index_switch_default_role_click()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        if ($form['role_name']) {
            // 获取用户已有的角色
            $role_list = $this->get_user_role_list();;
            $user_select_role_id = [];
            // 根据用户选择的角色获取想要的角色ID， 并验证用户选择的角色是否合法
            foreach ($role_list as $role => $value) {
                if ($role === $form['role_name']) {
                    $user_select_role_id = $value;
                }
            }

            if (count($user_select_role_id) === 0) {
                JS::alert('设置错误，请重试！');
            }

            $me->input_user_select_role = $form['role_name'];
            $me->input_user_select_role_id = $user_select_role_id;
            if ($me->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, T('默认角色设置成功!'));
                JS::refresh();
                // JS::alert('默认角色设置成功！');
            }
        }
    }
}