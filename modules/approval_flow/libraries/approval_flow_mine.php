<?php


class Approval_Flow_Mine
{
    public static function setup_approval_tab()
    {
        Event::bind('profile.view.tab', 'Approval_Flow_Mine::profile_approval_tab', '1');
    }

    public static function profile_approval_tab($e, $tabs)
    {
        $me = L('ME');

        $modules = Config::get('approval.modules');
        if (in_array('eq_reserv', $modules) && $tabs->user->id == $me->id) {
            Event::bind('profile.view.content', 'Approval_Flow_Mine::profile_approval_content', 0, 'reserv_approval');
            $tabs->add_tab('reserv_approval', [
                'url' => $me->url('reserv_approval'),
                'title' => I18N::T('billing', '我的预约审批'),
                'weight' => 110,
            ]);
        }
        if (in_array('eq_sample', $modules) && $tabs->user->id == $me->id) {
            Event::bind('profile.view.content', 'Approval_Flow_Mine::profile_approval_content', 0, 'sample_approval');
            $tabs->add_tab('sample_approval', [
                'url' => $me->url('sample_approval'),
                'title' => I18N::T('billing', '我的送样审批'),
                'weight' => 120,
            ]);
        }
    }

    // tab名称和对应source_orm的哈希, 因为有重名tab
    public static $_hash = [
        'reserv_approval' => 'eq_reserv',
        'sample_approval' => 'eq_sample',
    ];

    public static function profile_approval_content($e, $tabs)
    {
        $me = L('ME');

        $flow = Config::get('flow.' . self::$_hash[$tabs->selected]);

        // type: reserv_approval , sample_approval
        $type = $tabs->selected;
        // status: approve_pi, done, reject ...
        $params = Config::get('system.controller_params');
        $status = $params[2] ?: array_keys($flow)[0];
        Event::bind('profile.approval.view.tabs', 'Approval_Flow_Mine::_profile_approval_tabs', 0, $status);
        Event::bind('profile.approval.view.content', 'Approval_Flow_Mine::_profile_approval_content', 0, $status);

        $tabs->content = V('approval_flow:profile/content');
        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('type', $type)
            ->tab_event('profile.approval.view.tabs')
            ->content_event('profile.approval.view.content')
            ->select($status);
    }

    public static function my_approval_content($e, $tabs)
    {

        $me = L('ME');
        
        $flow = Config::get('flow.' . self::$_hash[$tabs->type]);

        // type: reserv_approval , sample_approval
        // tab: approve_pi, done, reject ...
        $params = Config::get('system.controller_params');
        $tab = $params[1] ?: array_keys($flow)[0];
        Event::bind('my.approval.view.tabs', 'Approval_Flow_Mine::_my_approval_tabs', 0, $tab);
        Event::bind('my.approval.view.content', 'Approval_Flow_Mine::_profile_approval_content', 0, $tab);

        $tabs
            ->tab_event('my.approval.view.tabs')
            ->content_event('my.approval.view.content')
            ->select($tab);
    }

    public static function _profile_approval_tabs($e, $tabs)
    {
        $me = L('ME');
        $flow = Config::get('flow.' . self::$_hash[$tabs->type]);
        foreach ($flow as $step => $operation) {
            $criteria = [
                'user' => $me,
                'flag' => $step,
                'source_name' => self::$_hash[$tabs->type],
            ];
            
            $selector = Approval_Help::make_tab_selector($criteria);
            $tabs->add_tab($step, [
                'url' => $me->url("{$tabs->type}.{$step}"),
                'title' => I18N::T('approval', $operation['title'] . ' (%count)', [
                    '%count' => Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ])
                ->set('class', 'secondary_tabs');
        }
    }

    public static function _my_approval_tabs($e, $tabs)
    {

        $me = L('ME');
        $flow = Config::get('flow.' . self::$_hash[$tabs->type]);
        foreach ($flow as $step => $operation) {
            $criteria = [
                'user' => $me,
                'flag' => $step,
                'source_name' => self::$_hash[$tabs->type],
            ];
            
            $selector = Approval_Help::make_tab_selector($criteria);
            $tabs->add_tab($step, [
                'url' => URI::url("!approval_flow/record/me." . $tabs->type . '.' . $step),
                'title' => I18N::T('approval', $operation['title'] . ' (%count)', [
                    '%count' => Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ]);
            
        }
    }



    public static function _profile_approval_content($e, $tabs)
    {
        $me = L('ME');
        // $tabs->type: reserv_approval , sample_approval
        $source_name = self::$_hash[$tabs->type];
        $flows = Config::get('flow.' . $source_name);
        $flag = $tabs->selected ?: array_keys($flows)[0];

        // $form = Lab::form(Approval_Help::date_filter_handler());
        $form = Lab::form();
        $criteria = [
            'user' => $me,
            'flag' => $flag,
            'source_name' => self::$_hash[$tabs->type],
            'sort' => 'ctime',
            'sort_asc' => 'D',
        ];
        $form = array_merge($form, $criteria);
        $selector = Approval_Help::make_tab_selector($form);
        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int) $form['st'], 20);

        $columns = self::get_reserv_approval_fields($form, $flag);
        $tabs->columns = new ArrayObject($columns);
        $tabs->search_box = V('application:search_box',
            [
                'panel_buttons' => $panel_buttons,
                'top_input_arr' => ['equipment'],
                'columns' => $columns,
            ]
        );

        $tabs->content = V("approval_flow:approval/list_{$source_name}_mine", [
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ?: 'date',
            'sort_asc' => $form['sort_asc'],
        ]);
    }

    public static function get_reserv_approval_fields($form, $flag)
    {
        if ($form['ctime_dtstart'] || $form['ctime_dtend']) {
            $form['ctime'] = true;
        }

        switch ($flag) {
            case 'approve':
                $stime_title = '';
                break;
            case 'done':
                $stime_title = '通过时间';
                break;
            case 'rejected':
                $stime_title = '驳回时间';
                break;
            case 'expired':
                $stime_title = '过期时间';
                break;
        }

        $columns = [
            'equipment' => [
                'title' => I18N::T('approval', '仪器名称'),
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $form['equipment'] ? H($form['equipment']) : null,
                ],
                'nowrap' => true,
            ],
            'reserv_time' => [
                'title' => I18N::T('approval', '预约时间'),
                'nowrap' => true,
            ],
            'amount' => [
                'title' => I18N::T('approval', '金额'),
                'align' => 'left',
                'nowrap' => true,
            ],
            'ctime' => [
                'title' => I18N::T('approval', '申请时间'),
                'nowrap' => true,
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/date', [
                        'form' => $form,
                        'name_prefix' => 'ctime_',
                        'dtstart' => $form['ctime_dtstart'],
                        'dtend' => $form['ctime_dtend'],
                    ]),
                    'value' => $form['ctime'] ? H($form['ctime']) : null,
                    'field' => 'ctime_dtstart,ctime_dtend',
                ],
            ],
            'stime' => [
                'title' => I18N::T('approval', $stime_title),
                'nowrap' => true,
            ],
            'description' => [
                'title' => I18N::T('approval', '备注'),
                'nowrap' => true,
            ],
            'rest' => [
                'align' => 'right',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }
}
