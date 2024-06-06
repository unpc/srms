<?php

class Admin_Controller extends Layout_Controller
{

    public function index($tab = null)
    {
        $this->layout->title = T('系统设置');
        $this->layout->body  = V('admin/body');

        $tabs = Widget::factory('tabs');

        //if (L('ME')->access('管理所有内容')) {
        if (!Module::is_installed('uno') && !Event::trigger('db_sync.need_to_hidden', 'system')) {
            Event::bind('admin.index.content', [$this, '_index_preferences'], 0, 'preferences');
            $tabs
                ->add_tab('preferences', [
                    'title' => T('偏好设置'),
                    'url' => URI::url('admin/preferences'),
                ]);
        }
        //}
        if ( L('ME')->access('管理所有内容') && !Event::trigger('db_sync.need_to_hidden', 'reminder')) {
            Event::bind('admin.index.content', [$this, '_index_reminder'], 0, 'reminder');
            $tabs->add_tab('reminder', [
                'title' => T('通知设置'),
                'url' => URI::url('admin/reminder'),
                'weight' => 20,
            ]);
        }

        // if ($GLOBALS['preload']['tag.group_limit'] >= 0 && L('ME')->access('管理组织机构')) {
        if (
            $GLOBALS['preload']['tag.group_limit'] >= 0
            && L('ME')->access('管理组织机构')
            && !Event::trigger('db_sync.need_to_hidden', 'tag_group')
            && !Module::is_installed('uno') 
        ) {
            Event::bind('admin.index.content', [$this, '_index_groups'], 0, 'groups');
            $tabs->add_tab('groups', [
                'title' => T('组织机构'),
                'url'   => URI::url('admin/groups'),
                'weight' => 100,
            ]);
        }

        if (L('ME')->access('管理所有内容')) {
            Event::bind('admin.index.content', [$this, '_index_columns'], 0, 'columns');
            $tabs->add_tab('columns', [
                'title' => T('列表管理'),
                'url' => URI::url('admin/columns'),
                'weight' => 100,
            ]);
        }

        if (L('ME')->access('管理地理位置')) {
            Event::bind('admin.index.content', [$this, '_index_locations'], 0, 'locations');
            $tabs->add_tab('locations', [
                'title' => T('地理位置'),
                'url'   => URI::url('admin/locations'),
                'weight' => 100,
            ]);
        }

        if ((in_array(L('ME')->token, Config::get('lab.admin', [])) || Event::trigger('admin.index.tab.import_data'))
            && !Event::trigger('db_sync.need_to_hidden', 'import')
        ) {
            Event::bind('admin.index.content', [$this, '_index_import'], 0, 'import');
            $tabs->add_tab('import', [
                'title'  => T('数据导入'),
                'url'    => URI::url('admin/import.equipments'),
                'weight' => '111',
            ]);
        }

        $tabs->tab_event('admin.index.tab')
            ->content_event('admin.index.content')
            ->select($tab);

        $this->add_css('tag_sortable mall');

        $this->layout->body->primary_tabs = $tabs;

    }

    public function _index_import($e, $tabs)
    {

        $me            = L('ME');
        $tabs->content = V('admin/import');
        $this->add_js('collapse')->add_css('collapse');
        $tabs->content->secondary_tabs = Widget::factory('tabs');

        if (in_array(L('ME')->token, Config::get('lab.admin'))) {
            Event::bind('admin.import.content', 'Import::index', 0, 'equipments');
            $tabs->content->secondary_tabs
                ->set('class', 'secondary_tabs')
                ->add_tab('equipments', [
                    'title'=>T('导入仪器'),
                    'url'=> URI::url('admin/import.equipments'),
                ]);
            if (!People::perm_in_uno()) {
                Event::bind('admin.import.content', 'Import::index', 0, 'users');
                Event::bind('admin.import.content', 'Import::index', 0, 'labs');
                Event::bind('admin.import.content', 'Import::index', 0, 'cardnos');
                $tabs->content->secondary_tabs
                ->set('class', 'secondary_tabs')
                ->add_tab('users', [
                    'url'=>URI::url('admin/import.users'),
                    'title'=> T('导入用户'),
                ])
                ->add_tab('labs', [
                    'title'=>T('导入课题组'),
                    'url'=>URI::url('admin/import.labs'),
                ])
                ->add_tab('cardnos', [
                    'title'=>T('导入卡号'),
                    'url'=>URI::url('admin/import.cardnos'),
                ]);
            }
        }
        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs
            ->tab_event('admin.import.tab')
            ->content_event('admin.import.content');
        $import_tab_can = new ArrayIterator;
        Event::trigger('admin.index.tab.import_tab_can', $import_tab_can);
        if (!$tabs->content->secondary_tabs->get_tab($params[1]) && !in_array($params[1], (array)$import_tab_can)) {
            //默认地址不存在，跳转到新的地址
            URI::redirect(URI::url('admin/import.'.$import_tab_can[0]));
        }
        $tabs->content->secondary_tabs->select($params[1]);
    }

    // 偏好设置 primary_tab
    public function _index_preferences($e, $tabs)
    {

        $me            = L('ME');
        $tabs->content = V('admin/view');

        Event::bind('admin.preferences.content', [$this, '_preference_sbmenu_content'], 0, 'sbmenu');
        Event::bind('admin.preferences.content', [$this, '_preference_tips_content'], 0, 'tips');
        Event::bind('admin.preferences.content', [$this, '_preference_home_content'], 0, 'home');
        Event::bind('admin.preferences.content', [$this, '_preference_notification_content'], 0, 'notification');
        Event::bind('admin.preferences.content', [$this, '_preference_mail_content'], 0, 'mail');
        Event::bind('admin.preferences.content', [$this, '_preference_pi_content'], 0, 'pi');

        $tabs->content->secondary_tabs = Widget::factory('tabs')->set('class', 'secondary_tabs');
        if (!Module::is_installed('uno')) {
            $tabs->content->secondary_tabs->add_tab('sbmenu', [
                'title' => T('边栏菜单'),
                'url'   => URI::url('admin/preferences.sbmenu'),
            ])
            ->add_tab('home', [
                'title' => T('自定义首页'),
                'url'   => URI::url('admin/preferences.home'),
            ]);
        }
        $tabs->content->secondary_tabs
            ->add_tab('tips', [
                'title' => T('提示设置'),
                'url'   => URI::url('admin/preferences.tips'),
            ])
            ->tab_event('admin.preferences.tab')
            ->content_event('admin.preferences.content');

        if (!Module::is_installed('labs')) {

            $pi_token = Config::get('lab.pi');
            $pi       = O('user', ['token' => $pi_token]);

            if ($pi->id == $me->id) {
                $tabs->content->secondary_tabs->add_tab('mail', [
                    'weight' => '200',
                    'title'  => T('邮件小秘书'),
                    'url'    => URI::url('admin/preferences.mail'),
                ]);
            }

            if (in_array($me->token, (array) Config::get('lab.admin'))) {
                $tabs->content->secondary_tabs->add_tab('pi', [
                    'weight' => '190',
                    'title'  => T('设置PI'),
                    'url'    => URI::url('admin/preferences.pi'),
                ]);
            }

        }

        // 判断是否显示消息提醒tab
        $notification_classification = Config::get('notification.classification');
        foreach ($notification_classification as $key => $item) {
            if ($item['#enable_callback']) {
                $notification_tab_enable = call_user_func($item['#enable_callback'], L('ME'));
                if ($notification_tab_enable) {
                    break;
                }
            }
        }

        if (Event::trigger('notification.tab.not_enable')) $notification_tab_enable = false;

        if ($notification_tab_enable) {
            $tabs->content->secondary_tabs
                ->add_tab('notification', [
                    'title' => T('消息提醒'),
                    'url'   => URI::url('admin/preferences.notification'),
                ]);
        }

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs->select($params[1]);
    }

    // 消息提醒 primary_tab
    public function _index_reminder($e, $tabs)
    {
        $me = L('ME');
        $tabs->content = V('admin/reminder');

        Event::bind('admin.reminder.content', [$this, '_commonuse_usernotice_content'], 0, 'usernotice');
        Event::bind('admin.reminder.content', 'Billing_Admin::_secondary_notification_content', 0, 'billing');
        Event::bind('admin.reminder.content', 'Node_Admin::_secondary_notification_content', 0, 'envmon');
        Event::bind('admin.reminder.content', 'Equipments_Admin::_secondary_notification_content', 0, 'message');
        Event::bind('admin.reminder.content', 'EQ_Charge_Admin::_notif_content', 0, 'charge');
        Event::bind('admin.reminder.content', 'EQ_Sample_Admin::_notif_content', 0, 'sample');
        Event::bind('admin.reminder.content', 'People_Admin::_secondary_email_content', 1, 'email');
        Event::bind('admin.reminder.content', 'Labs_Admin::_secondary_notification_content', 0, 'notifications');
        Event::bind('admin.reminder.content', ['Credit_Admin', '_commonuse_credit_notice_content'], 0, 'credit_notice' );

        $secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.reminder.tab')
            ->content_event('admin.reminder.content');

        if ($me->access('管理所有内容')) {
            $secondary_tabs->add_tab('usernotice', [
                'title' => T('@用户提醒'),
                'url'   => URI::url('admin/reminder.usernotice'),
            ]);
        }

        if ($me->access('管理财务中心')) {
            $secondary_tabs->add_tab('billing', [
                'url'   => URI::url('admin/reminder.billing'),
                'title' => I18N::T('billing', '财务消息提醒'),
            ]);
        }

        if (Module::is_installed('envmon') && $me->access('管理所有环境监控对象')) {
            $secondary_tabs->add_tab('envmon', [
                'url'   => URI::url('admin/reminder.envmon'),
                'title' => I18N::T('envmon', '环境监控提醒'),
            ]);
        }

        if ($me->access('添加/修改所有机构的仪器')) {
            $secondary_tabs->add_tab('message', [
                'url'   => URI::url('admin/reminder.message'),
                'title' => I18N::T('equipments', '仪器消息提醒'),
            ]);

            $secondary_tabs->add_tab('charge', [
                'url'   => URI::url('admin/reminder.charge'),
                'title' => I18N::T('eq_charge', '计费消息提醒'),
            ]);

            $secondary_tabs->add_tab('sample', [
                'url'   => URI::url('admin/reminder.sample'),
                'title' => I18N::T('eq_sample', '送样消息提醒'),
            ]);
        }

        if (!People::perm_in_uno() && Config::get('system.enable_notifications', true) && L('ME')->is_allowed_to('管理角色', 'user')) {
            $secondary_tabs->add_tab('email', [
                'url'   => URI::url('admin/reminder.email'),
                'title' => I18N::T('people', '成员消息提醒'),
            ]);
        }

        if (!People::perm_in_uno() && $me->is_allowed_to('管理', 'lab')) {
            $secondary_tabs->add_tab('notifications', [
                'url'   => URI::url('admin/reminder.notifications'),
                'title' => I18N::T('labs', '课题组消息提醒'),
            ]);
        }

        if (Module::is_installed('credit')) {
            $secondary_tabs->add_tab('credit_notice', [
                'title' => T('信用变更通知'),
                'url' => URI::url('admin/reminder.credit_notice'),
            ]);
        }

        $tabs->content->secondary_tabs = $secondary_tabs;
        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);
    }

    public function _commonuse_usernotice_content($e, $tabs)
    {
        $configs = [
            'notification.at_user',
        ];
        $vars = [];
        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', T('消息标题不能为空！'))
                    ->validate('body', 'not_empty', T('消息内容不能为空！'));
                $vars['form'] = $form;

                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];

                    foreach ((array) Lab::get('notification.handlers') as $k => $v) {
                        if (isset($form['send_by_' . $k])) {
                            $value = $form['send_by_' . $k];
                        } else {
                            $value = 0;
                        }
                        $tmp['send_by'][$k] = $value;

                    }

                    Lab::set($form['type'], $tmp);
                }
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, T('内容修改成功'));
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, T('恢复系统默认设置成功'));
            }
        }
        $views         = Notification::preference_views($configs, $vars, '');
        $tabs->content = $views;
    }

    public function _commonuse_billing_content($e, $tabs)
    {
        $configs = Config::get('notification.billing_conf');

        $vars = [];

        $form = Form::filter(Input::form());

        if (in_array($form['type'], $configs)) {

            if ($form['submit']) {

                $form
                    ->validate('title', 'not_empty', I18N::T('billing', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('billing', '消息内容不能为空！'));
                $vars['form'] = $form;

                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];
                }

                foreach (Lab::get('notification.handlers') as $k => $v) {
                    if (isset($form['send_by_' . $k])) {
                        $value = $form['send_by_' . $k];
                    } else {
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = $value;
                }
                Lab::set($form['type'], $tmp);

                /* 记录日志 */
                $me = L('ME');
                Log::add(strtr('[billing] %user_name[%user_id]修改了系统设置中的财务管理通知邮件', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '内容修改成功'));
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '恢复系统默认设置成功'));
            }
        }

        $tabs->content = Notification::preference_views($configs, $vars, 'billing');
    }

    public function _preference_tips_content($e, $tabs)
    {
        $form = Input::form();
        $me   = L('ME');

        if ($form['submit']) {
            $hide_all = $form['hide_all_tips'] == 'on' ? true : false;
            if (!$hide_all) {
                unset($me->hidden_tips);
            }
            $me->hide_all_tips = $hide_all;
            $me->save();
            if ($me->hide_all_tips) {
                Log::add(strtr('[application] %user_name[%user_id]成功设定隐藏页面提示信息', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');
            } else {
                Log::add(strtr('[application] %user_name[%user_id]成功设定显示页面提示信息', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');
            }
            Lab::message(Lab::MESSAGE_NORMAL, T('设置已更新.'));
        }

        $tabs->content = V('admin/tips');

    }

    public function _preference_home_content($e, $tabs)
    {
        $form = Input::form();
        $me   = L('ME');
        if ($form['submit'] && $form['home']) {
            $me->home = $form['home'];
            if ($me->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户登录默认页面设置成功！'));
            }
        }
        $tabs->content = V('admin/home');
    }

    public function _preference_mail_content($e, $tabs)
    {
        $form     = Input::form();
        $me       = L('ME');
        $pi_token = Config::get('lab.pi');
        $pi       = O('user', ['token' => $pi_token]);

        if ($pi->id != $me->id) {
            URI::redirect('error/404');
        }

        if ($form['submit']) {
            $categories = Config::get('newsletter.categories');
            foreach ($categories as $key => $category) {
                if ($form[$key]) {
                    $arr[$key] = true;
                } else {
                    $arr[$key] = false;
                }
            }
            $arr            = json_encode($arr, true);
            $me->nl_cat_vis = $arr;
            $me->save();
            Lab::message(Lab::MESSAGE_NORMAL, T('邮件小秘书设置成功！'));
        }
        $tabs->content = V('admin/mail');
    }

    public function _preference_pi_content($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $me   = L('ME');

        if ($form['submit']) {

            if (!$form['pi']) {
                $form->set_error('pi', T('请输入PI名称! '));
            }

            if ($form->no_error) {
                $user = O('user', $form['pi']);
                if ($user->id) {
                    Lab::set('lab.pi', $user->token);
                    Lab::message(Lab::MESSAGE_NORMAL, T('系统PI设置成功！'));
                }
            }
        }
        $tabs->content = V('admin/pi', ['form' => $form]);
    }

    public function _preference_sbmenu_content($e, $tabs)
    {
        $form = Input::form();
        $me   = L('ME');
        //一般用户保存，保存到一个私人的文件夹下
        if ($form['submit']) {

            if (SBMenu_Widget::validate_form($form)) {
                Lab::message(Lab::MESSAGE_ERROR, T('分类名称填写有误!'));
            } else {
                $me->sbmenu_categories = SBMenu_Widget::make_categories($form);
                $me->save();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '边栏菜单设置成功！'));
                Log::add(strtr('[application] %user_name[%user_id]成功修改边栏菜单', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');
            }
            // 进行cache
            // SBMenu_Widget::cache_clean($me);
        }
        //保存为默认，需要权限
        elseif ($form['reset']) {
            $me->sbmenu_categories = null;
            $me->save();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '边栏菜单设置成功！'));
        } elseif ($me->access('管理所有内容') && $form['save']) {
            Lab::set('sbmenu_categories', SBMenu_Widget::make_categories($form));
            $me->sbmenu_categories = Lab::get('sbmenu_categories');
            $me->save();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '边栏菜单设置成功！'));
        }

        $ct_items      = SBMenu_Widget::categorized_items($me, false);
        $tabs->content = V('admin/sbmenu', ['categorized_items' => $ct_items]);

        /*$panel_buttons[]  = [
        'url' => '',
        'tip' => I18N::T('setting', '添加管理组'),
        'text' => '',
        'extra' => 'class="button button_add"'
        ];*/

        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);

        $this->add_css('sbmenu_admin');
        $this->add_js('sortable');
    }

    public function _preference_notification_content($e, $tabs)
    {
        $form          = Input::form();
        $tabs->content = V('admin/notification/types', ['types' => Config::get('notification.classification')]);
    }

    function _index_groups($e, $tabs) {
        $root = Tag_Model::root('group');
        $tags = Q("tag_group[parent=$root]:sort(weight)");
        $this->add_js('tag_sortable');

        $uniqid="tag_".uniqid();
        $url=URI::url('tags');

        $tabs->content=V('admin/view');
        $secondary_tabs = Widget::factory('tabs');
        Event::bind('admin.group.tab', 'Admin::_index_group_tab');

        $tabs->content->secondary_tabs = $secondary_tabs
        ->tab_event('admin.group.tab')
        ->set('class', 'secondary_tabs')
        ->add_tab('groups', [
            'url'   => URI::url('admin/groups.groups'),
            'title' => I18N::T('groups', '组织机构'),
        ])
        ->select('groups');

        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);

        $button_title = $root->name;
        $tabs->content->secondary_tabs->content = V('application:admin/groups', ['tags' => $tags, 'root' => $root, 'uniqid'=>$uniqid,'button_title'=>$button_title]);
    }

    public function _groups_group_content($e, $tabs) {
        $root = Tag_Model::root('group');
        $tags = Q("tag_group[parent=$root]:sort(weight)");
        $this->add_js('tag_sortable');

        $uniqid="tag_".uniqid();
        $url=URI::url('tags');

        $tabs->content=V('admin/view');
        $secondary_tabs = Widget::factory('tabs');
        Event::bind('admin.group.tab', 'Admin::_index_group_tab');

        $tabs->content->secondary_tabs = $secondary_tabs
        ->tab_event('admin.group.tab')
        ->set('class', 'secondary_tabs')
        ->add_tab('groups', [
            'url'   => URI::url('admin/groups.groups'),
            'title' => I18N::T('groups', '组织机构'),
        ])
        ->select('groups');

        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);

        $button_title = $root->name;
        $tabs->content->secondary_tabs->content = V('application:admin/groups', ['tags' => $tags, 'root' => $root, 'uniqid'=>$uniqid,'button_title'=>$button_title]);
    }


    public function _index_support_tab($e, $tabs)
    {
        $tabs->content = V('admin/view');
        $configs       = Config::get('support');

        $secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs');

        foreach ($configs as $key => $value) {
            Event::bind('admin.support.content', [$this, '_index_support_content'], 0, $key);
            $secondary_tabs->add_tab($key, [
                'url'   => URI::url('admin/support.' . $key),
                'title' => T($value['name']),
            ]);
        }

        $secondary_tabs->tab_event('admin.support.tab')
            ->content_event('admin.support.content');

        $params                        = Config::get('system.controller_params');
        $tabs->content->secondary_tabs = $secondary_tabs;
        $tabs->content->secondary_tabs->select($params[1]);
    }

    public function _index_support_content($e, $tabs)
    {
        $select  = $tabs->selected;
        $configs = Config::get('support.' . $select)['items'];
        $form    = Input::form();
        try {
            if ($form['submit']) {
                if ($configs) {
                    foreach ($configs as $prekey => $subconfigs) {
                        foreach ($subconfigs['subitems'] as $key => $value) {
                            Lab::set($prekey . '.' . $key, ($form[$key] == 'on' ? true : false));
                        }
                    }
                }

                Event::trigger('support.equipment.switch', $form);
                if ($select == 'lab_signup' && Module::is_installed('uno')) {
                    $lab_signup_outside_fields = COnfig::get('support.lab_signup')['items']['outside']['subitems'];
                    foreach ($lab_signup_outside_fields as $key => $lsof) {
                        $custom_field = [
                            'key' => $key,
                            'name' => $lsof['title'],
                            'type' => 'text',
                            'object' => 'group',
                            'unique' => false,
                            'indexable' => false,
                            'nullable' => true,
                            'listable' => true,
                            'ui_type' => 'input',
                            'fit_type' => [5] // 分组类型 - 课题组
                        ];
                        $res = Gateway::postCustomField($custom_field);
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipment', '设置更新成功'));
            }

            $tabs->content = V('support:tab_content', ['configs' => $configs]);
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipment', '设置更新失败'));
        }
    }

    function _index_locations($e, $tabs) {
        $root = Tag_Model::root('location');
        $tags = Q("tag_location[parent=$root]:sort(weight)");
        $this->add_js('tag_sortable');

        $uniqid="tag_".uniqid();
        $url=URI::url('tags');

        $tabs->content=V('admin/view');

        $secondary_tabs = Widget::factory('tabs');
        Event::bind('admin.location.tab', 'Admin::_index_location_tab');

        $tabs->content->secondary_tabs = $secondary_tabs
            ->tab_event('admin.location.tab')
            ->set('class', 'secondary_tabs')
            ->add_tab('locations', [
                'url'   => URI::url('admin/locations.locations'),
                'title' => I18N::T('locations', '地理位置'),
            ])
            ->select('locations');

        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);

        $button_title = $root->name;
        $tabs->content->secondary_tabs->content = V('application:admin/locations', ['tags' => $tags, 'root' => $root, 'uniqid'=>$uniqid,'button_title'=>$button_title]);
    }

    function _index_columns($e, $tabs)
    {
        $this->add_js('sortable');
        $this->add_css('columns_list');

        $tabs->content = V('admin/view');
        $tabs->content->set('withoutToolbox', true);
        $secondary_tabs = Widget::factory('tabs')->set('class', 'secondary_tabs');

        $secondary_tabs->tab_event('admin.columns.tab')
            ->content_event('admin.columns.content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = $secondary_tabs;
        $tabs->content->secondary_tabs->select($params[1]);
    }

}

class Admin_AJAX_Controller extends AJAX_Controller
{

    public function index_add_sbmenu_category_click()
    {
        $me = L('ME');

        $index = count(SBMenu_Widget::categorized_items($me, false));
        $view  = V('admin/sbmenu/add', ['index' => $index]);

        JS::dialog($view, ['title' => I18N::T('people', '添加菜单项')]);
    }

    public function index_add_sbmenu_category_submit()
    {
        $me = L('ME');

        $form  = Form::filter(Input::form());
        $index = count(SBMenu_Widget::categorized_items($me, false));

        if ($form['submit']) {
            if (!$form['categories'][$index]['name']) {
                $form->set_error("categories[$index][name]", I18N::T('people', '请填写菜单名称!'));
            }
            if ($form->no_error) {
                try {
                    $arr[$form['categories'][$index]['name']] = [];
                    
                    if($me->sbmenu_categories){
                        $me->sbmenu_categories += $arr;
                    }else{
                        $me->sbmenu_categories = $arr;
                    }
                   
                    $me->save();
                    Log::add(strtr('[application] %user_name[%user_id]成功修改边栏菜单', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, T('添加菜单项成功!'));
                    JS::redirect('admin/preferences');
                } catch (Error_Exception $e) {
                }
            }
        }

        JS::dialog(V('admin/sbmenu/add', ['index' => $index, 'form' => $form]), ['title' => I18N::T('labs', '添加菜单项')]);
    }

    public function index_sbmenu_show_hidden_click()
    {
        $form                           = Input::form();
        $_SESSION['sbmenu_show_hidden'] = $form['show_hidden'];
    }

    public function index_get_classification_item_click()
    {
        $form                                                       = Input::form();
        Output::$AJAX['#' . $form['container_id'] . ' > div:eq(0)'] = [
            'data' => (string) V('admin/notification/relate_view', ['key' => $form['key']]),
            'mode' => 'replace',
        ];
    }

    public function index_modify_notification_classification_submit()
    {
        $me   = L('ME');
        $form = [];

        //form是通过jquery serialize而来，所以需要进行如下处理
        foreach (explode('&', urldecode(Input::form('form'))) as $form_item) {
            list($key, $value) = explode('=', $form_item);
            $form[$key]        = $value;
        }

        //获取设置的用户分类
        $key            = $form['key'];
        $classification = Config::get('notification.classification');

        //获取分类下属类目
        $sub_classification = [];
        foreach ($classification[$key] as $key => $value) {
            $sub_classification[$key] = $value;
        }

        //获取所有的notification的send方式
        $sends = array_keys((array) Config::get('notification.handlers'));
        foreach ($sub_classification as $title => $items) {
            foreach ((array) $sends as $send_type) {
                foreach ($items as $item) {
                    //notification.report_problem.messages form的key值
                    $str = Notification::get_key($item, $send_type, $me);

                    //notification.report_problem.messages.1 Lab::set Lab::get使用值
                    Lab::set($str, $form[$str] == 'on' ? true : false);
                }
            }
        }

        Output::$AJAX['#' . $form['message_uniqid']] = [
            'data' => (string) V('admin/notification/message'),
            'mode' => 'append',
        ];
    }
}
