<?php

class Training
{
    public static function setup_view()
    {
        Event::bind('equipment.index.tab', 'Training::index_training_tab');
        //Event::bind('equipment.index.tab.tool_box', 'Training::_tool_box_training', 0, 'training');
        Event::bind('equipment.index.tab.content', 'Training::index_training_tab_content', 0, 'training');
    }

    public static function _tool_box_training($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $training = array_pop(explode('.', _U())); // approved applied overdue group training
        $sort_fields = Config::get('equipments.training.sortable_columns');
        $panel_buttons = [];
        switch ($training) {
            case 'training':
            case 'applied':
                $form = Form::filter(Input::form());
                $status = implode(',', [
                    UE_Training_Model::STATUS_APPLIED,
                    UE_Training_Model::STATUS_AGAIN,
                ]);
                $selector = "ue_training[equipment={$equipment}][status=$status]";
                $field = [
                    'approved_name' => [
                        'title' => I18N::T('people', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name'], 'tip' => '请输入姓名']),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                        ],
                        'nowrap' => true,
                    ],
                    'contact_info' => ['sortable' => in_array('contact_info', $sort_fields), 'title' => I18N::T('people', '联系方式'), 'nowrap' => true],
                    // 'address' => ['sortable' => in_array('address', $sort_fields), 'title' => I18N::T('people', '地址'), 'nowrap' => true],
                    'rest' => ['nowrap' => true, 'title' => I18N::T('people', '操作'), 'align' => 'left'],
                ];
                break;
            case 'approved':
                $form = Lab::form(function (&$old_form, &$form) {
                });
                $status = UE_Training_Model::STATUS_APPROVED;
                $selector = "ue_training[equipment={$equipment}][status=$status]";

                if ($form['mtime_start'] || $form['mtime_end']) {
                    $form['mtime'] = true;
                }

                if ($form['atime_start'] || $form['atime_end']) {
                    $form['atime'] = true;
                }

                $field = [
                    'approved_name' => [
                        'title' => I18N::T('equipments', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name']]),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                            'field' => 'approved_name',
                        ],
                        'nowrap' => true,
                    ],
                    'contact_info' => [
                        'sortable' => in_array('contact_info', $sort_fields),
                        'title' => I18N::T('equipments', '联系方式'),
                        'nowrap' => true,
                    ],
                    // 'address' => [
                    //     'sortable' => in_array('address', $sort_fields),
                    //     'title' => I18N::T('equipments', '地址'),
                    //     'nowrap' => true,
                    // ],
                    'mtime' => [
                        'sortable' => in_array('mtime', $sort_fields),
                        'title' => I18N::T('equipments', '通过时间'),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'start' => 'mtime_start',
                                'end' => 'mtime_end',
                                'dtstart' => $form['mtime_start'],
                                'dtend' => $form['mtime_end'],
                            ]),
                            'value' => $form['mtime'] ? H($form['mtime']) : null,
                            'field' => 'mtime_dtstart,mtime_dtend',
                        ],
                        'nowrap' => true,
                    ],
                    'atime' => [
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'start' => 'atime_start',
                                'end' => 'atime_end',
                                'dtstart' => $form['atime_start'],
                                'dtend' => $form['atime_end'],
                            ]),
                            'value' => $form['atime'] ? H($form['atime']) : null,
                            'field' => 'atime_start,atime_end',
                        ],
                        'nowrap' => true,
                        /* TODO 'sortable' => TRUE, need some controller work*/
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'extra_class' => '',
                        'align' => 'left',
                    ],
                ];
                $panel_buttons[] = [
                    'tip' => I18N::T('equipments', '添加用户'),
                    'text' => I18N::T('equipments', '添加'),
                    'extra' => 'class="button button_add view object:add_approved_user event:click static:equipment_id=' . $equipment->id . ' src:' . URI::url('!equipments/training') . '"',
                    'url' => null,
                ];
                break;
            case 'overdue':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                if ($form['atime_dtstart'] || $form['atime_dtend']) {
                    $form['atime_date'] = true;
                }

                if ($form['lab']) {
                    $lab = O('lab', $form['lab']);
                }

                $field = [
                    'approved_name' => [
                        'title' => I18N::T('people', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name']]),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                        ],
                        'nowrap' => true,
                    ],
                    'lab' => [
                        'title' => I18N::T('labs', '实验室'),
                        'invisible' => true,
                        'suppressible' => true,
                        'filter' => [
                            'form' => Widget::factory('labs:lab_selector', [
                                'name' => 'lab',
                                'selected_lab' => $lab,
                                'all_labs' => true,
                                'no_lab' => true,
                                'size' => 15,
                            ]),
                            'value' => $lab->id ? H($lab->name) : null,
                        ],
                    ],
                    'contact_info' => [
                        'sortable' => in_array('contact_info', $sort_fields),
                        'title' => I18N::T('people', '联系方式'),
                        'nowrap' => true,
                    ],
                    // 'address' => [
                    //     'sortable' => in_array('address', $sort_fields),
                    //     'title' => I18N::T('people', '地址'),
                    //     'nowrap' => true,
                    // ],
                    'atime' => [
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/atime', [
                                'form' => $form,
                            ]),
                            'value' => $form['atime_date'] ? H($form['atime_date']) : null,
                            'field' => 'atime_dtstart,atime_dtend',
                        ],
                        'extra_class' => '',
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'extra_class' => 'last',
                        'align' => 'left',
                    ],
                ];
                $status = UE_Training_Model::STATUS_OVERDUE;
                $selector = "ue_training[equipment={$equipment}][status=$status]";
                break;
            case 'group':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                $selector = "ge_training[equipment={$equipment}]";

                $sort_by = $form['sort'] ?: 'id';
                $sort_user = " ";
                switch ($sort_by) {
                    case 'user':
                        $sort_user = ":sort(name_abbr $sort_flag) ";
                        break;
                    case 'ntotal':
                        $selector .= ":sort(ntotal $sort_flag)";
                        break;
                    case 'address':
                        $selector .= ":sort(address_abbr $sort_flag)";
                        break;
                    case 'napproved':
                        $selector .= ":sort(napproved $sort_flag)";
                        break;
                    case 'date':
                        $selector .= ":sort(date $sort_flag)";
                        break;
                    default:
                        $selector .= ":sort(id $sort_flag)";
                        break;
                }

                if ($form['name']) {
                    $name = Q::quote($form['name']);
                    $selector = "user[name*={$name}|name_abbr^={$name}]" . $sort_user . $selector;
                } else {
                    $selector = "user" . $sort_user . $selector;
                }

                if ($form['ctime_start'] || $form['ctime_end']) {
                    $form['ctime'] = true;
                }

                $field = [
                    'user' => [
                        'title' => I18N::T('equipments', '负责人'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:users_table/filters/name', ['name' => $form['name']]),
                            'value' => $form['name'] ? H($form['name']) : null,
                            'field' => 'name',
                        ],
                        'nowrap' => true,
                    ],
                    'ntotal' => [
                        'title' => I18N::T('equipments', '总培训人数'),
                        'sortable' => in_array('ntotal', $sort_fields),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'napproved' => [
                        'title' => I18N::T('equipments', '通过人数'),
                        'sortable' => in_array('napproved', $sort_fields),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'date' => [
                        'title' => I18N::T('equipments', '培训时间'),
                        'sortable' => in_array('date', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'startcheck_name' => 'ctime_start_check',
                                'endcheck_name' => 'ctime_end_check',
                                'dtstart_check' => $form['ctime_start_check'],
                                'dtend_check' => $form['ctime_end_check'],
                                'start' => 'ctime_start',
                                'end' => 'ctime_end',
                                'dtstart' => $form['ctime_start'],
                                'dtend' => $form['ctime_end'],
                                'filter' => 'ctime_filter',
                            ]),
                            'value' => $form['ctime'] ? H($form['ctime']) : null,
                            'field' => 'ctime_start_check,ctime_end_check,ctime_start,ctime_end',
                        ],
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'description' => [
                        'title' => I18N::T('equipments', '描述'),
                        'nowrap' => true,
                        'extra_class' => '',
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'align' => 'left',
                    ],
                ];

                $panel_buttons[] = [
                    'tip' => I18N::T('equipments', '添加'),
                    'text' => I18N::T('equipments', '添加'),
                    'extra' => 'class="button button_add view object:group_add event:click static:equipment_id=' . $equipment->id . ' src:' . URI::url('!equipments/training') . '"',
                    'url' => '#',
                ];
                break;
            default:
                $status = '';
                break;
        }

        $training = $training == 'training' ? 'applied' : $training;

        $form_token = Session::temp_token('training', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        $me = L('ME');
        $panel_buttons[] = [
            'tip' => I18N::HT('equipments', '导出Excel'),
            'text' => I18N::HT('equipments', '导出'),
            'extra' => 'q-object="export_' . $training . '" q-event="click" q-src="' . H(URI::url('!equipments/training')) . '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) . '" class="button button_save "',
            'url' => null,
        ];

        $panel_buttons[] = [
            'tip' => I18N::HT('equipments', '打印'),
            'text' => I18N::HT('equipments', '打印'),
            'extra' => 'q-object="export_' . $training . '" q-event="click" q-src="' . H(URI::url('!equipments/training')) . '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) . '" class="button button_print "',
            'url' => null,
        ];

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['approved_name', 'user'], 'columns' => $field]);
        $tabs->field = $field;
    }

    public static function _equipment_training($tabs)
    {
        $equipment = $tabs->equipment;
        $training = array_pop(explode('.', _U())); // approved applied overdue group training
        $sort_fields = Config::get('equipments.training.sortable_columns');
        $panel_buttons = [];
        switch ($training) {
            case 'training':
            case 'applied':
                $form = Form::filter(Input::form());
                $status = implode(',', [
                    UE_Training_Model::STATUS_APPLIED,
                    UE_Training_Model::STATUS_AGAIN,
                ]);
                $equipment = $tabs->equipment;
                $selector = "ue_training[equipment={$equipment}][status=$status]";

                if($form['ctime_s']){
                    $cs = strtotime(date('Y-m-d',$form['ctime_s']));
                    $cs = Date::get_day_start($cs);
                    $selector .= "[ctime>={$cs}]";
                }
                if($form['ctime_e']){
                    $ce = strtotime(date('Y-m-d',$form['ctime_e']));
                    $ce = Date::get_day_end($ce);
                    $selector .= "[ctime<={$ce}]";
                }
                if($form['check_time_s']){
                    $cs = strtotime(date('Y-m-d',$form['check_time_s']));
                    $cs = Date::get_day_start($cs);
                    $selector .= "[check_time>={$cs}]";
                }
                if($form['check_time_e']){
                    $ce = strtotime(date('Y-m-d',$form['check_time_e']));
                    $ce = Date::get_day_end($ce);
                    $selector .= "[check_time<={$ce}]";
                }
        
                if ($form['approved_name']) {
                    $approved_name = Q::quote(trim($form['approved_name']));
                    $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
                }
        
                //通过培训, 通过时间逆序
                $sort_by = $form['sort'] ?: 'mtime';
                $sort_asc = $form['sort_asc'];
                $sort_flag = $sort_asc ? 'A' : 'D';
        
                switch ($sort_by) {
                    case 'atime':
                    case 'mtime':
                        $selector .= ":sort($sort_by $sort_flag)";
                        break;
                    case 'name':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.name_abbr $sort_flag)";
                        break;
                    case 'address':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.address_abbr $sort_flag)";
                        break;
                    case 'contact_info':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.phone $sort_flag)";
                        break;
                    case 'equipment':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.name_abbr $sort_flag)";
                        break;
                    case 'location':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.location_abbr $sort_flag)";
                        break;
                    case 'contact':
                        $selector = "user equipment" . $selector;
                        $selector .= ":sort(user.contact_abbr $sort_flag)";
                        break;
                    case 'control':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.control $sort_flag)";
                        break;
                    default:
                        $selector .= ":sort(mtime $sort_flag)";
                        break;
                }

                $field = [
                    'approved_name' => [
                        'title' => I18N::T('people', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name'], 'tip' => '请输入姓名']),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                        ],
                        'nowrap' => true,
                        'weight' => 10
                    ],
                    'contact_info' => ['sortable' => in_array('contact_info', $sort_fields), 'title' => I18N::T('people', '联系方式'), 'nowrap' => true,'weight' => 20],
                    'ctime'=>[
                        'title'=>I18N::T('equipments', '申请时间'),
                        'filter'=>[
                            'form'=>V('equipments:training_table/filters/ctime', ['form'=>$form]),
                            'field'=>'ctime_s,ctime_e',
                            'value' => $form['ctime_s'] || $form['ctime_e'],
                        ],
                        'nowrap'=>true,
                        'weight' => 30
                    ],
                    // 'address' => ['sortable' => in_array('address', $sort_fields), 'title' => I18N::T('people', '地址'), 'nowrap' => true, 'weight' => 40],
                    'rest' => ['nowrap' => true, 'title' => I18N::T('people', '操作'), 'align' => 'left', 'weight' => 50],
                ];
                $panel_buttons[] = [
                    'text' => I18N::T('equipments', '添加授权'),
                    'extra' => 'q-object="incharge_add_approved_user" q-event="click" q-static="'.H(['eid' => $equipment->id]).'" q-src="' . URI::url('!equipments/training') .
                        '" class="button button_add"'
                ];
                break;
            case 'approved':
                $form = Lab::form(function (&$old_form, &$form) {
                });
                $status = UE_Training_Model::STATUS_APPROVED;
                $equipment = $tabs->equipment;
                $selector = "ue_training[equipment={$equipment}][status=$status]";

                if ($form['approved_name']) {
                    $approved_name = Q::quote(trim($form['approved_name']));
                    $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
                }
        
                if ($form['mtime_start']) {
                    $mtime_start = Q::quote($form['mtime_start']);
                    $selector .= "[mtime >= $mtime_start]";
                }
        
                if ($form['mtime_end']) {
                    $mtime_end = Q::quote($form['mtime_end']);
                    $selector .= "[mtime <= $mtime_end]";
                }
        
                /*
                atime不过期相当于期限最大，所以判断开始时间时也应关联到期时间为空的数据
                 */
                if ($form['atime_start']) {
                    $atime_start = Q::quote($form['atime_start']);
                    $selector .= "[atime >= $atime_start | !atime]";
                }
        
                if ($form['atime_end']) {
                    $atime_end = Q::quote($form['atime_end']);
                    $selector .= "[atime][atime <= $atime_end]";
                }
        
                // 通过培训, 通过时间逆序
                $sort_by = $form['sort'] ?: 'mtime';
                $sort_asc = $form['sort_asc'];
                $sort_flag = $sort_asc ? 'A' : 'D';
        
                switch ($sort_by) {
                    case 'atime':
                    case 'mtime':
                        $selector .= ":sort($sort_by $sort_flag)";
                        break;
                    case 'name':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.name_abbr $sort_flag)";
                        break;
                    case 'address':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.address_abbr $sort_flag)";
                        break;
                    case 'contact_info':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.phone $sort_flag)";
                        break;
                    case 'equipment':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.name_abbr $sort_flag)";
                        break;
                    case 'location':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.location_abbr $sort_flag)";
                        break;
                    case 'contact':
                        $selector = "user equipment" . $selector;
                        $selector .= ":sort(user.contact_abbr $sort_flag)";
                        break;
                    case 'control':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.control $sort_flag)";
                        break;
                    default:
                        $selector .= ":sort(mtime $sort_flag)";
                        break;
                }

                $field = [
                    'approved_name' => [
                        'title' => I18N::T('equipments', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name']]),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                            'field' => 'approved_name',
                        ],
                        'nowrap' => true,
                    ],
                    'contact_info' => [
                        'sortable' => in_array('contact_info', $sort_fields),
                        'title' => I18N::T('equipments', '联系方式'),
                        'nowrap' => true,
                    ],
                    // 'address' => [
                    //     'sortable' => in_array('address', $sort_fields),
                    //     'title' => I18N::T('equipments', '地址'),
                    //     'nowrap' => true,
                    // ],
                    'mtime' => [
                        'sortable' => in_array('mtime', $sort_fields),
                        'title' => I18N::T('equipments', '通过时间'),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'start' => 'mtime_start',
                                'end' => 'mtime_end',
                                'dtstart' => $form['mtime_start'],
                                'dtend' => $form['mtime_end'],
                            ]),
                            'value' => $form['mtime_start'] || $form['mtime_end'],
                            'field' => 'mtime_dtstart,mtime_dtend',
                        ],
                        'nowrap' => true,
                    ],
                    'atime' => [
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'start' => 'atime_start',
                                'end' => 'atime_end',
                                'dtstart' => $form['atime_start'],
                                'dtend' => $form['atime_end'],
                            ]),
                            'value' => $form['atime_start'] || $form['atime_end'],
                            'field' => 'atime_start,atime_end',
                        ],
                        'nowrap' => true,
                        /* TODO 'sortable' => TRUE, need some controller work*/
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'extra_class' => '',
                        'align' => 'left',
                    ],
                ];
                /*$panel_buttons[] = [
                    'tip' => I18N::T('equipments', '添加用户'),
                    'text' => I18N::T('equipments', '添加'),
                    'extra' => 'class="button button_add view object:add_approved_user event:click static:equipment_id=' . $equipment->id . ' src:' . URI::url('!equipments/training') . '"',
                    'url' => null,
                ];*/
                $panel_buttons[] = [
                    'text' => I18N::T('equipments', '添加授权'),
                    'extra' => 'q-object="incharge_add_approved_user" q-event="click" q-static="'.H(['eid' => $equipment->id]).'"  q-src="' . URI::url('!equipments/training') .
                        '" class="button button_add"'
                ];
                break;
            case 'overdue':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                $status = UE_Training_Model::STATUS_OVERDUE;
                $equipment = $tabs->equipment;
        
                $selector = "ue_training[equipment={$equipment}][status=$status]";
        
                if ($form['approved_name']) {
                    $approved_name = Q::quote(trim($form['approved_name']));
                    $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
                }
        
                if ($form['lab']) {
                    $lab = O('lab', trim($form['lab']));
                    //$pre_selectors[] = "$lab user";
                    $pre_selectors[] = $form['approved_name']? "$lab" : "$lab user";
                }
        
                // atime不过期相当于期限最大，所以判断开始时间时也应关联到期时间为空的数据
                if ($form['atime_dtstart']) {
                    $atime_start = Q::quote($form['atime_dtstart']);
                    $selector .= "[atime >= $atime_start | !atime]";
                }
        
                if ($form['atime_dtend']) {
                    $atime_end = Q::quote($form['atime_dtend']);
                    $selector .= "[atime][atime <= $atime_end]";
                }
        
                if ($pre_selectors) {
                    $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
                }
        
                //通过培训, 通过时间逆序
                $sort_by = $form['sort'] ?: 'mtime';
                $sort_asc = $form['sort_asc'];
                $sort_flag = $sort_asc ? 'A' : 'D';
        
                switch ($sort_by) {
                    case 'atime':
                    case 'mtime':
                        $selector .= ":sort($sort_by $sort_flag)";
                        break;
                    case 'approved_name':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.name_abbr $sort_flag)";
                        break;
                    case 'address':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.address_abbr $sort_flag)";
                        break;
                    case 'contact_info':
                        $selector = "user " . $selector;
                        $selector .= ":sort(user.phone $sort_flag)";
                        break;
                    case 'equipment':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.name_abbr $sort_flag)";
                        break;
                    case 'location':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.location_abbr $sort_flag)";
                        break;
                    case 'contact':
                        $selector = "user equipment" . $selector;
                        $selector .= ":sort(user.contact_abbr $sort_flag)";
                        break;
                    case 'control':
                        $selector = "equipment" . $selector;
                        $selector .= ":sort(equipment.control $sort_flag)";
                        break;
                    default:
                        $selector .= ":sort(mtime $sort_flag)";
                        break;
                }

                $field = [
                    'approved_name' => [
                        'title' => I18N::T('people', '姓名'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_name', ['value' => $form['approved_name']]),
                            'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                        ],
                        'nowrap' => true,
                    ],
                    'lab' => [
                        'title' => I18N::T('labs', '实验室'),
                        'invisible' => true,
                        'suppressible' => true,
                        'filter' => [
                            'form' => Widget::factory('labs:lab_selector', [
                                'name' => 'lab',
                                'selected_lab' => $lab,
                                'all_labs' => true,
                                'no_lab' => true,
                                'size' => 15,
                            ]),
                            'value' => $form['lab'],
                        ],
                    ],
                    'contact_info' => [
                        'sortable' => in_array('contact_info', $sort_fields),
                        'title' => I18N::T('people', '联系方式'),
                        'nowrap' => true,
                    ],
                    // 'address' => [
                    //     'sortable' => in_array('address', $sort_fields),
                    //     'title' => I18N::T('people', '地址'),
                    //     'nowrap' => true,
                    // ],
                    'atime' => [
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/atime', [
                                'form' => $form,
                            ]),
                            'value' => $form['atime_dtstart'] || $form['atime_dtend'],
                            'field' => 'atime_dtstart,atime_dtend',
                        ],
                        'extra_class' => '',
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'extra_class' => 'last',
                        'align' => 'left',
                    ],
                ];
                $status = UE_Training_Model::STATUS_OVERDUE;
                $selector = "ue_training[equipment={$equipment}][status=$status]";
                break;
            case 'group':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                $selector = "ge_training[equipment={$equipment}]";

                $sort_by = $form['sort'] ?: 'id';
                $sort_user = " ";
                switch ($sort_by) {
                    case 'user':
                        $sort_user = ":sort(name_abbr $sort_flag) ";
                        break;
                    case 'ntotal':
                        $selector .= ":sort(ntotal $sort_flag)";
                        break;
                    case 'address':
                        $selector .= ":sort(address_abbr $sort_flag)";
                        break;
                    case 'napproved':
                        $selector .= ":sort(napproved $sort_flag)";
                        break;
                    case 'date':
                        $selector .= ":sort(date $sort_flag)";
                        break;
                    default:
                        $selector .= ":sort(id $sort_flag)";
                        break;
                }

                if ($form['name']) {
                    $name = Q::quote($form['name']);
                    $selector = "user[name*={$name}|name_abbr^={$name}]" . $sort_user . $selector;
                } else {
                    $selector = "user" . $sort_user . $selector;
                }

                if ($form['ctime_start'] || $form['ctime_end']) {
                    $form['ctime'] = true;
                }

                $field = [
                    'user' => [
                        'title' => I18N::T('equipments', '负责人'),
                        'sortable' => in_array('user', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:users_table/filters/name', ['name' => $form['name']]),
                            'value' => $form['name'] ? H($form['name']) : null,
                            'field' => 'name',
                        ],
                        'nowrap' => true,
                    ],
                    'ntotal' => [
                        'title' => I18N::T('equipments', '总培训人数'),
                        'sortable' => in_array('ntotal', $sort_fields),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'napproved' => [
                        'title' => I18N::T('equipments', '通过人数'),
                        'sortable' => in_array('napproved', $sort_fields),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'date' => [
                        'title' => I18N::T('equipments', '培训时间'),
                        'sortable' => in_array('date', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:training_table/filters/approved_date', [
                                'startcheck_name' => 'ctime_start_check',
                                'endcheck_name' => 'ctime_end_check',
                                'dtstart_check' => $form['ctime_start_check'],
                                'dtend_check' => $form['ctime_end_check'],
                                'start' => 'ctime_start',
                                'end' => 'ctime_end',
                                'dtstart' => $form['ctime_start'],
                                'dtend' => $form['ctime_end'],
                                'filter' => 'ctime_filter',
                            ]),
                            'value' => $form['ctime'] ? H($form['ctime']) : null,
                            'field' => 'ctime_start_check,ctime_end_check,ctime_start,ctime_end',
                        ],
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'description' => [
                        'title' => I18N::T('equipments', '描述'),
                        'nowrap' => true,
                        'extra_class' => '',
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'nowrap' => true,
                        'align' => 'left',
                    ],
                ];

                $panel_buttons[] = [
                    'tip' => I18N::T('equipments', '添加'),
                    'text' => I18N::T('equipments', '添加'),
                    'extra' => 'class="button button_add view object:group_add event:click static:equipment_id=' . $equipment->id . ' src:' . URI::url('!equipments/training') . '"',
                    'url' => '#',
                ];
                break;
            default:
                $status = '';
                break;
        }

        $training = $training == 'training' ? 'applied' : $training;

        $form_token = Session::temp_token('training', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        $me = L('ME');
        $panel_buttons[] = [
            'tip' => I18N::HT('equipments', '导出Excel'),
            'text' => I18N::HT('equipments', '导出'),
            'extra' => 'q-object="export_' . $training . '" q-event="click" q-src="' . H(URI::url('!equipments/training')) . '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token, 'equipment_id' => $equipment->id]) . '" class="button button_save "',
            'url' => null,
        ];

        $panel_buttons[] = [
            'tip' => I18N::HT('equipments', '打印'),
            'text' => I18N::HT('equipments', '打印'),
            'extra' => 'q-object="export_' . $training . '" q-event="click" q-src="' . H(URI::url('!equipments/training')) . '" q-static="' . H(['type' => 'print', 'form_token' => $form_token, 'equipment_id' => $equipment->id]) . '" class="button button_print "',
            'url' => null,
        ];


        $field = new ArrayIterator($field);
        Event::trigger('equipment_training.list.columns', $field, $equipment);
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['approved_name', 'user'], 'columns' => (array)$field]);
        $tabs->field = (array)$field;
    }

    public static function index_training_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;

        if (L('ME')->is_allowed_to('管理培训', $equipment)) {
            if ($equipment->require_training && $equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE) {
                $tabs->add_tab('training', [
                    'url' => $equipment->url('training'),
                    'title' => I18N::T('equipments', '使用培训 / 授权'),
                    'weight' => 70,
                ]);
            }
        }
    }

    public static function index_training_tab_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $params = Config::get('system.controller_params');

        Event::bind('equipment.training.tab.content', 'Training::_index_training_approved', 0, 'approved');
        Event::bind('equipment.training.tab.content', 'Training::_index_training_applied', 0, 'applied');
        Event::bind('equipment.training.tab.content', 'Training::_index_training_overdue', 0, 'overdue');
        Event::bind('equipment.training.tab.content', 'Training::_index_training_group', 0, 'group');

        $tabs->content = V('training/index');
        $tabs->content->tertiary_tabs = Widget::factory('tabs')
            ->add_tab('applied', [
                'url' => $equipment->url('training.applied'),
                'title' => I18N::T('equipments', '已申请培训 / 授权的'),
                'weight' => '10',
            ])
            ->add_tab('approved', [
                'url' => $equipment->url('training.approved'),
                'title' => I18N::T('equipments', '已通过培训 / 授权的'),
                'weight' => '20',
            ])
            ->add_tab('overdue', [
                'url' => $equipment->url('training.overdue'),
                'title' => I18N::T('equipments', '已过期培训 / 授权的'),
                'weight' => '30',
            ])
            ->add_tab('group', [
                'url' => $equipment->url('training.group'),
                'title' => I18N::T('equipments', '团体培训'),
                'weight' => '40',
            ])
            ->content_event('equipment.training.tab.content')
            ->set('class', 'secondary_tabs')
            ->set('equipment', $equipment)
            ->select($params[2]);

        self::_equipment_training($tabs->content->tertiary_tabs);
    }

    public static function _index_training_approved($e, $tabs)
    {
        $form = Lab::form(function (&$old_form, &$form) {
        });
        $status = UE_Training_Model::STATUS_APPROVED;
        $equipment = $tabs->equipment;
        $selector = "ue_training[equipment={$equipment}][status=$status]";

        if ($form['approved_name']) {
            $approved_name = Q::quote(trim($form['approved_name']));
            $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
        }

        if ($form['mtime_start']) {
            $mtime_start = Q::quote($form['mtime_start']);
            $selector .= "[mtime >= $mtime_start]";
        }

        if ($form['mtime_end']) {
            $mtime_end = Q::quote($form['mtime_end']);
            $selector .= "[mtime <= $mtime_end]";
        }

        /*
        atime不过期相当于期限最大，所以判断开始时间时也应关联到期时间为空的数据
         */
        if ($form['atime_start']) {
            $atime_start = Q::quote($form['atime_start']);
            $selector .= "[atime >= $atime_start | !atime]";
        }

        if ($form['atime_end']) {
            $atime_end = Q::quote($form['atime_end']);
            $selector .= "[atime][atime <= $atime_end]";
        }

        // 通过培训, 通过时间逆序
        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'atime':
            case 'mtime':
                $selector .= ":sort($sort_by $sort_flag)";
                break;
            case 'name':
                $selector = "user " . $selector;
                $selector .= ":sort(user.name_abbr $sort_flag)";
                break;
            case 'address':
                $selector = "user " . $selector;
                $selector .= ":sort(user.address_abbr $sort_flag)";
                break;
            case 'contact_info':
                $selector = "user " . $selector;
                $selector .= ":sort(user.phone $sort_flag)";
                break;
            case 'equipment':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.name_abbr $sort_flag)";
                break;
            case 'location':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.location_abbr $sort_flag)";
                break;
            case 'contact':
                $selector = "user equipment" . $selector;
                $selector .= ":sort(user.contact_abbr $sort_flag)";
                break;
            case 'control':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.control $sort_flag)";
                break;
            default:
                $selector .= ":sort(mtime $sort_flag)";
                break;
        }
        $trainings = Q($selector);

        // $form_token = Session::temp_token('training', 300);
        // $_SESSION[$form_token]['selector'] = $selector;

        //分页
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($trainings, $start, $per_page);

        $tabs->content = V('training/approved', [
            'trainings' => $trainings,
            'pagination' => $pagination,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            // 'form_token'=> $form_token,
        ]);
    }

    public static function incharge_profile_tab($e, $tabs)
    {
        if ($tabs->user->id == L('ME')->id
            && Q("{$tabs->user}<incharge equipment[require_training=1]")->total_count() && ( L('ME')->access('管理所有仪器的培训记录') || L('ME')->access('管理负责仪器的培训记录') )) {
            $tabs->add_tab('eq_incharge_training', [
                'url'=> $tabs->user->url('eq_incharge_training'),
                'title'=> I18N::T('equipments', '负责仪器培训 / 授权'),
                'weight'=> 100,
            ]);
        }
    }

    public static function incharge_profile_content($e, $tabs)
    {
        $user = $tabs->user;

        Event::bind('profile.incharge_training.view.tabs', 'Training::_incharge_profile_approved_tabs', 0, 'approved');
        Event::bind('profile.incharge_training.view.tabs', 'Training::_incharge_profile_applied_tabs', 0, 'applied');
        Event::bind('profile.incharge_training.view.tabs', 'Training::_incharge_profile_overdue_tabs', 0, 'overdue');

        $tabs->content = V('equipments:profile/content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.incharge_training.view.tabs')
            ->content_event('profile.incharge_training.view.content')
            ->select($params[2]);
    }

    public static function _incharge_profile_approved_tabs($e, $tabs)
    {
        Event::bind('profile.incharge_training.view.content', 'Training::_incharge_profile_approved_content', 0, 'approved');

        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_APPROVED;

        $tabs->add_tab('approved', [
            'url'=> $user->url('eq_incharge_training.approved'),
            'title'=> I18N::T('equipments', '已通过 (%count)', [
                '%count'=> Q("{$user}<incharge equipment ue_training[status={$status}]")->total_count(),
            ]),
        ]);
    }

    public static function _incharge_profile_approved_content($e, $tabs)
    {
        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_APPROVED;

        $form = Lab::form();
        $pre_selector = [];
        if ($form['user']) {
            $user_name = Q::quote($form['user']);
            $pre_selector[] = "user[name*=$user_name|name_abbr*=$user_name]";
        }
        if ($form['equipment']) {
            $equipment_name = Q::quote($form['equipment']);
            $pre_selector[] = "{$user}<incharge equipment[name*=$equipment_name|name_abbr*=$equipment_name]";
        } else {
            $pre_selector[] = "{$user}<incharge equipment";
        }
        $selector = "ue_training[status={$status}]:sort(mtime D)";
        if (count($pre_selector)) {
            $selector = "(" . join(", ", $pre_selector) . ") " . $selector;
        }
        $trainings = Q($selector);

        $form_token = Session::temp_token('training_incharge', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        $panel_buttons = [];
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '添加授权'),
            'extra' => 'q-object="incharge_add_approved_user" q-event="click" q-src="' . URI::url('!equipments/training') .
                    '" class="button button_add"'
        ];
        /*$panel_buttons[] = [
            'text' => I18N::T('equipments', '下载模板'),
            'extra' => 'class="button button_export"',
            'url' => URI::url('public', ['f' => '!equipments/template/import_approve.xlsx'])
        ];*/
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '打印'),
            'extra' => 'q-object="export_approved" q-event="click" q-src="' . URI::url('!equipments/training') .
                    '" q-static="' . H(['type'=>'print','form_token' => $form_token]) .
                    '" class="button button_print"'
        ];
        $panel_buttons[]  = [
            'text' => I18N::T('equipments', '导出Excel'),
            'extra' => 'q-object="export_approved" q-event="click" q-src="' . URI::url('!equipments/training') .
                    '" q-static="' . H(['type'=>'csv','form_token' => $form_token]) .
                    '" class="button button_save"'
        ];
        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($trainings, $start, $per_page);

        $tabs->content = V('equipments:profile/incharge_training/approved', [
            'user'=> $user,
            'trainings'=> $trainings,
            'pagination'=> $pagination,
            'form'=> $form,
            'panel_buttons'=> $panel_buttons,
        ]);
    }

    public static function _incharge_profile_applied_tabs($e, $tabs)
    {
        Event::bind('profile.incharge_training.view.content', 'Training::_incharge_profile_applied_content', 0, 'applied');

        $user = $tabs->user;
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN
        ]);

        $tabs->add_tab('applied', [
            'url'=> $user->url('eq_incharge_training.applied'),
            'title'=> I18N::T('equipments', '申请中 (%count)', [
                '%count'=> Q("{$user}<incharge equipment ue_training[status={$status}]")->total_count(),
            ]),
        ]);
    }

    public static function _incharge_profile_applied_content($e, $tabs) {
        $user = $tabs->user;
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN
        ]);

        $form = Lab::form();
        $pre_selector = [];
        if ($form['user']) {
            $user_name = Q::quote($form['user']);
            $pre_selector[] = "user[name*=$user_name|name_abbr*=$user_name]";
        }

        if ($form['equipment']) {
            $equipment_name = Q::quote($form['equipment']);
            $pre_selector[] = "{$user}<incharge equipment[name*=$equipment_name|name_abbr*=$equipment_name]";
        }
        else {
            $pre_selector[] = "{$user}<incharge equipment";
        }

        $selector = "ue_training[status={$status}]:sort(mtime D)";

        if (count($pre_selector)) {
            $selector = "(" . join(", ", $pre_selector) . ") " . $selector;
        }

        $trainings = Q($selector);

        $panel_buttons = [];
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '批量审批'),
            'extra' => 'q-object="batch_apply" q-event="click" q-src="' . URI::url('!equipments/training') .
                    '" class="button button_add"'
        ];
        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($trainings, $start, $per_page);
        $cache = Cache::factory();
        $ids = $cache->get("training_applied_ids") ? : [];

        $tabs->content = V('equipments:profile/incharge_training/applied', [
            'ids' => $ids,
            'user' => $user,
            'trainings' => $trainings,
            'pagination' => $pagination,
            'form' => $form,
            'panel_buttons' => $panel_buttons,
        ]);
    }

    public static function _incharge_profile_overdue_tabs($e, $tabs)
    {
        Event::bind('profile.incharge_training.view.content', 'Training::_incharge_profile_overdue_content', 0, 'overdue');

        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_OVERDUE;

        $tabs->add_tab('overdue', [
            'url'=> $user->url('eq_incharge_training.overdue'),
            'title'=> I18N::T('equipments', '已过期 (%count)', [
                '%count'=> Q("{$user}<incharge equipment ue_training[status={$status}]")->total_count(),
            ]),
        ]);
    }

    public static function _incharge_profile_overdue_content($e, $tabs) {
        $user = $tabs->user;
        $status = implode(',', [
            UE_Training_Model::STATUS_OVERDUE,
        ]);

        $form = Lab::form();
        $pre_selector = [];
        if ($form['user']) {
            $user_name = Q::quote($form['user']);
            $pre_selector[] = "user[name*=$user_name|name_abbr*=$user_name]";
        }

        if ($form['equipment']) {
            $equipment_name = Q::quote($form['equipment']);
            $pre_selector[] = "{$user}<incharge equipment[name*=$equipment_name|name_abbr*=$equipment_name]";
        } else {
            $pre_selector[] = "{$user}<incharge equipment";
        }

        $selector = "ue_training[status={$status}]:sort(mtime D)";

        if (count($pre_selector)) {
            $selector = "(" . join(", ", $pre_selector) . ") " . $selector;
        }

        $trainings = Q($selector);

        $panel_buttons = [];
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '批量授权'),
            'extra' => 'q-object="batch_overdue" q-event="click" q-src="' . URI::url('!equipments/training') .
                '" class="button button_add"'
        ];
        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($trainings, $start, $per_page);
        $cache = Cache::factory();
        $ids = $cache->get("training_overdue_ids") ? : [];

        $tabs->content = V('equipments:profile/incharge_training/overdue', [
            'ids' => $ids,
            'user' => $user,
            'trainings' => $trainings,
            'pagination' => $pagination,
            'form' => $form,
            'panel_buttons' => $panel_buttons,
        ]);
    }

    public static function _index_training_applied($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN,
        ]);
        $equipment = $tabs->equipment;

        $selector = "ue_training[equipment={$equipment}][status=$status]";
        if($form['ctime_s']){
            $cs = strtotime(date('Y-m-d',$form['ctime_s']));
            $cs = Date::get_day_start($cs);
            $selector .= "[ctime>={$cs}]";
        }
        if($form['ctime_e']){
            $ce = strtotime(date('Y-m-d',$form['ctime_e']));
            $ce = Date::get_day_end($ce);
            $selector .= "[ctime<={$ce}]";
        }
        if($form['check_time_s']){
            $cs = strtotime(date('Y-m-d',$form['check_time_s']));
            $cs = Date::get_day_start($cs);
            $selector .= "[check_time>={$cs}]";
        }
        if($form['check_time_e']){
            $ce = strtotime(date('Y-m-d',$form['check_time_e']));
            $ce = Date::get_day_end($ce);
            $selector .= "[check_time<={$ce}]";
        }

        if ($form['approved_name']) {
            $approved_name = Q::quote(trim($form['approved_name']));
            $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
        }

        //通过培训, 通过时间逆序
        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'atime':
            case 'mtime':
                $selector .= ":sort($sort_by $sort_flag)";
                break;
            case 'name':
                $selector = "user " . $selector;
                $selector .= ":sort(user.name_abbr $sort_flag)";
                break;
            case 'address':
                $selector = "user " . $selector;
                $selector .= ":sort(user.address_abbr $sort_flag)";
                break;
            case 'contact_info':
                $selector = "user " . $selector;
                $selector .= ":sort(user.phone $sort_flag)";
                break;
            case 'equipment':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.name_abbr $sort_flag)";
                break;
            case 'location':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.location_abbr $sort_flag)";
                break;
            case 'contact':
                $selector = "user equipment" . $selector;
                $selector .= ":sort(user.contact_abbr $sort_flag)";
                break;
            case 'control':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.control $sort_flag)";
                break;
            default:
                $selector .= ":sort(mtime $sort_flag)";
                break;
        }
        $trainings = Q($selector);
        // $form_token = Session::temp_token('training', 300);
        // $_SESSION[$form_token]['selector'] = $selector;

        //分页
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);
        if ($start > 0) {
            $last = floor($trainings->total_count() / $per_page) * $per_page;
            if ($last == $trainings->total_count()) {
                $last = max(0, $last - $per_page);
            }
            if ($start > $last) {
                $start = $last;
            }
            $trainings = $trainings->limit($start, $per_page);
        } else {
            $trainings = $trainings->limit($per_page);
        }
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $trainings->total_count(),
        ]);
        $tabs->content = V('training/applied', [
            'pagination' => $pagination,
            'trainings' => $trainings,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            // 'form_token'=> $form_token,
        ]);
    }

    public static function _index_training_overdue($e, $tabs)
    {
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $status = UE_Training_Model::STATUS_OVERDUE;
        $equipment = $tabs->equipment;

        $selector = "ue_training[equipment={$equipment}][status=$status]";

        if ($form['approved_name']) {
            $approved_name = Q::quote(trim($form['approved_name']));
            $selector = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
        }

        if ($form['lab']) {
            $lab = O('lab', trim($form['lab']));
            //$pre_selectors[] = "$lab user";
            $pre_selectors[] = $form['approved_name']? "$lab" : "$lab user";
        }

        // atime不过期相当于期限最大，所以判断开始时间时也应关联到期时间为空的数据
        if ($form['atime_dtstart']) {
            $atime_start = Q::quote($form['atime_dtstart']);
            $selector .= "[atime >= $atime_start | !atime]";
        }

        if ($form['atime_dtend']) {
            $atime_end = Q::quote($form['atime_dtend']);
            $selector .= "[atime][atime <= $atime_end]";
        }

        if ($pre_selectors) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        //通过培训, 通过时间逆序
        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'atime':
            case 'mtime':
                $selector .= ":sort($sort_by $sort_flag)";
                break;
            case 'approved_name':
                $selector = "user " . $selector;
                $selector .= ":sort(user.name_abbr $sort_flag)";
                break;
            case 'address':
                $selector = "user " . $selector;
                $selector .= ":sort(user.address_abbr $sort_flag)";
                break;
            case 'contact_info':
                $selector = "user " . $selector;
                $selector .= ":sort(user.phone $sort_flag)";
                break;
            case 'equipment':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.name_abbr $sort_flag)";
                break;
            case 'location':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.location_abbr $sort_flag)";
                break;
            case 'contact':
                $selector = "user equipment" . $selector;
                $selector .= ":sort(user.contact_abbr $sort_flag)";
                break;
            case 'control':
                $selector = "equipment" . $selector;
                $selector .= ":sort(equipment.control $sort_flag)";
                break;
            default:
                $selector .= ":sort(mtime $sort_flag)";
                break;
        }

        $trainings = Q($selector);
        $form_token = Session::temp_token('training', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        //分页
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);
        if ($start > 0) {
            $last = floor($trainings->total_count() / $per_page) * $per_page;
            if ($last == $trainings->total_count()) {
                $last = max(0, $last - $per_page);
            }
            if ($start > $last) {
                $start = $last;
            }
            $trainings = $trainings->limit($start, $per_page);
        } else {
            $trainings = $trainings->limit($per_page);
        }
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $trainings->total_count(),
        ]);

        $tabs->content = V('training/overdue', [
            'pagination' => $pagination,
            'trainings' => $trainings,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            'form_token' => $form_token,
        ]);
    }

    public static function _index_training_group($e, $tabs)
    {
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $equipment = $tabs->equipment;
        $selector = "ge_training[equipment={$equipment}]";
        $sort_by = $form['sort'] ?: 'id';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $sort_user = " ";
        switch ($sort_by) {
            case 'user':
                $sort_user = ":sort(name_abbr $sort_flag) ";
                break;
            case 'ntotal':
                $selector .= ":sort(ntotal $sort_flag)";
                break;
            case 'address':
                $selector .= ":sort(address_abbr $sort_flag)";
                break;
            case 'napproved':
                $selector .= ":sort(napproved $sort_flag)";
                break;
            case 'date':
                $selector .= ":sort(date $sort_flag)";
                break;
            default:
                $selector .= ":sort(id $sort_flag)";
                break;
        }

        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $selector = "user[name*={$name}|name_abbr^={$name}]" . $sort_user . $selector;
        } else {
            $selector = "user" . $sort_user . $selector;
        }

        if ($form['ctime_start']) {
            $ctime_start = Q::quote($form['ctime_start']);
            $selector .= "[date>={$ctime_start}]";
        }

        if ($form['ctime_end']) {
            $ctime_end = Q::quote($form['ctime_end']);
            $selector .= "[date<={$ctime_end}]";
        }

        $form_token = Session::temp_token('training', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        $ge_trainings = Q($selector);

        //分页查找
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);
        if ($start > 0) {
            $last = floor($ge_trainings->total_count() / $per_page) * $per_page;
            if ($last == $ge_trainings->total_count()) {
                $last = max(0, $last - $per_page);
            }
            if ($start > $last) {
                $start = $last;
            }
            $ge_trainings = $ge_trainings->limit($start, $per_page);
        } else {
            $ge_trainings = $ge_trainings->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $ge_trainings->total_count(),
        ]);

        $tabs->content = V('training/group/view', [
            'ge_trainings' => $ge_trainings,
            'pagination' => $pagination,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'equipment' => $equipment,
            'form' => $form,
            'form_token' => $form_token,
        ]);
    }

    public static function _tool_box_training_with_user_profile($e, $tabs)
    {
        $training = array_pop(explode('.', _U())); // approved applied overdue training
        if (!in_array($training, ['eq_training', 'approved', 'applied', 'overdue'])) {
            $training = 'eq_training';
        }
        $sort_fields = Config::get('equipments.training.sortable_columns');
        $panel_buttons = [];
        switch ($training) {
            case 'eq_training':
            case 'approved':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                if ($form['mtime_dtstart'] || $form['mtime_dtend']) {
                    $form['mtime_date'] = true;
                }

                if ($form['atime_dtstart'] || $form['atime_dtend']) {
                    $form['atime_date'] = true;
                }

                $field = [
                    // '@'=> NULL,
                    'name' => [
                        'weight' => 20,
                        'title' => I18N::T('equipments', '仪器名称'),
                        'sortable' => in_array('equipment', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/name', [
                                // 'name'=> 'name',
                                'value' => $form['name'],
                            ]),
                            'value' => H($form['name']),
                        ],
                    ],
                    'control' => [
                        'weight' => 30,
                        'title' => I18N::T('equipments', '控制'),
                        'sortable' => in_array('control', $sort_fields),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'location' => [
                        'weight' => 40,
                        'title' => I18N::T('equipments', '放置房间'),
                        'sortable' => in_array('location', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                    ],
                    'mtime' => [
                        'weight' => 70,
                        'title' => I18N::T('equipments', '通过时间'),
                        'sortable' => in_array('mtime', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/mtime', [
                                'form' => $form,
                            ]),
                            'value' => $form['mtime_date'] ? H($form['mtime_date']) : null,
                            'field' => 'mtime_dtstart,mtime_dtend',
                        ],
                    ],
                    'atime' => [
                        'weight' => 80,
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'align' => 'right',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/atime', [
                                'form' => $form,
                            ]),
                            'value' => $form['atime_date'] ? H($form['atime_date']) : null,
                            'field' => 'atime_dtstart,atime_dtend',
                        ],
                        'extra_class' => '',
                    ],
                    'contact' => [
                        'weight' => 60,
                        'title' => I18N::T('equipments', '联系人'),
                        'sortable' => in_array('contact', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/contact', [
                                'name' => 'contact',
                                'value' => $form['contact'],
                            ]),
                            'value' => $form['contact'] ? H($form['contact']) : null,
                        ],
                        'align' => 'left',
                        'nowrap' => true,
                    ],
                ];

                break;
            case 'applied':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                if ($form['ctime_dtstart'] || $form['ctime_dtend']) {
                    $form['ctime_date'] = true;
                }

                $field = [
                    'name' => [
                        'weight' => 20,
                        'title' => I18N::T('equipments', '仪器名称'),
                        'align' => 'left',
                        'sortable' => in_array('equipment', $sort_fields),
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/name', [
                                // 'name'=> 'name',
                                'value' => $form['name'],
                            ]),
                            'value' => H($form['name']),
                        ],
                    ],
                    'control' => [
                        'weight' => 30,
                        'sortable' => in_array('control', $sort_fields),
                        'title' => I18N::T('equipments', '控制'),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'location' => [
                        'weight' => 40,
                        'sortable' => in_array('location', $sort_fields),
                        'title' => I18N::T('equipments', '放置房间'),
                        'align' => 'left',
                        'nowrap' => true,
                    ],
                    'ctime' => [
                        'weight' => 80,
                        'title' => I18N::T('equipments', '申请时间'),
                        'sortable' => in_array('ctime', $sort_fields),
                        'align' => 'right',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/ctime', [
                                'form' => $form,
                            ]),
                            'value' => $form['ctime_date'] ? H($form['ctime_date']) : null,
                            'field' => 'ctime_dtstart,ctime_dtend',
                        ],
                        'extra_class' => '',
                    ],
                    'contact' => [
                        'weight' => 60,
                        'title' => I18N::T('equipments', '联系人'),
                        'sortable' => in_array('contact', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/contact', [
                                'name' => 'contact',
                                'value' => $form['contact'],
                            ]),
                            'value' => H($form['contact']),
                        ],
                    ],
                ];
                break;
            case 'overdue':
                $form = Lab::form(function (&$old_form, &$form) {
                });

                if ($form['mtime_dtstart'] || $form['mtime_dtend']) {
                    $form['mtime_date'] = true;
                }

                if ($form['atime_dtstart'] || $form['atime_dtend']) {
                    $form['atime_date'] = true;
                }

                $field = [
                    'name' => [
                        'weight' => 20,
                        'title' => I18N::T('equipments', '仪器名称'),
                        'sortable' => in_array('equipment', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/name', [
                                // 'name'=> 'name',
                                'value' => $form['name'],
                            ]),
                            'value' => H($form['name']),
                        ],
                    ],
                    'control' => [
                        'weight' => 30,
                        'sortable' => in_array('control', $sort_fields),
                        'title' => I18N::T('equipments', '控制'),
                        'align' => 'center',
                        'nowrap' => true,
                    ],
                    'location' => [
                        'weight' => 40,
                        'sortable' => in_array('location', $sort_fields),
                        'title' => I18N::T('equipments', '放置房间'),
                        'align' => 'left',
                        'nowrap' => true,
                    ],
                    'atime' => [
                        'weight' => 70,
                        'title' => I18N::T('equipments', '过期时间'),
                        'sortable' => in_array('atime', $sort_fields),
                        'align' => 'left',
                        'nowrap' => true,
                        'filter' => [
                            'form' => V('equipments:profile/training_tables/filter/atime', [
                                'form' => $form,
                            ]),
                            'value' => $form['atime_date'] ? H($form['atime_date']) : null,
                            'field' => 'atime_dtstart_check,atime_dtend_check,atime_dtstart,atime_dtend',
                        ],
                        'extra_class' => '',
                    ],
                    'contact' => [
                        'weight' => 60,
                        'title' => I18N::T('equipments', '联系人'),
                        'sortable' => in_array('contact', $sort_fields),
                        'filter' => [
                            'form' => V('equipments:equipments_table/filters/contact', [
                                'name' => 'contact',
                                'value' => $form['contact'],
                            ]),
                            'value' => $form['contact'] ? H($form['contact']) : null,
                        ],
                        'align' => 'left',
                        'nowrap' => true,
                    ],
                    'rest' => [
                        'title' => I18N::T('equipments', '操作'),
                        'weight' => 90,
                        'nowrap' => true,
                        'extra_class' => '',
                        'align' => 'left',
                    ],
                ];
                break;
            default:
                $status = '';
                break;
        }

        $columns = new ArrayIterator($field);
        Event::trigger('training.table_list.columns', $form, $columns);

        $training = $training == 'training' ? 'approved' : $training;

        $form_token = Session::temp_token('trainsing', 300);
        $_SESSION[$form_token]['selector'] = $selector;

        $me = L('ME');

        $tabs->search_box = V('application:search_box', ['top_input_arr' => ['name'], 'columns' => $columns]);
        // $tabs->panel_buttons=V('application:panel_buttons', ['panel_buttons'=>$panel_buttons]);
        $tabs->columns = $columns;
    }

    public static function exam_profile_tab($e, $tabs)
    {
        $tabs->add_tab('eq_exam', [
            'url'=> $tabs->user->url('eq_exam'),
            'title'=> I18N::T('equipments', '考试记录'),
            'weight'=> 100,
        ]);
        Event::trigger('unset.profile.view.tab', $tabs);
    }

    public static function exam_profile_content($e, $tabs)
    {
        $user = $tabs->user;
        $form = Form::filter(Input::form());
        $limit = 20;
        $exams = [];
        $total = 0;
        $pagination = Widget::factory('pagination');
        $result = (new HiExam())->get("user/{$user->gapper_id}/exam/list",
                    ['currentPage'=> (int)($form['st']/$limit),'pageSize'=>$limit, 'passed' => 1]);
        $response = (new HiExam())->get("user/{$user->gapper_id}/exam/count");
        if (isset($response['count'])) {
            $total = $response['count'];
        }
        $remote_exam_app = Config::get('exam.remote_exam_app');
        if (isset($result['list'])) {
            $list = $result['list'];

            foreach ($list as $li) {
                $eqs = [];
                $exam_id = $li['id'];
                $exam = O('exam', ['remote_id'=>$exam_id, 'remote_app'=>$remote_exam_app]);
                if ($exam->id) {
                    $equipments = Q("{$exam} equipment");
                    $eqs = $equipments->to_assoc('id', 'name');
                }
                $exams[] = [
                    'name' => $li['title'],
                    'finish_time' => $li['finish_time']?:'--',
                    'status' => $li['status'],
                    'equipments' => $eqs, // todo
                ];
            }
        }
        $pagination->set([
            'start' => $form['st'],
            'per_page' => $limit,
            'total' => $total,
        ]);
        $tabs->content = V('equipments:training/exam/content', [
            'pagination' => $pagination,
            'records' => $exams
        ]);

    }

    public static function user_profile_tab($e, $tabs)
    {
        $tabs->add_tab('eq_training', [
            'url' => $tabs->user->url('eq_training'),
            'title' => I18N::T('equipments', '仪器培训 / 授权'),
            'weight' => 100,
        ]);
        Event::trigger('unset.profile.view.tab', $tabs);
    }

    public static function get_list_by_user($tabs)
    {

        Event::bind('profile.training.view.tabs', 'Training::_user_profile_approved_tabs', 0, 'approved');
        Event::bind('profile.training.view.tabs', 'Training::_user_profile_applied_tabs', 0, 'applied');
        Event::bind('profile.training.view.tabs', 'Training::_user_profile_overdue_tabs', 0, 'overdue');

        $params = Config::get('system.controller_params');
        $tab = $params[0];

        $tabs
            ->set('base_url', URI::url('!equipments/training/me'))
            ->tab_event('profile.training.view.tabs')
            ->content_event('profile.training.view.content')
            ->select($tab);

        self::_tool_box_training_with_user_profile(null, $tabs);
    }

    public static function user_profile_content($e, $tabs)
    {
        $user = $tabs->user;

        Event::bind('profile.training.view.tabs', 'Training::_user_profile_approved_tabs', 0, 'approved');
        Event::bind('profile.training.view.tabs', 'Training::_user_profile_applied_tabs', 0, 'applied');
        Event::bind('profile.training.view.tabs', 'Training::_user_profile_overdue_tabs', 0, 'overdue');

        $tabs->content = V('equipments:profile/content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.training.view.tabs')
            ->content_event('profile.training.view.content')
            ->select($params[2]);

        self::_tool_box_training_with_user_profile(null, $tabs->content->secondary_tabs);
    }

    public static function _user_profile_approved_tabs($e, $tabs)
    {
        Event::bind('profile.training.view.content', 'Training::_user_profile_approved_content', 0, 'approved');

        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_APPROVED;

        $site_filter = '';
        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $site_filter = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if (Lab::form()['site']) {
                    $site_filter = "[site=" . Lab::form()['site'] . "]";
                }
            }
        }

        $tabs->add_tab('approved', [
            'url' => $tabs->base_url ? "$tabs->base_url.approved" : $user->url('eq_training.approved'),
            'title' => I18N::T('equipments', '已通过 (%count)', [
                '%count' => Q("ue_training[user={$user}][status={$status}] equipment")->total_count(),
            ]),
        ]);
    }

    public static function _user_profile_approved_content($e, $tabs)
    {
        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_APPROVED;

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $eq_where[] = "[name*={$name}|name_abbr*={$name}]";
        }

        //过期时间
        if ($form['atime_dtstart']) {
            $atime_dtstart = Date::get_day_start(Q::quote($form['atime_dtstart']));
            $ue_where[] = "[atime>{$atime_dtstart}|!atime]";
        }

        if ($form['atime_dtend']) {
            $atime_dtend = Date::get_day_end(Q::quote($form['atime_dtend']));
            $ue_where[] = "[atime<{$atime_dtend}][atime]";
        }

        if ($form['mtime_dtstart']) {
            $mtime_dtstart = Date::get_day_start(Q::quote($form['mtime_dtstart']));
            $ue_where[] = "[mtime>{$mtime_dtstart}]";
        }

        if ($form['mtime_dtend']) {
            $mtime_dtend = Date::get_day_end(Q::quote($form['mtime_dtend']));
            $ue_where[] = "[mtime<{$mtime_dtend}]";
        }

        if ($form['contact']) {
            $contact = Q::quote(trim($form['contact']));
            $pre_selector[] = "user<contact[name*={$contact}|name_abbr*={$contact}]";
        }

        $pre_selector[] = "ue_training[user={$user}][status={$status}]%ue_where";

        switch ($sort_by) {
            case 'equipment':
                $eq_where['sort'] = ":sort(name_abbr $sort_flag)";
                break;
            case 'location':
                $eq_where['sort'] = ":sort(location_abbr $sort_flag)";
                break;
            case 'contact':
                $eq_where['sort'] = ":sort(contacts_abbr $sort_flag)";
                break;
            case 'control':
                $eq_where['sort'] = ":sort(control_mode $sort_flag)";
                break;
            case 'atime':
            case 'mtime':
            case 'ctime':
                $pre_selector[] = array_pop($pre_selector) . ":sort($sort_by $sort_flag)";
                break;
            default:
                $pre_selector[] = array_pop($pre_selector) . ":sort(mtime $sort_flag)";
                break;
        }

        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $eq_where[] = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if ($form['site']) {
                    $eq_where[] = "[site={$form['site']}]";
                }
            }
        }

        $selector = strtr('('. implode(', ', $pre_selector). ") equipment%eq_where", [
            '%eq_where'=> join('', (array)$eq_where),
            '%ue_where'=> join('', (array)$ue_where),
        ]);

        $equipments = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($equipments, $start, $per_page);

        $tabs->content = V('equipments:profile/training/approved', [
            'user' => $user,
            'equipments' => $equipments,
            'pagination' => $pagination,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
        ]);
    }

    public static function _user_profile_applied_tabs($e, $tabs)
    {
        Event::bind('profile.training.view.content', 'Training::_user_profile_applied_content', 0, 'applied');

        $user = $tabs->user;
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN,
        ]);

        $site_filter = '';
        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $site_filter = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if (Lab::form()['site']) {
                    $site_filter = "[site=" . Lab::form()['site'] . "]";
                }
            }
        }

        $tabs->add_tab('applied', [
            'url'=> $tabs->base_url ? "$tabs->base_url.applied" : $user->url('eq_training.applied'),
            'title'=> I18N::T('equipments', '申请中 (%count)', [
                '%count'=> Q("ue_training[user={$user}][status={$status}] equipment{$site_filter}")->total_count(),
            ]),
        ]);
    }

    public static function _user_profile_applied_content($e, $tabs)
    {
        $user = $tabs->user;
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN,
        ]);

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $eq_where[] = "[name*={$name}|name_abbr*={$name}]";
        }

        //申请时间
        if ($form['ctime_dtstart']) {
            $ctime_dtstart = Date::get_day_start(Q::quote($form['ctime_dtstart']));
            $ue_where[] = "[ctime>{$ctime_dtstart}]";
        }

        if ($form['ctime_dtend']) {
            $ctime_dtend = Date::get_day_end(Q::quote($form['ctime_dtend']));
            $ue_where[] = "[ctime<{$ctime_dtend}]";
        }

        //过期时间
        if ($form['atime_dtstart']) {
            $atime_dtstart = Date::get_day_start(Q::quote($form['atime_dtstart']));
            $ue_where[] = "[atime>{$atime_dtstart}|!atime]";
        }

        if ($form['atime_dtend']) {
            $atime_dtend = Date::get_day_end(Q::quote($form['atime_dtend']));
            $ue_where[] = "[atime<{$atime_dtend}][atime]";
        }

        if ($form['contact']) {
            $contact = Q::quote(trim($form['contact']));
            $pre_selector[] = "user<contact[name*={$contact}|name_abbr*={$contact}]";
        }

        $pre_selector[] = "ue_training[user={$user}][status={$status}]%ue_where";

        switch ($sort_by) {
            case 'equipment':
                $eq_where['sort'] = ":sort(name_abbr $sort_flag)";
                break;
            case 'location':
                $eq_where['sort'] = ":sort(location_abbr $sort_flag)";
                break;
            case 'contact':
                $eq_where['sort'] = ":sort(contacts_abbr $sort_flag)";
                break;
            case 'control':
                $eq_where['sort'] = ":sort(control_mode $sort_flag)";
                break;
            case 'atime':
            case 'mtime':
            case 'ctime':
                $pre_selector[] = array_pop($pre_selector) . ":sort($sort_by $sort_flag)";
                break;
            default:
                $pre_selector[] = array_pop($pre_selector) . ":sort(mtime $sort_flag)";
                break;
        }

        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $eq_where[] = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if (Lab::form()['site']) {
                    $eq_where[] = "[site=" . Lab::form()['site'] . "]";
                }
            }
        }

        $selector = strtr('('. implode(', ', $pre_selector). ") equipment%eq_where", [
            '%eq_where'=> join('', (array)$eq_where),
            '%ue_where'=> join('', (array)$ue_where),
        ]);

        $equipments = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($equipments, $start, $per_page);

        $tabs->content = V('equipments:profile/training/applied', [
            'user' => $user,
            'equipments' => $equipments,
            'pagination' => $pagination,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
        ]);
    }

    public static function _user_profile_overdue_tabs($e, $tabs)
    {
        Event::bind('profile.training.view.content', 'Training::_user_profile_overdue_content', 0, 'overdue');

        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_OVERDUE;

        $site_filter = '';
        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $site_filter = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if (Lab::form()['site']) {
                    $site_filter = "[site=" . Lab::form()['site'] . "]";
                }
            }
        }

        $tabs->add_tab('overdue', [
            'url'=> $tabs->base_url ? "$tabs->base_url.overdue" : $user->url('eq_training.overdue'),
            'title'=> I18N::T('equipments', '已过期 (%count)', [
                '%count'=> Q("ue_training[user={$user}][status={$status}] equipment{$site_filter}")->total_count(),
            ]),
        ]);
    }

    public static function _user_profile_overdue_content($e, $tabs)
    {
        $user = $tabs->user;
        $status = UE_Training_Model::STATUS_OVERDUE;

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $sort_by = $form['sort'] ?: 'mtime';
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $eq_where[] = "[name*={$name}|name_abbr*={$name}]";
        }

        if ($form['lab']) {
            $lab = O('lab', $form['lab']);
            $pre_selector[] = "$lab user";
        }

        //申请时间
        if ($form['ctime_dtstart']) {
            $ctime_dtstart = Date::get_day_start(Q::quote($form['ctime_dtstart']));
            $ue_where[] = "[ctime>{$ctime_dtstart}]";
        }

        if ($form['ctime_dtend']) {
            $ctime_dtend = Date::get_day_end(Q::quote($form['ctime_dtend']));
            $ue_where[] = "[ctime<{$ctime_dtend}]";
        }

        //过期时间
        if ($form['atime_dtstart']) {
            $atime_dtstart = Date::get_day_start(Q::quote($form['atime_dtstart']));
            $ue_where[] = "[atime>{$atime_dtstart}|!atime]";
        }

        if ($form['atime_dtend']) {
            $atime_dtend = Date::get_day_end(Q::quote($form['atime_dtend']));
            $ue_where[] = "[atime<{$atime_dtend}][atime]";
        }

        if ($form['contact']) {
            $contact = Q::quote(trim($form['contact']));
            $pre_selector[] = "user<contact[name*={$contact}|name_abbr*={$contact}]";
        }

        $pre_selector[] = "ue_training[user={$user}][status={$status}]%ue_where";

        switch ($sort_by) {
            case 'equipment':
                $eq_where['sort'] = ":sort(name_abbr $sort_flag)";
                break;
            case 'location':
                $eq_where['sort'] = ":sort(location_abbr $sort_flag)";
                break;
            case 'contact':
                $eq_where['sort'] = ":sort(contacts_abbr $sort_flag)";
                break;
            case 'control':
                $eq_where['sort'] = ":sort(control_mode $sort_flag)";
                break;
            case 'atime':
            case 'mtime':
            case 'ctime':
                $pre_selector[] = array_pop($pre_selector) . ":sort($sort_by $sort_flag)";
                break;
            default:
                $pre_selector[] = array_pop($pre_selector) . ":sort(mtime $sort_flag)";
                break;
        }

        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('equipment')) {
            if (DB_SYNC::is_slave()) {
                $eq_where[] = "[site=" . LAB_ID . "]";
            } elseif (DB_SYNC::is_master()) {
                if ($form['site']) {
                    $eq_where[] = "[site={$form['site']}]";
                }
            }
        }

        $selector = strtr('('. implode(', ', $pre_selector). ") equipment%eq_where", [
            '%eq_where'=> join('', (array)$eq_where),
            '%ue_where'=> join('', (array)$ue_where),
        ]);

        $equipments = Q($selector);

        $start = (int) Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($equipments, $start, $per_page);

        $tabs->content = V('equipments:profile/training/overdue', [
            'user' => $user,
            'equipments' => $equipments,
            'pagination' => $pagination,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
        ]);
    }

    public static function eq_training_pending_count($e, $user)
    {
        if (!$user->id) {
            return;
        }

        $training_status_applied = UE_Training_Model::STATUS_APPLIED;
        $training_status_again = UE_Training_Model::STATUS_AGAIN;
        $training = Q("$user<incharge equipment ue_training[status=$training_status_applied|status=$training_status_again]")->total_count();
        $e->return_value = $training;
    }

    public static function eq_job_pending_count($e, $user)
    {
        if (!$user->id) {
            return;
        }

        $training_status_approved = UE_Training_Model::STATUS_APPROVED;
        $job = Q("ue_training[user=$user][status=$training_status_approved]")->total_count();
        $e->return_value = $job;
    }

    public static function equipment_training_list_columns($e, $field, $equipment) {
        $training = array_pop(explode('.', _U()));
        $form = Form::filter(Input::form());
        switch ($training) {
            case 'applied':
            default:
                if ($equipment->control_mode == 'bluetooth') {
                    $field['check_time'] = [
                        'title'=>I18N::T('equipments', '签到时间'),
                        'filter'=>[
                            'form'=>V('equipments:training_table/filters/check_time', ['form'=>$form]),
                            'field'=>'check_time_s,check_time_e',
                            'value' => $form['check_time'] ? H($form['check_time']) : null
                        ],
                        'nowrap'=>true,
                        'weight' => 31
                    ];
                }
                break;
        }
    }

    public static function equipment_training_list_row_applied($e, $row, $training) {
        $equipment = $training->equipment;
        if ($equipment->control_mode == 'bluetooth') {
            $row['check_time'] = V('equipments:training_table/data/check_time', ['training' => $training]);
        }
    }

    static function notification_extra_display ($e, $notification_keys) {
        if (!Config::get('training.training_period')) {
            foreach ($notification_keys as $k => $v) {
                if ($v == 'equipments.training.deleted.period' || 
                    $v == 'equipments.training_before_delete') {
                    unset($notification_keys[$k]);
                }
            }
        }

        $e->return_value = $notification_keys;

        return TRUE;
    }

    static function on_training_delete ($e, $ue) {
        if (Config::get('training.training_period')) {
            $user = $ue->user;
            $equipment = $ue->equipment;

            //过期时间
            $expire_time = $equipment->training_expire_time;

            $incharges = [];

            foreach(Q("{$equipment} user.incharge") as $i) {
                $incharges[] = Markup::encode_Q($i);
            }

            $strtr = [
                '%incharge' => join(', ', $incharges),
                '%user'=>Markup::encode_Q($user),
                '%equipment'=>Markup::encode_Q($equipment),
            ];

            if ($expire_time  //设定过期时间
                &&
                    ($ue->atime > Date::time()
                        ||
                    !$ue->atime
                    ) //未过期
                &&
                max(Q("eq_record[user={$user}][equipment={$equipment}][dtend>0]:sort(dtend D):limit(1)")->current()->dtend, $ue->ctime) + $expire_time < Date::time() //长时间没使用
            ) {
                $notification_template = 'equipments.training.deleted.period';

                list($day, $format) = Date::format_interval($expire_time);

                $format_text = ($format == 'm' ? '个' :  NULL). Date::unit($format);

                $strtr['%day'] = strtr('%day %format', [
                    '%day'=> $day,
                    '%format'=> $format_text,
                ]);
            }
            else {
                $notification_template = 'equipments.training_deleted';
            }

            Notification::send($notification_template, $user, $strtr);
        }

    }

    static function training_edit_use_view ($e, $equipment) {
        if (Config::get('training.training_period')) {
            $e->return_value .= V('equipments:training/edit_use_view', ['equipment' => $equipment]);
        }
        return TRUE;
    }

    static function training_edit_use_submit ($e, $equipment, $form) {
        if (Config::get('training.training_period')) {
            //勾选了需要培训后进行逻辑判断
            if ($form['require_training']) {

                try {
                    $time = (float) $form['training_expire_time'];

                    if ($time < 0) throw new Error_Exception;

                    $format = $form['training_expire_format'];

                    if (!in_array($format, ['m', 'd'])) throw new Error_Exception;

                    $time = Date::convert_interval($time, $format);

                }
                catch(Error_Exception $e) {
                    //按产品经理要求, 此处时间进行容错
                    //当出现错误提交时, 重置为0
                    $time = 0;
                }

                //单位s
                $equipment->training_expire_time = $time;
            }
            else {
                //清空
                $equipment->training_expire_time = NULL;
            }
        }
        
        return TRUE;
    }

    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $me = L('ME');
        $equipment = $view->calendar->parent;
        $result = true;
        if (($me->access('为所有仪器添加预约'))
            || ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
            || ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
        ) {
            $check_list[] = [
                'title' => I18N::T('equipments', '培训申请'),
                'result' => true,
                'description' => ''
            ];
        } else {
            if (!$equipment->require_training) {
                $check_list[] = [
                    'title' => I18N::T('equipments', '培训申请'),
                    'result' => true,
                    'description' => ''
                ];
            } else {
                if (!$equipment->reserv_require_training) {
                    $check_list[] = [
                        'title' => I18N::T('equipments', '培训申请'),
                        'result' => true,
                        'description' => I18N::T('equipments', '请及时参加培训，培训未通过前将无法上机')
                    ];
                } else {
                    $training = O('ue_training', ['user' => $me, 'equipment' => $equipment]);
                    $applied_status = join(',', [UE_Training_Model::STATUS_APPLIED, UE_Training_Model::STATUS_AGAIN]);
                    $approved_status = join(',', [UE_Training_Model::STATUS_APPROVED]);
                    if (Q("ue_training[user={$me}][equipment={$equipment}][status={$approved_status}]")->total_count()) {
                        $result = true;
                        $description = '培训通过';
                    } elseif (Q("ue_training[user={$me}][equipment={$equipment}][status={$applied_status}]")->total_count()) {
                        $result = false;
                        $description = I18N::T('equipments', '培训尚未通过，请先联系仪器管理员通过仪器培训');
                    } else {
                        $result = false;
                        $description = I18N::T('equipments', '未提交培训申请, ');
                        $description .= '<a class="blue prevent_default" href="'.$training->url($equipment->id, null, null, 'apply').'">'.I18N::T('equipments', '点击申请').'</a>';
                    }
                    $check_list[] = [
                        'title' => I18N::T('equipments', '培训申请'),
                        'result' => $result,
                        'description' => $description
                    ];
                }
            }
        }
        $view->check_list = $check_list;
    }


    static function before_user_save_message($e, $user)
    {
        /*
            自定义的在用户删除时候提示的信息，可以进行修正。暂时定为只要该用户有相关的未删除的培训记录，则不可删除。
        */
        if (Q("ue_training[user={$user}][status!=".UE_Training_Model::STATUS_DELETED."]")->total_count()) {
            $e->return_value = I18N::T('equipments', '该用户关联了相应的培训记录!');
            return FALSE;
        }
    }
}
