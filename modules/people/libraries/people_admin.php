<?php

class People_Admin
{

    public static function setup()
    {
        if(L('ME')->is_allowed_to('管理角色', 'user') && !Event::trigger('db_sync.need_to_hidden', 'people')){
            Event::bind('admin.index.tab', 'People_Admin::_primary_tab');
        }
        /*
        NO.TASK#299(guoping.zhang@2010.11.09)
        成员的locale设置信息转到系统设置页面的偏好设置
         */
        Event::bind('admin.preferences.tab', 'People_Admin::_edit_locale_tab');
        Event::bind('admin.preferences.tab', 'People_Admin::_default_group_tab');
        Event::bind('admin.preferences.tab', 'People_Admin::_edit_privacy_tab');
    }

    public static function _primary_tab($e, $tabs)
    {
        if (!Module::is_installed('uno') && !Event::trigger('db_sync.need_to_hidden', 'people')) {
            Event::bind('admin.index.content', 'People_Admin::_primary_content', 0, 'people');

            $tabs->add_tab('people', [
                'url'    => URI::url('admin/people'),
                'title'  => I18N::T('people', '成员管理'),
                'weight' => 40,
            ]);
        }

    }

    public static function _primary_content($e, $tabs)
    {
        Event::bind('admin.people.content', 'People_Admin::_secondary_email_content', 1, 'email');
        Event::bind('admin.people.content', 'People_Admin::_secondary_limit_content', 2, 'limit');
        Event::bind('admin.people.content', 'People_Admin::_secondary_role_content', 3, 'role');

        $tabs->content = V('admin/view');

        $secondary_tabs = Widget::factory('tabs');

        $tabs->content->secondary_tabs = $secondary_tabs;

        if (!Module::is_installed('uno') && Module::is_installed('labs')) {
            $tabs->content->secondary_tabs
                ->add_tab('signup', [
                    'url'   => URI::url('admin/people.signup'),
                    'title' => I18N::T('people', '注册须知'),
                ]
                );

            Event::bind('admin.people.content', 'People_Admin::_secondary_signup_content', 0, 'signup');
        }

        $tabs->content->secondary_tabs
            ->add_tab('limit', [
                'url'   => URI::url('admin/people.limit'),
                'title' => I18N::T('people', '帐号限制'),
            ])
            ->add_tab('role', [
                'url'   => URI::url('admin/people.role'),
                'title' => I18N::T('people', '角色查看'),
            ])
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.people.tab')
            ->content_event('admin.people.content');

        Event::trigger('admin.people.secondary_tabs', $secondary_tabs);

        /*if (Config::get('system.enable_notifications', true)) {
        $tabs->content->secondary_tabs
        ->add_tab('email', [
        'url'   => URI::url('admin/people.email'),
        'title' => I18N::T('people', '通知提醒'),
        ]);
        }*/

        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);

    }

    public static function _secondary_limit_content($e, $tabs)
    {
        $reserved = Lab::get('people.reserved.token', Config::get('people.reserved.token'));
        if (!is_array($reserved)) {
            $reserved = [];
        }

        if (Input::form('del_resv')) {
            $form = Form::filter(Input::form());
            if (in_array($form['del_resv'], $reserved) && !in_array($form['del_resv'], Config::get('people.reserved.token'))) {
                $key = array_search($form['del_resv'], $reserved);
                unset($reserved[$key]);
                Lab::set('people.reserved.token', $reserved);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('people', '保留帐号 "%name" 删除成功!', ['%name' => $form['del_resv']]));

                Log::add(strtr('[people] %user_name[%user_id]修改了系统设置中的帐号限制', ['%user_name' => L('ME')->name, '%user_id' => L('ME')->id]), 'journal');

            }
            URI::redirect($_SESSION['system.current_layout_url']);
        } elseif (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('reserved', 'not_empty', I18N::T('people', '保留帐号不能为空字符！'));
            if ($form->no_error) {
                if (self::check_token($form['reserved'])) {
                    if (!in_array($form['reserved'], $reserved)) {
                        $reserved[] = $form['reserved'];
                        Lab::set('people.reserved.token', $reserved);
                    } else {
                        $form->set_error(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的帐号在系统中已存在!'));
                    }
                    if ($form->no_error) {
                        /* 记录日志 */
                        Log::add(strtr('[people] %user_name[%user_id]修改了系统设置中的帐号限制', ['%user_name' => L('ME')->name, '%user_id' => L('ME')->id]), 'journal');

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '保留帐号添加成功!'));
                    }
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的帐号在系统中已存在!'));
                }
            }
        }
        $tabs->content = V('people:admin/limit', ['reserved' => $reserved, 'form' => $form]);
    }

    // 检测token在系统中是否已经存在
    public static function check_token($token)
    {
        $backends = Config::get('auth.backends');
        foreach ($backends as $backend) {
            $token = Auth::make_token($token, $backends['handler']);
            if (O('user', ['token' => $token])->id) {
                return false;
            }
        }
        return true;
    }

    public static function _secondary_email_content($e, $tabs)
    {

        $configs = (array) Config::get('people.people_admin.email.content');

        $vars = [];
        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', I18N::T('people', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('people', '消息内容不能为空！'));
                $vars['form'] = $form;
                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];

                    foreach (Lab::get('notification.handlers') as $k => $v) {
                        if (isset($form['send_by_' . $k])) {
                            $value = $form['send_by_' . $k];
                        } else {
                            $value = 0;
                        }
                        if (isset($config['receive_by'][$k])) {
                            $tmp['receive_by'][$k] = $config['receive_by'][$k];
                        }
                        $tmp['send_by'][$k] = $value;
                    }
                    Lab::set($form['type'], $tmp);

                    /* 记录日志 */
                    Log::add(strtr('[people] %user_name[%user_id]修改了系统设置中的通知邮件', ['%user_name' => L('ME')->name, '%user_id' => L('ME')->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '内容修改成功'));
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '恢复系统默认设置成功'));
            }
        }
        $views         = Notification::preference_views($configs, $vars, 'people');
        $tabs->content = $views;

    }

    public static function _secondary_signup_content($e, $tabs)
    {
        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('signup_title', 'not_empty', I18N::T('people', '标题不能为空!'))
                ->validate('signup_doc', 'not_empty', I18N::T('people', '内容不能为空!'));

            if ($form->no_error) {
                Lab::set('people.signup.doc', $form['signup_doc']);
                Lab::set('people.signup.title', $form['signup_title']);
                /* 记录日志 */
                Log::add(strtr('[people] %user_name[%user_id]修改了系统设置中的注册须知', ['%user_name' => L('ME')->name, '%user_id' => L('ME')->id]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '内容修改成功'));
            }
        }

        $tabs->content = V('people:admin/signup', [
            'form'         => $form,
            'signup_doc'   => I18N::T('people', Lab::get('people.signup.doc')),
            'signup_title' => I18N::T('people', Lab::get('people.signup.title')),
        ]);
    }

    public static function _secondary_role_content($e, $tabs)
    {
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            foreach ($form['role_privacy'] as $role_id => $privacy) {
                $role = O('role', $role_id);

                if (in_array($privacy, array_flip(Role_Model::$privacy))) {
                    $role->privacy = $privacy;
                } else {
                    $role->privacy = Role_Model::PRIVACY_GROUP;
                }

                $role->save();
            }

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '角色权限隐私设置成功!'));
        }

        $tabs->content = V('people:admin/role', ['form' => $form]);
    }

    /*
    NO.TASK#299(guoping.zhang@2010.11.09)
    成员的locale设置信息转到系统设置页面的偏好设置
     */
    public static function _edit_locale_tab($e, $tabs)
    {
        if (Module::is_installed('uno')) {
            return;
        }
        Event::bind('admin.preferences.content', 'People_Admin::_edit_locale_tab_content', 0, 'locale');
        $tabs
            ->add_tab('locale', [
                'url'   => URI::url('admin/preferences.locale'),
                'title' => I18N::T('people', '区域设置'),
            ]);
    }

    public static function _edit_locale_tab_content($e, $tabs)
    {
        $me = L('ME');

        $locale_arr = Config::get('system.locales');
        $timezone   = Lab::get('system.timezone') ?: (Config::get('system.timezone') ?: date_default_timezone_get());

        if (Input::form('submit')) {

            $form = Form::filter(Input::form());

            if ($me->access('管理所有内容')) {
                if (!$form['timezone'] || !in_array($form['timezone'], timezone_identifiers_list())) {
                    $form->set_error('timezone', I18N::T('people', '请正确填写时区!'));
                }
            }

            if ($form->no_error) {
                $me->locale = $form['locale'];
                if ($me->save()) {
                    I18N::shutdown();
                    if (L('ME')->id == $me->id) {
                        $_SESSION['system.locale'] = $me->locale;
                        Config::set('system.locale', $me->locale);
                    }
                    I18N::setup();
                }

                if ($form['timezone'] && $me->access('管理所有内容')) {
                    Lab::set('system.timezone', $form['timezone']);
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '更新成功!'));
                URI::redirect(URI::url('admin/preferences.locale'));
            }

        }
        $tabs->content = V('people:profile/edit.locale', ['locale_arr' => $locale_arr, 'timezone' => $timezone, 'form' => $form]);
    }

    public static function _default_group_tab($e, $tabs)
    {
        Event::bind('admin.preferences.content', 'People_Admin::_default_group_tab_content', 0, 'default_group');
        $tabs
            ->add_tab('default_group', [
                'url'   => URI::url('admin/preferences.default_group'),
                'title' => I18N::T('people', '默认机构'),
            ]);
    }

    public static function _default_group_tab_content($e, $tabs)
    {
        $me         = L('ME');
        $group_root = Tag_Model::root('group');
        $mine_root  = $group_root;
        if ($GLOBALS['preload']['roles.manage_subgroup_perm'] && $me->group->id && !$me->access('管理所有内容')) {
            $mine_root = $me->group;
        }

        $form = Input::form();
        if ($form['submit']) {
            $me->default_group_id = $form['group_id'];
            $me->save();

            Log::add(strtr('[people] %user_name[%user_id]成功保存偏好设置->默认机构', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '默认机构设置成功!'));
        }

        $default_group = O('tag_group', ['root' => $group_root, 'id' => $me->default_group_id]);
        if ($GLOBALS['preload']['roles.manage_subgroup_perm'] && $me->group->id && !$me->access('管理所有内容')) {
            $tabs->content = V('people:preferences/default_group', ['user' => $me, 'default_group' => $default_group, 'group_root' => $mine_root]);
        } else {
            $tabs->content = V('people:preferences/default_group', ['user' => $me, 'default_group' => $default_group, 'group_root' => $group_root]);
        }
    }

    public static function _edit_privacy_tab($e, $tabs)
    {
        Event::bind('admin.preferences.content', 'People_Admin::_edit_privacy_tab_content', 0, 'edit_privacy');
        $tabs
            ->add_tab('edit_privacy', [
                'url'   => URI::url('admin/preferences.edit_privacy'),
                'title' => I18N::T('people', '隐私设置'),
            ]);
    }

    public static function _edit_privacy_tab_content($e, $tabs)
    {
        $me = L('ME');

        $form = Input::form();

        if ($form['submit']) {

            $privacy = $form['privacy'];

            if (!in_array($privacy, array_keys(User_Model::$privacy))) {
                $e->return_value = false;
            }

            $userinfo = O('user_info',['user'=>$me]);
            $userinfo->privacy = $privacy;
            $userinfo->default_group_id = $privacy->default_group_id ?: 0;
            if ($userinfo->save()) {
                if (Config::get('lab.modules')['app']) {
                    $user = O('user',$me->id);
                    $user->privacy = $privacy;
                    $user->name = $user->name;
                    $user->save();
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '您的隐私设置保存成功！'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您的隐私设置保存失败！'));
            }

        }

        $tabs->content = V('people:preferences/edit_privacy', ['user' => $me]);
    }

}
