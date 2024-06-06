<?php
class Approval_Flow_Lab
{
    public static function setup_approval_tab()
    {
        Event::bind('lab.view.tab', 'Approval_Flow_Lab::lab_approval_tab', '1');
    }

    public static function lab_approval_tab($e, $tabs)
    {
        $me = L('ME');
        $lab = $tabs->lab;

        if (!$me->is_allowed_to('查看审核', $lab)) {
            return true;
        }
        if ($lab->reserv_approval) {
            Event::bind('lab.view.content', 'Approval_Flow_Lab::lab_approval_content', 0, 'reserv_approval');
            $tabs->add_tab('reserv_approval', [
                'url'=>$lab->url('reserv_approval'),
                'title'=>I18N::T('billing', '预约审批'),
                'weight' => 110
            ]);
        }
        if ($lab->sample_approval) {
            Event::bind('lab.view.content', 'Approval_Flow_Lab::lab_approval_content', 0, 'sample_approval');
            $tabs->add_tab('sample_approval', [
                'url'=>$lab->url('sample_approval'),
                'title'=>I18N::T('billing', '送样审批'),
                'weight' => 120
            ]);
        }
    }

    // tab名称和对应source_orm的哈希, 因为有重名tab
    static $_hash = [
        'reserv_approval' => 'eq_reserv',
        'sample_approval' => 'eq_sample',
    ];

    public static function lab_approval_content($e, $tabs)
    {
        $me = L('ME');
        $lab = $tabs->lab;

        $flow = Config::get('flow.'.self::$_hash[$tabs->selected]);
        foreach ($flow as $key => $v) {
            if ($me->can_approval($key, '')) {
                break;
            }
        }
        // type: reserv_approval , sample_approval
        $type = $tabs->selected;
        // status: approve_pi, done, reject ...
        $params = Config::get('system.controller_params');
        $status = $params[2] ? : $key;
        Event::bind('lab.approval.view.tabs', 'Approval_Flow_Lab::_lab_approval_tabs', 0, $status);
        Event::bind('lab.approval.view.content', 'Approval_Flow_Lab::_lab_approval_content', 0, $status);

        $tabs->content = V('approval_flow:profile/content');
        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('lab', $lab)
            ->set('type', $type)
            ->tab_event('lab.approval.view.tabs')
            ->content_event('lab.approval.view.content')
            ->select($status);
    }

    public static function _lab_approval_tabs($e, $tabs)
    {
        $me  = L('ME');
        $lab = $tabs->lab;
        $flow = Config::get('flow.'.self::$_hash[$tabs->type]);
        foreach ($flow as $step => $operation) {
            $criteria = [
                'lab' => $lab,
                'flag' => $step,
                'source_name' => self::$_hash[$tabs->type]
            ];
            $selector = Approval_Help::make_tab_selector($criteria);
            $tabs->add_tab($step, [
                'url'=>$lab->url("{$tabs->type}.{$step}"),
                'title'=> I18N::T('approval', $operation['title'].' (%count)', [
                    '%count'=> Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ])
            ->set('class', 'secondary_tabs');
        }
    }

    public static function _lab_approval_content($e, $tabs)
    {
        $me  = L('ME');
        $lab = $tabs->lab;
        // $tabs->type: reserv_approval , sample_approval
        $source_name = self::$_hash[$tabs->type];
        $flows = Config::get('flow.'.$source_name);
        foreach ($flows as $step => $flow) {
            if ($me->can_approval($step, '')) {
                $key = $step;
                break;
            }
        }

        $flag = $tabs->selected ? : $key;

        // $form = Lab::form(Approval_Help::date_filter_handler());
        $form = Lab::form();
        $criteria = [
            'lab' => $lab,
            'flag' => $flag,
            'source_name' => self::$_hash[$tabs->type],
            'sort' => 'ctime',
            'sort_asc' => 'D',
        ];
        $form = array_merge($form, $criteria);
        $selector = Approval_Help::make_tab_selector($form);
        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int)$form['st'], 20);

        switch ($tabs->type) {
            case 'reserv_approval':
                $columns = self::get_lab_reserv_approval_fields($form, $flag);
                $tabs->columns = new ArrayObject($columns);
                $tabs->search_box = V('application:search_box',
                    [
                        'panel_buttons' => $panel_buttons,
                        'top_input_arr' => ['equipment'],
                        'columns' => $columns,
                    ]
                );
                break;
            case 'sample_approval':
                $columns = self::get_lab_sample_approval_fields($form, $flag);
                $tabs->columns = new ArrayObject($columns);
                $tabs->search_box = V('application:search_box',
                    [
                        'panel_buttons' => $panel_buttons,
                        'top_input_arr' => ['equipment'],
                        'columns' => $columns,
                    ]
                );
                break;
        }

        $tabs->content = V("approval_flow:approval/list_{$source_name}_lab", [
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ? : 'date',
            'sort_asc' => $form['sort_asc'],
        ]);
    }

    public static function get_lab_reserv_approval_fields($form, $flag) 
    {
        if($form['source_dtstart'] || $form['source_dtend']) {
            $form['source_time'] = true;
        }

        switch($flag) {
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
            'user'=> [
                'title' => I18N::T('approval', '申请人'),
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/user', ['user' => $form['user']]),
                    'value' => $form['user'] ? O('user', H($form['user']))->name : NULL
                ],
                'nowrap' => TRUE
            ],
            'equipment'=> [
                'title' => I18N::T('approval', '仪器名称'),
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $form['equipment'] ? H($form['equipment']) : NULL
                ],
                'nowrap' => TRUE
            ],
            'reserv_time'=> [
                'title' => I18N::T('approval', '预约时间'),
                'nowrap' => TRUE,
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/date', [
                        'form' => $form,
                        'dtstart' => $form['source_dtstart'],
                        'dtend' => $form['source_dtend'],
                        'name_prefix' => 'source_'
                    ]),
                    'value' => $form['source_time'] ? H($form['source_time']) : NULL,
                    'field' => 'source_dtstart,source_dtend'
                ]
            ],
            'amount' => [
                'title' => I18N::T('approval', '金额'),
                'align' => 'left',
                'nowrap' =>TRUE,
            ],
            'ctime'=> [
                'title' => I18N::T('approval', '申请时间'),
                'nowrap' => TRUE
            ],
            'stime'=> [
                'title' => I18N::T('approval', $stime_title),
                'nowrap' => TRUE
            ],
            'description'=> [
                'title' => I18N::T('approval', '备注'),
                'nowrap' => TRUE
            ],
            'rest'=>[
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];

        return $columns;
    }

    public static function get_lab_sample_approval_fields($form, $flag) 
    {
        if($form['ctime_dtstart'] || $form['ctime_dtend']) {
            $form['ctime'] = true;
        }

        switch($flag) {
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
            'user'=> [
                'title' => I18N::T('approval', '申请人'),
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/user', ['user' => $form['user']]),
                    'value' => $form['user'] ? O('user', H($form['user']))->name : NULL
                ],
                'nowrap' => TRUE
            ],
            'equipment'=> [
                'title' => I18N::T('approval', '仪器名称'),
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $form['equipment'] ? H($form['equipment']) : NULL
                ],
                'nowrap' => TRUE
            ],
            'date' => [
                'title' => I18N::T('approval', '送样时间'),
                'align' => 'left',
                'nowrap' =>TRUE,
            ],
            'count' => [
                'title' => I18N::T('approval', '送样数'),
                'align' => 'left',
                'nowrap' =>TRUE,
            ],
            'amount' => [
                'title' => I18N::T('approval', '金额'),
                'align' => 'left',
                'nowrap' =>TRUE,
            ],
            'ctime'=> [
                'title' => I18N::T('approval', '申请时间'),
                'nowrap' => TRUE,
                'filter' => [
                    'form' => V('approval_flow:approval_table/filters/date', [
                        'form' => $form,
                        'dtstart' => $form['ctime_dtstart'],
                        'dtend' => $form['ctime_dtend'],
                        'name_prefix' => 'ctime_'
                    ]),
                    'value' => $form['ctime'] ? H($form['ctime']) : NULL,
                    'field' => 'ctime_dtstart,ctime_dtend'
                ]
            ],
            'stime'=> [
                'title' => I18N::T('approval', $stime_title),
                'nowrap' => TRUE
            ],
            'description'=> [
                'title' => I18N::T('approval', '备注'),
                'nowrap' => TRUE
            ],
            'rest'=>[
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];

        return $columns;
    }

    public static function approval_config_tab($e, $tabs)
    {
        $me = L('ME');
        $lab = $tabs->lab;
        if ($me->is_allowed_to('查看审核', $lab)) {
            $tabs->add_tab('approval', [
                'url'=>$lab->url('approval', null, null, 'edit'),
                'title'=>I18N::T('labs', '预约/送样审核'),
                'weight' => 120
            ]);
            Event::bind('lab.edit.content', 'Approval_Flow_Lab::_config_approval', 0, 'approval');
        }
    }

    public static function _config_approval($e, $tabs)
    {
        $me = L('ME');
        $lab = $tabs->lab;
        if (!$me->access('管理所有内容') && !Q("{$me}<pi {$lab}")->total_count()) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());

        if (Input::form('submit')) {
            $lab->sample_approval = $form['sample_approval'] == 'on';
            $lab->reserv_approval = $form['reserv_approval'] == 'on';

            Event::trigger('approval_flow.lab_setting.extra_validate',$lab,$form);

            if ($form->no_error){
                // 送样审核
                $lab->sample_approval_unlimit_users_mode = null;
                $lab->sample_approval_unlimit_users = null;
                $lab->sample_approval_unlimit_amount_mode = null;
                $lab->sample_approval_unlimit_amount = null;
                $lab->sample_approval_unlimit_time_mode = null;
                $lab->sample_approval_unlimit_time_mins = null;
                $lab->sample_approval_unlimit_time_type = null;
                if ($lab->sample_approval) {
                    $lab->sample_approval_unlimit_users_mode = $form['sample_approval_unlimit_users_mode'] == 'on';
                    $lab->sample_approval_unlimit_amount_mode = $form['sample_approval_unlimit_amount_mode'] == 'on';
                    $lab->sample_approval_unlimit_time_mode = $form['sample_approval_unlimit_time_mode'] == 'on';

                    // 免审用户
                    if ($lab->sample_approval_unlimit_users_mode) {
                        $unlimit_users = [];
                        $users = json_decode($form['sample_approval_unlimit_users'], true);
                        if (is_array($users) && !empty($users)) {
                            foreach($users as $user_id => $user_name) {
                                $user = O('user', $user_id);
                                if ($user->id) {
                                    $unlimit_users[] = $user->id;
                                }
                            }
                        }
                        $lab->sample_approval_unlimit_users = $unlimit_users;
                    }
 
                    // 免审金额
                    if ($lab->sample_approval_unlimit_amount_mode) {
                        $lab->sample_approval_unlimit_amount = $form['sample_approval_unlimit_amount'];
                    }

                    // 审核时限
                    if ($lab->sample_approval_unlimit_time_mode) {
                        $lab->sample_approval_unlimit_time_mins = Date::convert_interval($form['sample_approval_unlimit_time_mins'], $form['sample_approval_unlimit_time_format']);
                        $lab->sample_approval_unlimit_time_type = $form['sample_approval_unlimit_time_type'] == 1 ? 1 : 2;
                    }
                }

                // 预约审核
                $lab->reserv_approval_unlimit_users_mode = null;
                $lab->reserv_approval_unlimit_users = null;
                $lab->reserv_approval_unlimit_amount_mode = null;
                $lab->reserv_approval_unlimit_amount = null;
                $lab->reserv_approval_unlimit_time_mode = null;
                $lab->reserv_approval_unlimit_time_mins = null;
                $lab->reserv_approval_unlimit_time_type = null;
                if ($lab->reserv_approval) {
                    $lab->reserv_approval_unlimit_users_mode = $form['reserv_approval_unlimit_users_mode'] == 'on';
                    $lab->reserv_approval_unlimit_amount_mode = $form['reserv_approval_unlimit_amount_mode'] == 'on';
                    $lab->reserv_approval_unlimit_time_mode = $form['reserv_approval_unlimit_time_mode'] == 'on';

                    // 免审用户
                    if ($lab->reserv_approval_unlimit_users_mode) {
                        $unlimit_users = [];
                        $users = json_decode($form['reserv_approval_unlimit_users'], true);
                        if (is_array($users) && !empty($users)) {
                            foreach($users as $user_id => $user_name) {
                                $user = O('user', $user_id);
                                if ($user->id) {
                                    $unlimit_users[] = $user->id;
                                }
                            }
                        }
                        $lab->reserv_approval_unlimit_users = $unlimit_users;
                    }
 
                    // 免审金额
                    if ($lab->reserv_approval_unlimit_amount_mode) {
                        $lab->reserv_approval_unlimit_amount = $form['reserv_approval_unlimit_amount'];
                    }

                    // 审核时限
                    if ($lab->reserv_approval_unlimit_time_mode) {
                        $lab->reserv_approval_unlimit_time_mins = Date::convert_interval($form['reserv_approval_unlimit_time_mins'], $form['reserv_approval_unlimit_time_format']);
                        $lab->reserv_approval_unlimit_time_type = $form['reserv_approval_unlimit_time_type'] == 1 ? 1 : 2;
                    }
                }

                // 清理缓存，防止提交后页面显示不一致
                unset($form['sample_approval_unlimit_users_mode']);
                unset($form['sample_approval_unlimit_users']);
                unset($form['sample_approval_unlimit_amount_mode']);
                unset($form['sample_approval_unlimit_amount']);
                unset($form['sample_approval_unlimit_time_mode']);
                unset($form['sample_approval_unlimit_time_type']);
                unset($form['reserv_approval_unlimit_users_mode']);
                unset($form['reserv_approval_unlimit_users']);
                unset($form['reserv_approval_unlimit_amount_mode']);
                unset($form['reserv_approval_unlimit_amount']);
                unset($form['reserv_approval_unlimit_time_mode']);
                unset($form['reserv_approval_unlimit_time_type']);

                Event::trigger('approval_flow.lab_setting.extra_submit',$lab,$form);

                if ($lab->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '审核设置已更新'));
                    Log::add(strtr('[approval_flow] %user_name[%user_id]更新课题组%lab_name[%lab_id]审核设置: %form', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%lab_name' => $lab->name,
                        '%lab_id' => $lab->id,
                        '%form' => json_encode($form, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
                    ]), 'journal');
                }
            }
        }

        $tabs->content = V('approval_flow:lab/config.approval', [
            'form' => $form,
            'lab' => $lab,
        ]);
    }

}
