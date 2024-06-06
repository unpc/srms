<?php

class Gapper_User
{

    static function edit_gapper_user($e, $tabs)
    {
        $user = $tabs->user;

        Event::bind('profile.edit.content', "Gapper_User::edit_gapper_user_content", 0, 'gapper');
        $tabs->add_tab('gapper', [
            'weight' => 80,
            'url' => $user->url('gapper', NULL, NULL, 'edit'),
            'title' => I18N::T('eq_glogon', '统一身份用户绑定')
        ]);
    }

    static function edit_gapper_user_content($e, $tabs)
    {
        $user = $tabs->user;
        $form = Form::filter(Input::form());

        if (Input::form('submit')) {
            $gapper = isset($form['gapper_info']) ? json_decode($form['gapper_info'], true) : [];
            $form->validate('gapper_info', 'not_empty', I18N::T('eq_glogon', '请选择要绑定的用户！'));
            $other_user = Q("user[gapper_id={$gapper['gapper_id']}][id!={$user->id}]");
            if ($other_user->total_count()) {
                $form->set_error("gapper_info", I18N::T('people', '此统一认证已经被其他用户绑定!'));
            }
            Event::trigger('bind.gapperuser.extra.form.validate', $form, $gapper, $user);

            if ($form->no_error) {
                $ori = $user->gapper_id ?: 0;
                $user->gapper_id = $gapper['gapper_id'];

                $lu = O('gapper_user', ['gapper_id' => $gapper['gapper_id']]);
                $lu->ref_no = $gapper['gapper_ref_no'];
                $lu->email = $gapper['gapper_email'];
                $lu->avatar = $gapper['gapper_avatar'];
                $lu->name = $gapper['gapper_name'];
                $lu->gapper_id = $gapper['gapper_id'];
                $lu->save();

                if ($user->save()) {
                    Event::trigger('bind.gapperuser.extra.form.post_submit', $form, $gapper, $user);
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_glogon', '绑定成功'));
                    Log::add(strtr('[gapper] %m_name[%m_id] 于 %date 变更用户%user_name[%user_id]的gapper信息,gapper_id由%ori变更为%new', [
                        '%m_name' => L('ME')->name,
                        '%m_id'   => L('ME')->id,
                        '%date'      => Date::format(time(), 'Y/m/d H:m:s'),
                        '%user_name' => $user->name,
                        '%user_id'   => $user->id,
                        '%ori' => $ori,
                        '%new'   => $gapper['gapper_id'],
                    ]), 'gapper');
                }
            }
        }
        if (Input::form('cancel')) {
            $user->gapper_id = 0;
            $user->save();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_glogon', '解绑成功'));
            Log::add(strtr('[gapper] %m_name[%m_id] 于 %date 解绑了用户%user_name[%user_id]的gapper信息', [
                '%m_name' => L('ME')->name,
                '%m_id'   => L('ME')->id,
                '%date'      => Date::format(time(), 'Y/m/d H:m:s'),
                '%user_name' => $user->name,
                '%user_id'   => $user->id,
            ]), 'gapper');
        }

        $tabs->content = V('gateway:connect_gapper_user', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    static function get_remote_user($condition = [])
    {
        $condition['status'] = 4; // 正常用户
        $users = Gateway::getRemoteUser($condition);
        return $users;
    }

    static function user_links($e, $user, $links, $mode)
    {

        $me = L('ME');
        switch ($mode) {
            case 'view':
                if ($me->id == $user->id) {
                    $links['wechat'] = [
                        'html' => (string) V('gateway:gappet_user_wechat', ['user' => $user]),
                    ];
                }
        }
    }

    static function get_user($e, $token)
    {
        list($identity, $source) = Auth::parse_token($token);
        // 根据token 获取用户
        $user = Gateway::getRemoteUser([
            'identity' => $identity,
            'source' => $source
        ]);
        if (is_array($user) && isset($user['id'])) {
            $e->return_value = O('user', ['gapper_id' => $user['id']]);
            return;
        }
        $e->return_value = O('user');
    }
}
