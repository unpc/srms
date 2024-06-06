<?php

class EQ_Comment_User
{

    public static function setup_extra($e, $controller, $method, $params)
    {
        list($name, $type) = $params;
        if (!$type) {
            return;
        }

        Event::bind('equipments.primary.tab', "EQ_Comment_User::extra_{$type}_primary_tab");
        Event::bind('equipments.primary.content', "EQ_Comment_User::extra_{$type}_primary_tab_content", 0, 'comment');
    }

    public static function extra_all_primary_tab($e, $tabs)
    {
        $me = L('ME');
        if ($me->is_allowed_to('列表全部仪器使用评价', 'equipment')) {
            $tabs->add_tab('comment', [
                'url' => URI::url('!equipments/extra/comment.all'),
                'title' => I18N::T('eq_comment', '所有仪器的评价记录'),
            ]);
        }
    }

    public static function extra_group_primary_tab($e, $tabs)
    {
        $me = L('ME');
        if ($me->is_allowed_to('列表下属机构仪器使用评价', 'equipment')) {
            $tabs->add_tab('comment', [
                'url' => URI::url('!equipments/extra/comment.group'),
                'title' => I18N::T('eq_comment', "{$me->group->name}所有仪器的使用评价"),
            ]);
        }
    }

    public static function extra_all_primary_tab_content($e, $tabs)
    {
        $params = Config::get('system.controller_params');
        $tab = $params[2] ?: 'incharge';
        $tabs->base_url && count($params) < 3 && $tab = $params[1] ?: 'incharge';

        Event::bind('equipment.primary.comment.content', "EQ_Comment_User::extra_comment_user_primary_tab_content", 0, 'incharge');
        Event::bind('equipment.primary.comment.content', "EQ_Comment_User::extra_comment_incharge_primary_tab_content", 0, 'user');

        $secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->content_event('equipment.primary.comment.content')
            ->add_tab('incharge', [ // 评价机主
                'url' => $tabs->base_url ? "$tabs->base_url.incharge" : URI::url('!equipments/extra/comment.all.incharge'),
                'title' => I18N::T('eq_comment', '用户评价'),
                'weight' => 0,
            ])
            ->add_tab('user', [ // 评价用户
                'url' => $tabs->base_url ? "$tabs->base_url.user" : URI::url('!equipments/extra/comment.all.user'),
                'title' => I18N::T('eq_comment', '机主评价'),
                'weight' => 0,
            ])
            ->select($tab);

        $tabs->content = V('eq_comment:shortcut/all_content', [
            'secondary_tabs' => $secondary_tabs,
        ]);
    }

    public static function extra_group_primary_tab_content($e, $tabs)
    {
        $params = Config::get('system.controller_params');
        $tab = $params[2] ?: 'incharge';

        Event::bind('equipment.primary.comment.content', "EQ_Comment_User::extra_comment_user_primary_tab_content", 0, 'incharge');
        Event::bind('equipment.primary.comment.content', "EQ_Comment_User::extra_comment_incharge_primary_tab_content", 0, 'user');

        $secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->content_event('equipment.primary.comment.content')
            ->add_tab('incharge', [ // 评价机主
                'url' => URI::url('!equipments/extra/comment.group.incharge'),
                'title' => I18N::T('eq_comment', '用户评价'),
                'weight' => 0,
            ])
            ->add_tab('user', [ // 评价用户
                'url' => URI::url('!equipments/extra/comment.group.user'),
                'title' => I18N::T('eq_comment', '机主评价'),
                'weight' => 0,
            ])
            ->select($tab);

        $tabs->content = V('eq_comment:shortcut/all_content', [
            'secondary_tabs' => $secondary_tabs,
        ]);
    }

    public static function extra_comment_incharge_primary_tab_content($e, $tabs)
    {
        $me = L('ME');
        $params = Config::get('system.controller_params');
        $type = $params[1] ?: 'all';

        $selector = " eq_comment_user";

        $form = Lab::form();

        if ($type == 'group') {
            $root = $me->group;
            $group = $me->group;
            $pre_selectors[] = "{$group} equipment";
        } else {
            $root = Tag_Model::root('group');
        }

        $group_id = Q::quote($form['group_id']);
        $group = O('tag_group', $group_id);
        $groot = Tag_Model::root('group');
        if ($form['group_id'] && ($group->root->id == $groot->id)) {
            $pre_selectors[] = "{$group} equipment";
        }

        if ($form['id']) {
            $selector .= "[id={$form['id']}]";
        }

        if ($form['equipment_name']) {
            $pre_selectors[] = "equipment[name*={$form['equipment_name']}]";
        }

        if ($form['incharge_name']) {
            $incharge = Q::quote($form['incharge_name']);
            $pre_selectors[] = "user<incharge[name*=$incharge|name_abbr*=$incharge] equipment";
        }

        if (!$form['dtend_dtstart'] && !$form['dtend_dtend']) {
            $dtend_date = getdate(time());
            // $form['dtend_dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            // $form['dtend_dtstart'] = Date::prev_time($form['dtend_dtend'], 1, 'm') + 1;
        }

        if ($form['dtend_dtstart']) {
            $dtstart = Q::quote($form['dtend_dtstart']);
            $selector .= "[test_dtend>=$dtstart]";
        }

        if ($form['dtend_dtend']) {
            $dtend = Q::quote($form['dtend_dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[test_dtend>0][test_dtend<=$dtend]";
        }

        if (isset($form['user_attitude']) && $form['user_attitude'] != -1) {
            $user_attitude = Q::quote($form['user_attitude'] + 1);
            $selector .= "[user_attitude={$user_attitude}]";
        }

        if ($form['lab_id']) {
            $lab = O('lab', $form['lab_id']);
            $pre_selectors[] = "{$lab} user";
        }

        $selector .= ":sort(id D)";

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $form_token = Session::temp_token('all_user_comment_', 300);
        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $comments = Q($selector);

        $pagination = Lab::pagination($comments, (int) $form['st'], 15);

        $tabs->content = V('eq_comment:shortcut/all_user_comment', [
            'form_token' => $form_token,
            'root' => $root,
            'group' => $group,
            'comments' => $comments,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);
    }

    public static function extra_comment_user_primary_tab_content($e, $tabs)
    {
        $me = L('ME');
        $params = Config::get('system.controller_params');
        $type = $params[1] ?: 'all';

        $form = Lab::form();

        $selector = " eq_comment_incharge";

        if ($type == 'group') {
            $root = $me->group;
            $group = $me->group;
            $pre_selectors[] = "{$group} equipment";
        } else {
            $root = Tag_Model::root('group');
        }

        $group_id = Q::quote($form['group_id']);
        $group = O('tag_group', $group_id);
        $groot = Tag_Model::root('group');

        if ($form['group_id'] && ($group->root->id == $groot->id)) {
            $pre_selectors[] = "{$group} equipment";
        }

        if ($form['uid']) {
            $selector .= "[id={$form['uid']}]";
        }

        if ($form['user_equipment_name']) {
            $pre_selectors[] = "equipment[name*={$form['user_equipment_name']}]";
        }

        if ($form['user_name']) {
            $pre_selectors[] = "user[name*={$form['user_name']}]";
        }

        if ($form['incharge_name']) {
            $incharge = Q::quote($form['incharge_name']);
            $pre_selectors[] = "user<incharge[name*=$incharge|name_abbr*=$incharge] equipment";
        }

        /* if (!$form['user_dtend_dtstart_check'] && !$form['user_dtend_dtend_check']) {
            $dtend_date = getdate(time());
            $form['user_dtend_dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['user_dtend_dtstart'] = Date::prev_time($form['user_dtend_dtend'], 1, 'm') + 1;
        } */

        if ($form['user_dtend_dtstart']) {
            $dtstart = Q::quote($form['user_dtend_dtstart']);
            $selector .= "[obj_dtend>=$dtstart]";
        }

        if ($form['user_dtend_dtend']) {
            $dtend = Q::quote($form['user_dtend_dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[obj_dtend>0][obj_dtend<=$dtend]";
        }

        if (isset($form['service_attitude']) && $form['service_attitude'] != -1) {
            $service_attitude = Q::quote($form['service_attitude'] + 1);
            $selector .= "[service_attitude={$service_attitude}]";
        }

        if (isset($form['service_quality']) && $form['service_quality'] != -1) {
            $service_quality = Q::quote($form['service_quality'] + 1);
            $selector .= "[service_quality={$service_quality}]";
        }

        if (isset($form['technical_ability']) && $form['technical_ability'] != -1) {
            $technical_ability = Q::quote($form['technical_ability'] + 1);
            $selector .= "[technical_ability={$technical_ability}]";
        }

        if (isset($form['emergency_capability']) && $form['emergency_capability'] != -1) {
            $emergency_capability = Q::quote($form['emergency_capability'] + 1);
            $selector .= "[emergency_capability={$emergency_capability}]";
        }

        if ($form['lab_id']) {
            $lab = O('lab', $form['lab_id']);
            $pre_selectors[] = "{$lab} user";
        }

        $selector .= ":sort(id D)";

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $form_token = Session::temp_token('all_incharge_comment_', 300);
        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;

        $comments = Q($selector);
        $pagination = Lab::pagination($comments, (int) $form['st'], 15);

        $tabs->content = V('eq_comment:shortcut/all_incharge_comment', [
            'form_token' => $form_token,
            'root' => $root,
            'group' => $group,
            'comments' => $comments,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);
    }

    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'EQ_Comment_User::user_profile_tab');
        Event::bind('profile.view.content', 'EQ_Comment_User::user_profile_content_incharge', 0, 'incharge_comment');
        Event::bind('profile.view.content', 'EQ_Comment_User::user_profile_content_my', 0, 'my_comment');
    }

    public static function user_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me = L('ME');

        if ($user->id == $me->id && $me->is_allowed_to('列表负责仪器使用评价', 'user')) {
            $tabs->add_tab('incharge_comment', [
                'url' => $tabs->user->url('incharge_comment'),
                'title' => I18N::T('eq_comment', '我的评价'), // 用户
            ]);
        }

        if ($user->id == $me->id) {
            $tabs->add_tab('my_comment', [
                'url' => $tabs->user->url('my_comment'),
                'title' => I18N::T('eq_comment', '使用评价'), // 用户
            ]);
        }
    }

    public static function user_profile_content_incharge($e, $tabs)
    {
        $me = L('ME');

        $pre_selectors = ["{$me} equipment.incharge"];
        $selector = " eq_comment_user[commentator={$me}]";
        $form = Lab::form();

        if ($form['id']) {
            $selector .= "[id={$form['id']}]";
        }

        if ($form['equipment_name']) {
            $pre_selectors[] = "equipment[name*={$form['equipment_name']}]";
        }

        /* if (!$form['dtend_dtstart_check'] && !$form['dtend_dtend_check']) {
            $dtend_date = getdate(time());
            $form['dtend_dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['dtend_dtstart'] = Date::prev_time($form['dtend_dtend'], 1, 'm') + 1;
        } */

        if ($form['dtend_dtstart']) {
            $dtstart = Q::quote($form['dtend_dtstart']);
            $selector .= "[test_dtend>=$dtstart]";
        }

        if ($form['dtend_dtend']) {
            $dtend = Q::quote($form['dtend_dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[test_dtend>0][test_dtend<=$dtend]";
        }

        if (isset($form['user_attitude']) && $form['user_attitude'] != -1) {
            $user_attitude = Q::quote($form['user_attitude'] + 1);
            $selector .= "[user_attitude={$user_attitude}]";
        }

        if ($form['lab_id']) {
            $lab = O('lab', $form['lab_id']);
            $pre_selectors[] = "{$lab} user";
        }

        $selector .= ":sort(id D)";

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $comments = Q($selector);
        $pagination = Lab::pagination($comments, (int) $form['st'], 15);

        $columns = self::get_incharge_comment_field($form);
        $tabs->columns = new ArrayObject($columns);
        $tabs->search_box = V('application:search_box',
            [
                'panel_buttons' => $panel_buttons,
                'top_input_arr' => ['serial_number', 'equipment_name'],
                'columns' => $columns,
            ]
        );

        $tabs->content = V('eq_comment:profile/incharge_comment', [
            'tab' => 'user',
            'form_token' => $form_token,
            'comments' => $comments,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);
    }

    public static function user_profile_content_my($e, $tabs)
    {
        $me = L('ME');

        $pre_selectors = [];
        $selector = " eq_comment_incharge[user={$me}]";
        $form = Lab::form();

        if ($form['uid']) {
            $selector .= "[id={$form['uid']}]";
        }

        if ($form['user_equipment_name']) {
            $pre_selectors[] = "equipment[name*={$form['user_equipment_name']}]";
        }

        /* if (!$form['user_dtend_dtstart_check'] && !$form['user_dtend_dtend_check']) {
            $dtend_date = getdate(time());
            $form['user_dtend_dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['user_dtend_dtstart'] = Date::prev_time($form['user_dtend_dtend'], 1, 'm') + 1;
        } */

        if ($form['user_dtend_dtstart']) {
            $dtstart = Q::quote($form['user_dtend_dtstart']);
            $selector .= "[obj_dtend>=$dtstart]";
        }

        if ($form['user_dtend_dtend']) {
            $dtend = Q::quote($form['user_dtend_dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[obj_dtend>0][obj_dtend<=$dtend]";
        }

        if (isset($form['service_attitude']) && $form['service_attitude'] != -1) {
            $service_attitude = Q::quote($form['service_attitude'] + 1);
            $selector .= "[service_attitude={$service_attitude}]";
        }

        $selector .= ":sort(id D)";

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $comments = Q($selector);
        $pagination = Lab::pagination($comments, (int) $form['st'], 15);

        $columns = self::get_my_comment_field($form);
        $tabs->columns = new ArrayObject($columns);
        $tabs->search_box = V('application:search_box',
            [
                'panel_buttons' => $panel_buttons,
                'top_input_arr' => ['user_serial_number', 'user_equipment_name'],
                'columns' => $columns,
            ]
        );

        $tabs->content = V('eq_comment:profile/user_comment', [
            'form_token' => $form_token,
            'comments' => $comments,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);
    }

    public static function get_my_comment_field($form)
    {
        if ($form['user_dtend_dtstart'] || $form['user_dtend_dtend']) {
            $form['user_dtend_date'] = true;
        }

        $columns = [
            'serial_number' => [
                'title' => I18N::T('eq_comment', '编号'),
                'weight' => 11,
                'nowrap' => true,
            ],
            'user_serial_number' => [
                'title' => I18N::T('eq_comment', '编号'),
                'align' => 'left',
                'invisible' => true,
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/user/user_serial_number', ['uid' => $form['uid']]),
                    'value' => $form['uid'] ? Number::fill(H($form['uid']), 6) : null,
                    'field' => 'uid',
                ],
                'weight' => 10,
                'nowrap' => true,
            ],
            'user_equipment_name' => [
                'title' => I18N::T('eq_comment', '仪器'),
                'align' => 'left',
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/user/user_equipment_name', ['user_equipment_name' => $form['user_equipment_name']]),
                    'value' => $form['user_equipment_name'] ? H($form['user_equipment_name']) : null,
                ],
                'weight' => 20,
                'nowrap' => true,
            ],
            'service_attitude' => [
                'title' => I18N::T('eq_comment', '服务态度'),
                'align' => 'left',
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/rate', ['name' => 'service_attitude', 'val' => $form['service_attitude']]),
                    'value' => (isset($form['service_attitude']) && $form['service_attitude'] != -1) ? true : false,
                ],
                'weight' => 30,
                'nowrap' => true,
            ],
            'user_dtend' => [
                'title' => I18N::T('eq_comment', '时间范围'),
                'align' => 'left',
                'invisible' => true,
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/user/date', [
                        'name_prefix' => 'user_dtend_',
                        'dtstart' => $form['user_dtend_dtstart'],
                        'dtend' => $form['user_dtend_dtend'],
                    ]),
                    'value' => $form['user_dtend_date'] ? H($form['user_dtend_date']) : null,
                    'field' => 'user_dtend_dtstart,user_dtend_dtend',
                ],
                'weight' => 50,
                'nowrap' => true,
            ],
            'service_quality' => ['title' => I18N::T('eq_comment', '服务质量'), 'align' => 'left', 'weight' => 90, 'nowrap' => true],
            'technical_ability' => ['title' => I18N::T('eq_comment', '技术能力'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'emergency_capability' => ['title' => I18N::T('eq_comment', '应急能力'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'detection_performance' => ['title' => I18N::T('eq_comment', '检测性能'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'accuracy' => ['title' => I18N::T('eq_comment', '准确性'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'compliance' => ['title' => I18N::T('eq_comment', '吻合度'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'timeliness' => ['title' => I18N::T('eq_comment', '及时性'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'sample_processing' => ['title' => I18N::T('eq_comment', '样品处理'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'comment_suggestion' => ['title' => I18N::T('eq_comment', '评价建议'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
        ];

        return $columns;
    }

    public static function get_incharge_comment_field($form)
    {
        if ($form['dtend_dtstart'] || $form['dtend_dtstart']) {
            $form['dtend_date'] = true;
        }

        $columns = [
            // '@' => null,
            'serial_number' => [
                'title' => I18N::T('eq_comment', '编号'),
                'align' => 'left',
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/serial_number', ['id' => $form['id']]),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'weight' => 10,
                'nowrap' => true,
            ],
            'equipment_name' => [
                'title' => I18N::T('eq_comment', '仪器'),
                'align' => 'left',
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/equipment_name', ['equipment_name' => $form['equipment_name']]),
                    'value' => $form['equipment_name'] ? H($form['equipment_name']) : null,
                ],
                'weight' => 20,
                'nowrap' => true,
            ],
            'user_attitude' => [
                'title' => I18N::T('eq_comment', '样品吻合度'),
                'align' => 'left',
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/rate', ['name' => 'user_attitude', 'val' => $form['user_attitude']]),
                    'value' => (isset($form['user_attitude']) && $form['user_attitude'] != -1) ? true : false,
                ],
                'weight' => 30,
                'nowrap' => true,
            ],
            'dtend' => [
                'title' => I18N::T('eq_comment', '时间范围'),
                'align' => 'left',
                'invisible' => true,
                'filter' => [
                    'form' => V('eq_comment:comments_table/filters/date', [
                        'name_prefix' => 'dtend_',
                        'dtstart' => $form['dtend_dtstart'],
                        'dtend' => $form['dtend_dtend'],
                    ]),
                    'value' => $form['dtend_date'] ? H($form['dtend_date']) : null,
                    'field' => 'dtend_dtstart,dtend_dtend',
                ],
                'weight' => 50,
                'nowrap' => true,
            ],
            'lab_id' => [
                'title' => I18N::T('eq_comment', '课题组'),
                'invisible' => true,
                'suppressible' => true,
                'filter' => [
                    'form' => Widget::factory('labs:lab_selector', [
                        'name' => 'lab_id',
                        'selected_lab' => $form['lab_id'],
                        'size' => 25,
                        'all_labs' => true,
                    ]),
                    'value' => $form['lab_id'] ? H(O('lab', $form['lab_id'])->name) : null,
                ],
                'weight' => 60,
            ],
            'user_proficiency' => ['title' => I18N::T('eq_comment', '熟练度'), 'align' => 'left', 'weight' => 90, 'nowrap' => true],
            'user_cleanliness' => ['title' => I18N::T('eq_comment', '清洁度 / 标准操作'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_importance' => ['title' => I18N::T('eq_comment', '重要性'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_understanding' => ['title' => I18N::T('eq_comment', '设备了解度'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_purpose' => ['title' => I18N::T('eq_comment', '测试目的'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_method' => ['title' => I18N::T('eq_comment', '测试方法'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_result' => ['title' => I18N::T('eq_comment', '测试结果'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_fit' => ['title' => I18N::T('eq_comment', '预期吻合度'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
            'test_remark' => ['title' => I18N::T('eq_comment', '备注'), 'align' => 'left', 'weight' => 100, 'nowrap' => true],
        ];
        
        if ($tab == 'equipment') {
            unset($columns['equipment_name']['filter']);
        }

        return $columns;
    }

    public static function setup_equipment()
    {
        Event::bind('equipment.index.tab', 'EQ_Comment_User::equipment_index_tab');
        Event::bind('equipment.index.tab.content', 'EQ_Comment_User::equipment_index_tab_content', 0, 'comment');
    }

    public static function equipment_index_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me = L('ME');

        if ($me->is_allowed_to('列表负责仪器使用评价', $equipment)) {
            $tabs->add_tab('comment', [
                'url' => $tabs->equipment->url('comment'),
                'title' => I18N::T('eq_comment', '评价记录'), // 用户
                'weight' => 50,
            ]);
        }
    }

    public static function equipment_index_tab_content($e, $tabs)
    {
        $me = L('ME');
        $equipment = $tabs->equipment;

        $selector = " eq_comment_user[commentator={$me}][equipment=$equipment]";
        $form = Lab::form();

        if ($form['id']) {
            $selector .= "[id={$form['id']}]";
        }

        if ($form['equipment_name']) {
            $pre_selectors[] = "equipment[name*={$form['equipment_name']}]";
        }

        if (!$form['dtend_dtstart'] && !$form['dtend_dtend']) {
            $dtend_date = getdate(time());
            // $form['dtend_dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            // $form['dtend_dtstart'] = Date::prev_time($form['dtend_dtend'], 1, 'm') + 1;
        }

        if ($form['dtend_dtstart']) {
            $dtstart = Q::quote($form['dtend_dtstart']);
            $selector .= "[test_dtend>=$dtstart]";
        }

        if ($form['dtend_dtend']) {
            $dtend = Q::quote($form['dtend_dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[test_dtend>0][test_dtend<=$dtend]";
        }

        if (isset($form['user_attitude']) && $form['user_attitude'] != -1) {
            $user_attitude = Q::quote($form['user_attitude'] + 1);
            $selector .= "[user_attitude={$user_attitude}]";
        }

        if ($form['lab_id']) {
            $lab = O('lab', $form['lab_id']);
            $pre_selectors[] = "{$lab} user";
        }

        $selector .= ":sort(id D)";

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $comments = Q($selector);
        $pagination = Lab::pagination($comments, (int) $form['st'], 15);

        $columns = self::get_incharge_comment_field($form);
        $tabs->columns = new ArrayObject($columns);
        $tabs->search_box = V('application:search_box',
            [
                'panel_buttons' => $panel_buttons,
                'top_input_arr' => ['serial_number', 'equipment_name'],
                'columns' => $columns,
            ]
        );

        $tabs->content = V('eq_comment:profile/incharge_comment', [
            'tab' => 'equipment',
            'form_token' => $form_token,
            'comments' => $comments,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);
    }

    public static function comment_user_ACL($e, $user, $action, $object, $options)
    {
        if (is_object($object)) {
            $equipment = $object->equipment;
            $comment = O('eq_comment_user', ['source' => $object]);
        }

        switch ($action) {
            case '列表负责仪器使用评价':
                $e->return_value = Q("equipment user.incharge[id=$user->id]")->total_count() > 0;
                break;
            case '评价':
                if (is_object($object) && Equipments::user_is_eq_incharge($user, $equipment)) {
                    if ($object->name() == 'eq_sample') {
                        if ($object->dtsubmit > 0 && $object->status == EQ_Sample_Model::STATUS_TESTED) {
                            $e->return_value = true;
                        }
                    } else {
                        if ($object->dtend > 0) {
                            $e->return_value = true;
                        }
                    }
                }

                if ($comment->id) {
                    $e->return_value = false;
                }

                if ($feedback != '' && !is_null($feedback) && !$comment->id) {
                    $e->return_value = true;
                }

                break;
            default:
                $e->return_value = false;
                break;
        }
    }

    public static function comment_equipment_ACL($e, $user, $action, $object, $options)
    {
        $me = L('ME');
        switch ($action) {
            case '列表全部仪器使用评价';
                if ($me->id && $me->is_active() && $me->access('管理所有仪器的使用评价')) {
                    $e->return_value = true;
                }
                break;
            case '列表下属机构仪器使用评价';
                if ($me->id && $me->is_active() && $me->group->id && $me->access('管理下属机构仪器的使用评价')) {
                    $e->return_value = true;
                }
                break;
            case '列表负责仪器使用评价':
                $e->return_value = Equipments::user_is_eq_incharge($user, $object) && $user->access('添加负责的仪器');
                break;
            default:
                $e->return_value = false;
                break;
        }
    }

    public static function eq_object_links_edit($e, $object, $links, $mode, $ajax_id = null)
    {
        $me = L('ME');

        if ($me->is_allowed_to('评价', $object)) {
            $links['comment_user'] = [
                'url' => '#',
                'text' => I18N::T('eq_comment', '评价'),
                'extra' => 'class="blue" q-event="click" q-object="comment_user" q-static="'
                . H(['object_id' => $object->id, 'object_name' => $object->name()]) . '" q-src="' . URI::url('!eq_comment/user') . '"',
            ];
        }

        if ($object->name == 'eq_record' && !$me->is_allowed_to('反馈', $object) && !$me->is_allowed_to('评价', $object) && $me->is_allowed_to('评价机主', $object)) {
            $links['comment_incharge'] = [
                'url' => '#',
                'text' => I18N::T('eq_comment', '评价'),
                'extra' => 'class="blue" q-event="click" q-object="comment_incharge" q-static="' . H(['id' => $object->id, 'object_type' => 'record']) . '" q-src="' . URI::url('!eq_comment/incharge') . '"',
            ];
        }
    }

    public static function eq_object_model_saved($e, $object, $new_data, $old_data)
    {
        $comment = O('eq_comment_user', ['source' => $object]);
        if (!$comment->id) {
            return true;
        }

        if ($object->name() == 'eq_sample') {
            $comment->user = $object->sender;
            $comment->test_dtend = $object->dtsubmit;
        } else {
            $comment->user = $object->user;
            $comment->test_dtend = $object->dtend;
        }
        $comment->test_dtstart = $object->dtstart;

        $comment->save();
    }
}
