<?php
class EQ_Charge
{
    public static function setup_index($e, $controller, $method, $params)
    {
        if ('charges' != $params[0] && 'charge' != $params[0]) {
            return;
        }

        $me = L('ME');

        if (!$me->id || !$me->is_active()) {
            URI::redirect('error/401');
        }

        if ('charges' == $params[0]) {
            if (!$me->access('查看所有仪器的使用收费情况') && !$me->access('查看下属机构仪器的使用收费情况')) {
                URI::redirect('error/401');
            }
            Event::bind('equipments.primary.tab', 'EQ_Charge::charges_tab');
            Event::bind('equipments.primary.content', 'EQ_Charge::charges_tab_content', 100, 'charges');
        }

        if ('charge' == $params[0]) {
            $length = Q("{$me}<incharge equipment")->total_count();
            if (!$length) {
                URI::redirect('error/401');
            }
            Event::bind('equipments.primary.tab', 'EQ_Charge::charge_primary_tab');
            Event::bind('equipments.primary.content', 'EQ_Charge::charge_primary_tab_content', 100, 'charge');
            // Event::bind('equipments.primary.tool_box', 'EQ_Charge::_tool_box_charge', 200, 'charge');
        }
    }

    public static function charge_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('charge', [
            'url'   => URI::url('!equipments/extra/charge'),
            'title' => I18N::T('eq_charge', '%name负责的所有仪器的收费情况', ['%name' => H($me->name)]),
        ]);
    }

    public static function charge_primary_tab_content($e, $tabs)
    {
        $me         = L('ME');
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_charge_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
            });


            $panel_buttons = [
                [
                'tip' => I18N::T('eq_charge', '导出Excel'),
                'text' => I18N::T('eq_charge', '导出'),
                'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') .
                '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                '" class="button button_save middle"',
                ],
                [
                'tip' => I18N::T('eq_charge', '打印'),
                'text' => I18N::T('eq_charge', '打印'),
                'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') .
                '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                '" class="button button_print middle"',
                ]
            ];

            $new_links = Event::trigger('eq_charge.charge_view.links', $panel_buttons, ['form_token' => $form_token]);
			if ($new_links) $panel_buttons = $new_links;

            $pre_selector = [];

            $pre_selector['user'] = "user";
            $pre_selector[base]   = " {$me} equipment.incharge ";

            $group = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');

            if($form['group_id'] && ($group->root->id == $group_root->id)) {
                $pre_selector['group'] = "{$group} equipment";
            }

            $tabs->content_type = "incharge";

            $selector = 'eq_charge[amount!=0]';

            if ($form[equipment]) {
                $equipment_id = Q::quote($form[equipment]);
                $selector .= "[equipment_id={$equipment_id}]";
            }

            if ($form[lab]) {
                $lab_id = Q::quote($form[lab]);
                $selector .= "[lab_id={$lab_id}]";
            }

            if ($form[user]) {
                $user_id = Q::quote($form[user]);
                $selector .= "[user_id={$user_id}]";
            }

            if (!empty($form['status'])) {
                $status = implode(',',$form['status']);
                $selector .= "[status={$status}]";
            }

            //按时间搜索
            if ($form[dtstart]) {
                $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
                $selector .= "[ctime>={$dtstart}]";
            }

            if ($form[dtend]) {
                $dtend = Q::quote(Date::get_day_end($form['dtend']));
                $selector .= "[ctime>0][ctime<={$dtend}]";
            }

            if ($form[charge_id]) {
                $id = Q::quote($form[charge_id]);
                if (Module::is_installed('billing')) {
                    $selector .= "[transaction_id={$id}]";
                } else {
                    $selector .= "[id={$id}]";
                }
            }
            $new_selector = Event::trigger('eq_charge.primary.content.selector', $form, $selector, $pre_selector) ?: $selector;
            if ($new_selector) {
                $selector = $new_selector;
            }
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;

            $sort_by   = $form['sort'] ?: 'ctime';
            $sort_asc  = $form['sort_asc'];
            $sort_flag = $sort_asc ? 'A' : 'D';

            switch ($sort_by) {
				case 'user':
					$selector .= ":sort(user.name_abbr $sort_flag) ";
					break;
				case 'type':
					$selector .= ":sort(source_name $sort_flag)";
					break;
				case 'equipment':
					$selector .= ":sort(equipment.name_abbr $sort_flag) ";
					break;
				case 'status':
					$selector .= ":sort(status $sort_flag)";
					break;
				case 'amount':
					$selector .= ":sort(amount $sort_flag)";
					break;
				default:
					$selector .= ":sort(ctime $sort_flag)";
					break;
			}

            $token             = [];
            $token['selector'] = $selector;
            $token['form']     = $form;
            $form['form_token']= $form_token;
            $_SESSION[$form_token] = $token;

            $charges    = Q($selector);
            $pagination = Lab::pagination($charges, (int) $form['st'], 20);

            $obj     = null;
            // $columns = self::get_field_lab($obj, $form);

            // $tabs->columns = $columns;

            // if (!$form['dtstart'] && !$form['dtend']) {
            //     $dtend_date      = getdate(time());
            //     $form['dtend']   = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            //     $form['dtstart'] = $form['dtend'] - 2592000;
            // }

            $tabs->content = V('eq_charge:view/extra_index_charges', [
                'charges'    => $charges,
                'pagination' => $pagination,
                'sort_by'    => $sort_by,
                'sort_asc'   => $sort_asc,
                'form'       => $form,
                'panel_buttons'     => $panel_buttons,
                'tabs'       => $tabs,
                'group' => $group,
                'group_root' => $group_root,
            ]);
        }
    }

    public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'EQ_Charge::charge_tab');
        Event::bind('lab.notifications.edit', 'EQ_Charge::edit_charge_content');
    }

    public static function setup_view()
    {
        Event::bind('equipment.index.tab', 'EQ_Charge::index_charge_tab');
        Event::bind('equipment.view.dashboard.sections', 'EQ_Charge::equipment_dashboard_sections');
        Event::bind('equipment.index.tab.tool_box', 'EQ_Charge::_tool_box_charge', 0, 'charge');
    }

    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'EQ_Charge::index_profile_tab');
        Event::bind('profile.view.content', 'EQ_Charge::index_profile_content', 0, 'eq_charge');
        Event::bind('profile.view.tool_box', 'EQ_Charge::_tool_box_charge', 0, 'eq_charge');
    }

    public static function setup_lab()
    {
        Event::bind('lab.view.tab', 'EQ_Charge::lab_view_tab', 0);
        Event::bind('lab.view.content', 'EQ_Charge::lab_view_content', 0, 'eq_charge');
        Event::bind('lab.view.tool_box', 'EQ_Charge::lab_view_tool', 0, 'eq_charge');
    }

    public static function charges_tab($e, $tabs)
    {
        $tabs->add_tab('charges', [
            'url'   => URI::url('!equipments/extra/charges'),
            'title' => I18N::T('eq_charge', '所有仪器的使用收费'),
        ]);
    }

    public static function charges_tab_content($e, $tabs)
    {
        self::charge_view_content($tabs);
    }

    public static function index_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if ($me->is_allowed_to('查看收费情况', $user)) {
            $tabs
                ->add_tab('eq_charge', [
                    'url'    => $tabs->user->url('eq_charge'),
                    'title'  => I18N::T('eq_charge', '仪器收费'), //用户
                    'weight' => 50,
                ]);
        }
    }

    public static function edit_charge_content($e, $lab, $sections)
    {
        if (Input::form('submit')) {
            $form                              = Form::filter(Input::form());
            $lab->charge_notification_required = (bool) $form['charge_notification_required'];
            if ($lab->charge_notification_required) {
                $form->validate('min_notification_fee', 'is_numeric');
                $lab->min_notification_fee = $form['min_notification_fee'];
            } else {
                $lab->min_notification_fee = 0;
            }

            if ($form->no_error) {
                if ($lab->save()) {
                    // 记录日志
                    Log::add(strtr('[eq_charge] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的消息提醒', [
                        '%user_name' => L('ME')->name,
                        '%user_id'   => L('ME')->id,
                        '%lab_name'  => $lab->name,
                        '%lab_id'    => $lab->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '更新成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '更新失败！'));
                }
            }
        }

        $sections[] = V('eq_charge:edit/charge_notification_required', [
            'lab'  => $lab,
            'form' => $form,
        ]);
    }

    public static function index_profile_content($e, $tabs)
    {
        $user = $tabs->user;
        self::charge_view_content($tabs, $user);
    }

    public static function lab_view_tab($e, $tabs)
    {
        $lab = $tabs->lab;
        $me  = L('ME');
        if ($me->is_allowed_to('查看收费情况', $lab)) {
            $tabs
                ->add_tab('eq_charge', [
                    'url'   => $lab->url('eq_charge'),
                    'title' => I18N::T('eq_charge', '仪器收费'), //实验室
                ]);
        }
    }

    public static function lab_view_content($e, $tabs)
    {
        $lab = $tabs->lab;
        self::charge_view_content($tabs, $lab);
    }

    public static function index_charge_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->id && $me->is_allowed_to('查看仪器收费记录', $equipment)) {
            Event::bind('equipment.index.tab.content', 'EQ_Charge::index_charge_tab_content', 0, 'charge');
            // Event::bind('equipment.index.tab.tool_box', 'EQ_Charge::_tool_box_equipment', 0, 'charge');
            $tabs->add_tab('charge', [
                'url'    => $equipment->url('charge'),
                'title'  => I18N::T('eq_charge', '使用收费'),
                'weight' => 50,
            ]);
        }
    }

    public static function index_charge_tab_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        self::charge_view_content($tabs, $equipment);
    }

    public static function _get_charge_selector(&$form)
    {
        $selector = 'eq_charge[amount!=0]';

        if ($form['equipment']) {
            $selector .= "[equipment_id={$form['equipment']}]";
        }

        if ($form['lab']) {
            $selector .= "[lab_id={$form['lab']}]";
        }

        if ($form['user']) {
            $selector .= "[user_id={$form['user']}]";
        }

        return $selector;
    }

    public static function _get_sample_selector(&$form)
    {
        $selector = 'eq_sample[amount!=0]';

        /* 按时间搜索 */

        if ($form['equipment']) {
            $id = $form['equipment'];
            $selector .= "[equipment_id=$id]";
        }

        if ($form['lab']) {
            $id = $form['lab'];
            $selector .= "[lab_id=$id]";
        }

        if ($form['user']) {
            $id = $form['user'];
            $selector .= "[sender_id=$id]";
        }
        return $selector;
    }

    public static function charge_view_content($tabs, $obj = null)
    {
        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['status'][0] == -1) {
                unset($form['status'][0]);
            }
        });

        //生成 session token， 为导出做准备
        $form_token       = Session::temp_token('charge_', 300);
        $tabs->form_token = $form_token;
        $params           = [
            'form_token' => $form_token,
            'oid'        => $obj->id,
        ];

        if ($obj->id) {
            $params['oname'] = $obj->name();
        }

        $links          = [];
        $links['excel'] = [
            'tip'   => I18N::T('eq_charge', '导出Excel'),
            'text' => I18N::T('eq_charge', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') .
            '" q-static="' . H(['type' => 'csv'] + $params) .
            '" class="button button_save "',
        ];
        $links['print'] = [
            'tip'   => I18N::T('eq_charge', '打印'),
            'text' => I18N::T('eq_charge', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') .
            '" q-static="' . H(['type' => 'print'] + $params) .
            '" class="button button_print  middle"',
        ];

        /* 容许的链接(不对低权限用户提供打印和导出) */
        $me        = L('ME');
        
        $columns   = self::get_charge_field($obj, $form);

        if ($obj->id && $obj->name() == 'equipment') {
            $form['equipment'] = $obj->id;
            $view_name = 'eq_charge:view/index_charges';
            //TODO 添加实验室管理员可以查看自己实验室成员的信息
            if (!($me->is_allowed_to('查看收费情况', $obj))) {
                $form['user'] = $me->id;
            }
        } elseif ($obj->id && $obj->name() == 'lab') {
            $columns = self::get_field_lab($obj, $form);

            $form['lab'] = $obj->id;
            $view_name   = 'eq_charge:view/lab_charges';
            if (!$me->is_allowed_to('查看收费情况', $obj)) {
                $links = [];
            }
        } elseif ($obj->id && $obj->name() == 'user') {
            $form['user'] = $obj->id;
            $view_name = 'eq_charge:view/index_charges';
            if (!$me->is_allowed_to('查看收费情况', $obj)) {
                $links = [];
            }
        }else{
            $view_name = 'eq_charge:view/extra_index_charges';
        }

        $selector = "eq_charge[amount!=0]";

        $time_sel = '';
        if ($form['dtstart']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
            $time_sel .= "[ctime>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtend']));
            $time_sel .= "[ctime<=$dtend]";
        }

        $pre_selector = '';
        $g_pre_selector = [];

        //按组织机构查看
        if($obj === null){
            if(!$me->access('查看下属机构仪器的使用收费情况')){
                $links = [];
            }
            //增加下属机构范围,selector有点傻，但是时间不允许
            if($me->access('查看所有仪器的使用收费情况')){
            }elseif($me->access('查看下属机构仪器的使用收费情况')){
                $g_pre_selector['me_group']= "{$me->group} equipment";
            }

            if ($tabs->group->id) {
               $g_pre_selector['me_group']= "{$tabs->group} equipment";
            }

            //GROUP搜索
            $group = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');

            if ($group->id && $group->root->id == $group_root->id) {
                $g_pre_selector['group'] = "$group equipment";
            } else {
                $group = null;
            }

            if ($form['site']) {
                $g_pre_selector['equipment'] = "equipment[site={$form['site']}]";
            }

        }

        if(!empty($g_pre_selector)){
            $selector = "( " . join(', ', $g_pre_selector) . " ) " . $selector;
        }

        if ($form['equipment']) {
            $pre_selector .= "[equipment_id={$form['equipment']}]";
        }

        if ($form['lab']) {
            $pre_selector .= "[lab_id={$form['lab']}]";
        }

        if ($form['user']) {
            $pre_selector .= "[user_id={$form['user']}]";
        }

        if ($form['status']) {
            if (in_array(5, $form['status'])) {
                $form['status'] += [6, 8];
            }

            if (in_array(4, $form['status'])) {
                $form['status'] += [6];
            }

            if (in_array(8, $form['status'])) {
                $form['status'] += [7];
            }

            $status = implode(',', $form['status']);
            $selector .= "[status={$status}]";
        }

        if ($form['charge_id']) {
            $id = Q::quote($form['charge_id']);
            if (Module::is_installed('billing')) {
                $pre_selector .= "[transaction=billing_transaction#{$id}]";
            } else {
                $selector .= "[id={$id}]";
            }
        }

        $selector .= $pre_selector . $time_sel;
        $selector = Event::trigger('eq_charge_all.primary.content.selector', $form, $selector, $pre_selector) ?: $selector;

        $sort_by   = $form['sort'] ?: 'ctime';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'user':
				$selector = "user:sort(name_abbr $sort_flag) " . $selector;
				break;
            case 'type':
                $selector .= ":sort(source_name $sort_flag)";
                break;
            case 'status':
                $selector .= ":sort(status $sort_flag)";
                break;
            case 'amount':
                $selector .= ":sort(amount $sort_flag)";
                break;
            case 'equipment':
                $selector = "equipment:sort(name_abbr $sort_flag) " . $selector;
                break;
            default:
                $selector .= ":sort(ctime $sort_flag)";
                break;
        }
        $token             = [];
        $token['selector'] = $selector;
        $token['form']     = $form;
        //将搜索条件存入session
        $_SESSION[$form_token] = $token;
        $charges = Q($selector);

        $start    = (int) $form['st'];
        $per_page = 25;

        $pagination = Lab::pagination($charges, $start, $per_page);

        $tabs->links   = $links;
        $tabs->columns = $columns;
        $tabs->obj     = $obj;

        /* 使用收费 */
        $tabs->content = V($view_name, [
            'charges'    => $charges,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'columns'    => $columns,
            'form'       => $form,
            'group_root' => $group_root,
            'group'      => $group,
            'links'      => $links,
            'panel_buttons'      => $links,
            'obj'        => $obj,
        ]);
    }

    public static function lab_view_tool($e, $tabs)
    {
        $form_token = $tabs->form_token;

        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '导出Excel'),
            'text' => I18N::T('equipments', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') . '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '打印'),
            'text' => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_charge/report') . '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];

        $extra_view = Event::trigger('charges.table_list.search_box.extra_view', $form, $tabs->lab, '');

        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons,
             'top_input_arr' => ['charge_id'], 'columns' => (array) $tabs->columns, 'extra_view'    => $extra_view]);

    }

    public static function get_field_lab($obj, $form)
    {
        $user        = O('user', $form['user']);
        $equipment   = O('equipment', $form['equipment']);
        $sort_fields = Config::get('eq_charge.sortable_columns');

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }

        $field = [];
        //增加下属机构范围,selector有点傻，但是时间不允许
        if ($GLOBALS['preload']['tag.group_limit'] >= 0 && $obj === null) {
            $field['group'] = [
                'title'=>I18N::T('equipments', '组织机构'),
                'align'=>'left',
                'suppressible' => TRUE,
                'invisible' => TRUE,
                'filter'=> [
                    'form' => V('equipments:equipments_table/filters/group', [
                        'name'=>'group_id', 'group'=>$group,
                        'root'=>$group_root,
                    ]),
                    'value' => ($group->id && $group->id != $group_root->id) ? V('application:tag/path', ['tag'=>$group, 'tag_root'=>$group_root, 'url_template'=>URI::url('', 'group_id=%tag_id')]) : NULL,
                    'field' => 'group_id'
                ],
                'nowrap'=>TRUE,
            ];
        }
        //end

        $field = [
            'charge_id' => [
                'title'       => I18N::T('eq_charge', '计费编号'),
                'filter'      => [
                    'form'  => V('eq_charge:charges_table/filters/id', ['form' => $form, 'tip' => '请输入计费编号']),
                    'value' => $form['charge_id'] ? Number::fill($form['charge_id']) : null,
                ],
                'extra_class' => 'blue nowrap',
            ],
            'user'      => [
                'title'    => I18N::T('eq_charge', '使用者'),
                'sortable' => in_array('user', $sort_fields),
                'filter'   => [
                    'form'  => V('eq_charge:charges_table/filters/user', ['user' => $user]),
                    'value' => $user->id ? H($user->name) : H($form['user'] ?: null),
                ],
                'nowrap'   => true,
            ],
            'equipment' => [
                'title'    => T('仪器'),
                'sortable' => in_array('equipment', $sort_fields),
                'filter'   => [
                    'form'  => V('eq_charge:charges_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $equipment->id ? H($equipment->name) : null,
                ],
                'nowrap'   => true,
            ],
            'date'      => [
                'title'     => I18N::T('eq_charge', '时间'),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_charge:charges_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap'    => true,
                'weight'    => 10,
            ],
            'type'      => [
                'title'    => I18N::T('eq_charge', '收费类型'),
                'sortable' => in_array('type', $sort_fields),
                'nowrap'   => true,
                'weight'   => 20,
            ],
            'amount'    => [
                'title'    => I18N::T('eq_charge', '收费'),
                'sortable' => in_array('type', $sort_fields),
                'nowrap'   => true,
                'weight'   => 30,
            ],
            'status'    => [
                'title'    => I18N::T('eq_charge', '状态'),
                'sortable' => in_array('status', $sort_fields),
                'filter'   => [
                    'form'   => V('eq_charge:charges_table/filters/status', [
                        'status' => $form['status'],
                    ]),
                    'value'  => $form['status'] ? (implode(', ', array_map(function ($k) {
                        return EQ_Reserv_Model::$reserv_status[$k] == '正常使用' ? '正常' : EQ_Reserv_Model::$reserv_status[$k];
                    }, array_keys($form['status'])))) : '',
                    'nowrap' => false,
                ],
                'nowrap'   => true,
                'weight'   => 40,
            ],
            'summary'   => [
                'title'  => I18N::T('eq_charge', '备注'),
                'nowrap'   => true,
                'weight' => 50,
            ],
            'rest'      => [
                'title'  => I18N::T('eq_charge', '操作'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 60,
            ],
        ];

        $columns = new ArrayObject($field);
        Event::trigger('lab_charges.table_list.columns', $form, $columns, $obj);

        return $columns;
    }

    public static function _tool_box_charge($e, $tabs)
    {
        $obj = $tabs->obj;

        if (L('ME')->is_allowed_to('查看收费情况', $obj ? $obj : 'equipment')) {
            $panel_buttons = $tabs->links;
        }

        $extra_view = Event::trigger('charges.table_list.search_box.extra_view', $form, $obj, $tabs->content_type);

        $tabs->search_box = V('application:search_box', [
            'panel_buttons' => $panel_buttons ?: [], 
            'top_input_arr' => ['charge_id'], 
            'columns'       => $tabs->columns,
            'extra_view'    => $extra_view
        ]);
    }

    // 获取使用收费的field
    public static function get_charge_field($obj, $form)
    {
        $me                 = L('ME');
        $sort_fields        = Config::get('eq_charge.sortable_columns');
        $field              = [];
        $field['charge_id'] = [
            'title'       => I18N::T('eq_charge', '计费编号'),
            'filter'      => [
                'form'  => V('eq_charge:charges_table/filters/id', ['value' => $form['charge_id'], 'tip' => '请输入计费编号']),
                'value' => $form['charge_id'] ? Number::fill($form['charge_id']) : null,
            ],
            'weight'      => 5,
            'extra_class' => 'blue nowrap',
        ];

        if (!$obj || $obj->name() != 'equipment') {
            $equipment          = O('equipment', $form['equipment']);
            $field['equipment'] = [
                'title'    => T('仪器'),
                'sortable' => in_array('equipment', $sort_fields),
                'filter'   => [
                    'form'  => V('eq_charge:charges_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $equipment->id ? H($equipment->name) : H($form['equipment'] ?: null),
                ],
                'nowrap'   => true,
                'weight'   => 10,
            ];
            /* $field['equipment_ref'] = [
                'title'    => T('仪器编号'),
                'sortable' => in_array('equipment', $sort_fields),
                'invisible' => true,
                'filter'   => [
                    'form'  => V('eq_charge:charges_table/filters/equipment_ref', ['equipment_ref' => $form['equipment_ref']]),
                    'value' => $equipment->id ? H($equipment->ref_no) : H($form['equipment_ref'] ?: null),
                ],
                'nowrap'   => true,
                'weight'   => 15,
            ]; */
        }else{
            $equipment = $obj;
        }

        if ($me->is_allowed_to('查看收费情况', $equipment->id ? $equipment : 'equipment')) {
            if (!$obj || $obj->name() != 'user') {
                $user          = O('user', $form['user']);
                $field['user'] = [
                    'title'    => I18N::T('eq_charge', '使用者'),
                    'sortable' => in_array('user', $sort_fields),
                    'filter'   => [
                        'form'  => V('eq_charge:charges_table/filters/user', ['user' => $user]),
                        'value' => $user->id ? H($user->name) : H($form['user'] ?: null),
                    ],
                    'nowrap'   => true,
                    'weight'   => 20,
                ];
            }

            if (!$obj || $obj->name() != 'lab') {
                $lab          = O('lab', $form['lab']);
                $field['lab'] = [
                    'title'     => I18N::T('eq_charge', '实验室'),
                    'invisible' => true,
                    'filter'    => [
                        'form'  => V('eq_charge:charges_table/filters/lab', ['lab' => $lab]),
                        'value' => $lab->id ? H($lab->name) : H($form['lab'] ?: null),
                    ],
                    'nowrap'    => true,
                    'weight'    => 20,
                ];
            }
        }

        /*if (Config::get('eq_charge.foul_charge')) {
        $field['status'] = [
        'title' => I18N::T('eq_charge', '状态'),
        'filter' => [
        'form' => V('eq_charge:charges_table/filters/status', [
        'status' => $form['status'],
        ]),
        'value' => $form['status'] ? (implode(', ', array_map(function ($k) {
        return EQ_Reserv_Model::$reserv_status[$k] == '正常使用' ? '正常' : EQ_Reserv_Model::$reserv_status[$k];
        }, $form['status']))) : '',
        'nowrap' => false,
        ],
        'nowrap' => true,
        'weight' => 40,
        ];
        }*/

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }

        $field += [
            'date'    => [
                'title'     => I18N::T('eq_charge', '时间'),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_charge:charges_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap'    => true,
            ],
            'amount'  => [
                'title'    => I18N::T('eq_charge', '收费'),
                'sortable' => in_array('amount', $sort_fields),
                'nowrap'   => true,
                'weight'   => 30,
            ],
            'type'    => [
                'title'    => I18N::T('eq_charge', '收费类型'),
                'sortable' => in_array('type', $sort_fields),
                'nowrap'   => true,
                'weight'   => 30,
            ],
            'status'  => [
                'title'    => I18N::T('eq_charge', '状态'),
                'sortable' => in_array('status', $sort_fields),
                'filter'   => [
                    'form'   => V('eq_charge:charges_table/filters/status', [
                        'status' => $form['status'],
                    ]),
                    'value'  => $form['status'] ? (implode(', ', array_map(function ($k) {
                        return EQ_Reserv_Model::$reserv_status[$k] == '正常使用' ? '正常' : EQ_Reserv_Model::$reserv_status[$k];
                    }, $form['status']))) : '',
                    'nowrap' => false,
                ],
                'nowrap'   => true,
                'weight'   => 40,
            ],
            'summary' => [
                'title'       => I18N::T('eq_charge', '备注'),
                'extra_class' => '',
                'nowrap'   => true,
                'weight'      => 50,
            ],
            'rest'    => [
                'title'  => I18N::T('eq_charge', '操作'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 60,
            ],
        ];

        $columns = new ArrayObject($field);
        Event::trigger('index_charges.table_list.columns', $form, $columns, $obj);

        return $columns;
    }

    public static function equipment_dashboard_sections($e, $equipment, $sections)
    {
        if ($equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE
            && L('ME')->is_allowed_to('显示仪器计费设置', $equipment)) {
            $views = new ArrayIterator;
            Event::trigger('eq_charge.view.dashboard.sections', $equipment, $views);

            $sections[] = V('eq_charge:view/section.equipment_charge_setting', [
                'equipment' => $equipment,
                'sections'  => $views,
            ]);
        }
    }

    public static function charge_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->is_allowed_to('查看计费设置', $equipment)) {
            $tabs
                ->add_tab('charge', [
                    'url'    => $equipment->url('charge', null, null, 'edit'),
                    'title'  => I18N::T('eq_charge', '计费设置'),
                    'weight' => 50,
                ]);
            Event::bind('equipment.edit.content', 'EQ_Charge::charge_content', 0, 'charge');
        }
    }

    //equipment 编辑计费
    public static function charge_content($e, $tabs)
    {
        $stab       = $tabs->stab;
        $equipment  = $tabs->equipment;
        $properties = Properties::factory($equipment);

        $content = V('eq_charge:edit/setup_charge');
        Event::bind('equipment.charge.edit.content', 'EQ_Charge::edit_charge_dashboard', 0, 'dashboard');

        $content->third_tabs
        = Widget::factory('tabs')
            ->set('class', 'fifth_tabs')
            ->set('equipment', $equipment)
            ->set('content', $content)
            ->tab_event('equipment.charge.edit.tab')
            ->content_event('equipment.charge.edit.content');

        $tabs->content = $content;

        Event::trigger('equipment.charge.edit.content.tabs', $tabs);

        $content->third_tabs
            ->add_tab('dashboard', [
                'url'    => $equipment->url('charge.dashboard', null, null, 'edit'),
                'title'  => I18N::T('eq_charge', '基本设置'),
                'weight' => 0,
            ]);

        // 进行判断，如果是不进行收费，则不显示对应的设置tab
        $charge_template = $equipment->charge_template;
        if ($charge_template) {
            foreach ($charge_template as $key => $value) {
                if ($key == 'reserv' && $equipment->accept_reserv && $value) {
                    $accept_reserv = true;
                    if ($value == 'time_reserv_record') {
                        $time_reserv_record = true;
                        $title = '综合计费';
                    } else {
                        $title = '预约计费';
                    }
                }
                if ($key == 'sample' && $equipment->accept_sample && $value) {
                    $accept_sample = true;
                }
                if ($key == 'record' && $value) {
                    $accept_record = true;
                }
            }
        }

        if ($equipment->charge_script && count(array_filter($equipment->charge_script), true)) {
            $content->third_tabs
                ->add_tab('limit', [
                    'url'    => $equipment->url('charge.limit', null, null, 'edit'),
                    'title'  => I18N::T('eq_charge', '限制设置'),
                    'weight' => 1,
                ]);

            Event::bind('equipment.charge.edit.content', 'EQ_Charge::edit_charge_limit', 1, 'limit');
        }

        if ($accept_reserv) {
            $content->third_tabs
                ->add_tab('reserv', [
                    'url'    => $equipment->url('charge.reserv', null, null, 'edit'),
                    'title'  => I18N::T('eq_charge', $title),
                    'weight' => 2,
                ]);
            Event::bind('equipment.charge.edit.content', 'EQ_Charge::edit_charge_reserv', 1, 'reserv');
        }

        if ($accept_record) {
            $content->third_tabs
                ->add_tab('record', [
                    'url'    => $equipment->url('charge.record', null, null, 'edit'),
                    'title'  => I18N::T('eq_charge', '使用计费'),
                    'weight' => 3,
                ]);
            Event::bind('equipment.charge.edit.content', 'EQ_Charge::edit_charge_record', 2, 'record');
        }

        if ($accept_sample) {
            $content->third_tabs
                ->add_tab('sample', [
                    'url'    => $equipment->url('charge.sample', null, null, 'edit'),
                    'title'  => I18N::T('eq_charge', '送样计费'),
                    'weight' => 4,
                ]);
            Event::bind('equipment.charge.edit.content', 'EQ_Charge::edit_charge_sample', 3, 'sample');
        }

        $tabs->content->third_tabs->select($stab);
    }

    public static function edit_charge_limit($e, $tabs)
    {
        $form      = Form::filter(Input::form());
        $equipment = $tabs->equipment;
        if ($form['submit']) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '限制设置更新失败'));
                URI::redirect();
            }

            $equipment->reserv_limit            = $form['reserv_limit'] == 'on' ? true : false;
            $equipment->reserv_balance_required = $form['reserv_limit'] == 'on' ? $form['reserv_balance_required'] : null;

            $equipment->record_limit            = $form['record_limit'] == 'on' ? true : false;
            $equipment->record_balance_required = $form['record_limit'] == 'on' ? $form['record_balance_required'] : null;

            $equipment->sample_limit            = $form['sample_limit'] == 'on' ? true : false;
            $equipment->sample_balance_required = $form['sample_limit'] == 'on' ? $form['sample_balance_required'] : null;

            $equipment->save();

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '限制设置更新成功'));
        }

        $tabs->content = V('eq_charge:edit/limit', ['equipment' => $equipment]);
    }

    public static function edit_charge_dashboard($e, $tabs)
    {
        $equipment  = $tabs->equipment;
        $properties = Properties::factory($equipment);
        $type_array = ['reserv', 'record', 'sample', 'sample_form', 'traning', 'service'];
        $templates  = Config::get('eq_charge.template');

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备收费基本设置更新失败'));
                URI::redirect();
            }

            Event::trigger('edit.charge.dashboard.validate', $form, $equipment);
            if (!$form->no_error) {
                foreach ($form->errors as $errors) {
                    foreach ($errors as $error) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::HT('eq_charge', $error));
                    }
                }
                URI::redirect();
            }

            $department = Module::is_installed('billing') ? Billing_Department::get($_POST['charge']) : Event::trigger('billing_department.get', $_POST['charge']);

            if (L('ME')->is_allowed_to('锁定计费', $equipment)) {
                $equipment->charge_lock = $form['charge_lock'];
            }

            if (is_array($department) && $department['id']) {
                $equipment->billing_dept_id = $department['id'];
            } elseif ($department->id && $equipment->billing_dept->id != $department->id) {
                $now            = Date::time();
                $old_department = $equipment->billing_dept;
                $eq_charge      = Q("eq_charge[dtstart>$now][equipment={$equipment}]");
                foreach ($eq_charge as $charge) {
                    $lab                     = $charge->lab;
                    $account                 = O('billing_account', ['department' => $department, 'lab' => $lab]);
                    $old_account             = O('billing_account', ['department' => $old_department, 'lab' => $lab]);
                    $old_account_balance     = $old_account->balance;
                    $old_account_new_balance = $old_account->balance + $charge->amount;
                    $old_account->balance    = $old_account_new_balance;
                    $old_account->save();
                    if (!$account->id) {
                        $account             = O('billing_account');
                        $account->department = $department;
                        $account->lab        = $lab;
                        $new_account_balance = '-' . $eq_charge->amount;
                        $account->balance    = $new_account_balance;
                        $account->save();
                    } else {
                        $account_balance     = $account->balance;
                        $new_account_balance = $account->balance - $eq_charge->amount;
                        $account->balance    = $new_account_balance;
                        $account->save();
                    }
                    $transactions = Q("{$charge}<transaction billing_transaction");

                    foreach ($transactions as $billing_transaction) {
                        $billing_transaction->account = $account;
                        $billing_transaction->balance = $new_account_balance;
                        $billing_transaction->save();
                    }
                }

                $equipment->billing_dept = $department;
            }

            $arr = [];

            $r_template = $form['reserv_record_template'];

            if ($r_template == 'advanced_custom') {
                $arr['reserv'] = 'custom_reserv';
                $arr['record'] = 'custom_record';
            } elseif ($r_template != 'free') {
                if ($r_template == 'custom_reserv') {
                    $t = 'reserv';
                } elseif ($r_template == 'custom_record') {
                    $t = 'record';
                } else {
                    $t = $templates[$r_template]['category'];
                }

                if ($t) {
                    $arr[$t] = $r_template;
                }
            }

            if ($form['sample'] != 'no_charge_sample') {
                $arr['sample'] = $form['sample'];
            }

            if ($form['sample_form'] != 'no_charge_sample_form') {
				$arr['sample_form'] = $form['sample_form'];
			}

            if ($form['service'] != 'no_charge_service') {
                $arr['service'] = $form['service'];
            }

            $charge_script = $equipment->charge_script;
            $charge_tags   = $equipment->charge_tags;

            //用于存储计费标准的lua脚本
            $template_standard = $equipment->template_standard;

            // 如果计费设置发生改变，清空原来的设置
            if (!array_key_exists('reserv', $arr)) {
                $equipment->reserv_limit            = false;
                $equipment->reserv_balance_required = null;
            }
            if (!array_key_exists('record', $arr)) {
                if ($r_template != 'time_reserv_record') {
                    $equipment->record_limit            = false;
                    $equipment->record_balance_required = null;
                }
            }
            if (!array_key_exists('sample', $arr)) {
                $equipment->sample_limit            = false;
                $equipment->sample_balance_required = null;
            }
            foreach ($type_array as $type) {
                if ($equipment->charge_template[$type] != $arr[$type]) {
                    $charge_script[$type]     = null;
                    $charge_tags[$type]       = null;
                    $template_standard[$type] = null;
                    //清空原来的模板对应的设置
                    $old_template = $templates[$equipment->charge_template[$type]];
                    foreach ((array) $old_template['content'] as $key => $value) {
                        $charge_script[$key]      = null;
                        $charge_tags[$key]        = null;
                        $template_standard[$type] = null;
                        //清空单位计费，开机费设置
                        self::put_charge_setting($equipment, $key);
                    }
                }
            }

            //可视化脚本变量key
        	$charge2CustomName = [
        	    'reserv' => 'reserv_charge_script',
        	    'sample' => 'sample_charge_script',
        	    'record' => 'record_charge_script',
            ];

            // 某些模板可能对应多个脚本，所以应该先foreach将需要清空的数据都清空
            foreach ($type_array as $type) {
                $script = null;

                if ($type == 'sample_form' && !Module::is_installed('sample_form')) {
					continue;
				}

                if ($type == 'service' && !Module::is_installed('technical_service')) {
                    continue;
                }

                //如果选择了计费模板，则根据计费模板生成对应的脚本
                if ($arr[$type] && $arr[$type] != 'custom_' . $type && $equipment->charge_template[$type] != $arr[$type]) {
                    $template = $templates[$arr[$type]];
                    foreach ($template['content'] as $k => $v) {
                        $convert_script = null;
                        $script         = EQ_Lua::get_lua_content('eq_charge', $v['script']);

                        //将php数组的对应的值转换为lua数组
                        //[%options] => {["*"] = {minimum_fee = 0, unit_price = 0}}
                        foreach ((array) $v['params'] as $key => $value) {
                            $params[$key] = EQ_Lua::array_p2l($value) ?: $value;
                        }
                        $convert_script = EQ_Charge_LUA::convert_script($script, $params);

                        $charge_script[$k] = $convert_script;
                        Event::trigger('equipment.custom_content_empty',$equipment,$charge2CustomName[$k]);
                    }

                    $params['%template_title'] = $template['title'];
                    $params['%script']         = '';
                    $params['%template_type']  = $arr[$type];

                    $tstandard                = EQ_Lua::get_lua_content('eq_charge', "private:{$type}_template.lua");
                    $tstandard                = EQ_Charge_LUA::convert_script($tstandard, $params);
                    $template_standard[$type] = $tstandard;
                } elseif ($arr[$type] == 'custom_' . $type && $equipment->charge_template[$type] != $arr[$type]) {
                    // 收费的计费标签及自定义收费的显示与否是以charge_script[$type]作为判断的，所以初始化必须有值
                    $script                   = '--' . I18N::T('billing', '自定义脚本');
                    $charge_script[$type]     = $script;
                    $params['%script']        = $script;
                    $params['%options']       = '\'\'';
                    $params['%template_type'] = $arr[$type];
                    $tstandard                = EQ_Lua::get_lua_content('eq_charge', "private:{$type}_template.lua");
                    $tstandard                = EQ_Charge_LUA::convert_script($tstandard, $params);
                    $template_standard[$type] = $tstandard;
                }
            }

            $equipment->template_standard = $template_standard;
            $equipment->charge_script     = $charge_script;
            $equipment->charge_tags       = $charge_tags;
            $equipment->charge_template   = $arr;

            Event::trigger('extra.charge.setting.content', $form, $equipment);

        	$equipment->save();

			if (Module::is_installed('yiqikong')) {
				CLI_YiQiKong::update_equipment_setting($equipment->id);
			}
            // 检测单价注入lua并存到equipment->charge_script
			Event::trigger('template[project].setting_view', $equipment, null);

            Log::add(strtr('[eq_charge] %user_name[%user_id]修改了%equipment_name[%equipment_id]的基本计费设置', [
                '%user_name'      => L('ME')->name,
                '%user_id'        => L('ME')->id,
                '%equipment_name' => $equipment->name,
                '%equipment_id'   => $equipment->id,
            ]), 'journal');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备收费基本设置已更新'));
            URI::redirect();
        }

        $tabs->content = V('eq_charge:edit/charge', ['equipment' => $equipment]);
    }

    public static function edit_charge_reserv($e, $tabs)
    {
        $equipment            = $tabs->equipment;
        $accept_reserv        = $equipment->accept_reserv;
        $enable_charge_script = Config::get('equipment.enable_charge_script', false);
        $charge_type          = $equipment->charge_template['reserv'];

        $form = Form::filter(Input::form());
        //如果不是自定义则trigger对应的事件，如果存在则显示
        if ($charge_type && $charge_type != 'custom_reserv') {
            $script_view = Event::trigger('template[' . $charge_type . '].setting_view', $equipment, $form);
        }

        if (!$script_view && Input::form('submit')) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备使用收费信息更新失败'));
                URI::redirect();
            }

            $success = true;

            if (!$charge_type) {
            } elseif ($charge_type == 'custom_reserv' && $enable_charge_script) {
                $script = $form['reserv_charge_script'];
                if (!$script) {
                    $script = '--' . I18N::T('billing', '自定义脚本');
                }

                $script_array           = (array) $equipment->charge_script;
                $script_array['reserv'] = $script;

                $template_standard            = $equipment->template_standard;
                $template_standard['reserv']  = $script;
                $equipment->template_standard = $template_standard;

                $equipment->charge_script = $script_array;

                //lua脚本是否非genee用户可见
                $script_array = $equipment->display_reserv_script;
                if (!is_array($script_array)) $script_array = [];
                if ($form['display_reserv_charge_script'] == 'on') $script_array['reserv_charge_script'] = 1;
                else $script_array['reserv_charge_script'] = 0;
                $equipment->display_reserv_script = $script_array;

                $tag_array              = (array) $equipment->charge_tags;
                $tag_array['reserv']    = array_values((array) @json_decode($form['reserv_charge_tags'], true));
                $equipment->charge_tags = $tag_array;

                $params['%script']        = $script;
                $params['%options']       = '\'\'';
                $params['%template_type'] = $charge_type;

                $template_standard = self::update_template_standard($equipment, 'reserv', $params);

                $equipment->template_standard = $template_standard;

                if (!EQ_Charge_LUA::check_syntax($equipment->charge_script['reserv'], 'reserv', $err)) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '计费脚本问题: %lua', ['%lua' => $err]));
                    $success = false;
                }

                if ($success) {
                    Event::trigger("equipment.custom_content",$script,$form,$equipment);

					$equipment->save();
					
					if (Module::is_installed('yiqikong')) {
						CLI_YiQiKong::update_equipment_setting($equipment->id);
					}

                    Log::add(strtr('[eq_charge] %user_name[%user_id]修改了%equipment_name[%equipment_id]的使用计费设置', [
                        '%user_name'      => L('ME')->name,
                        '%user_id'        => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id'   => $equipment->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备使用收费信息已更新'));
                }
            }
        }

        if ($charge_type == 'custom_reserv') {
            if ($enable_charge_script) {
                $script_view = V('eq_charge:edit/setup/reserv/custom', ['equipment' => $equipment]);
            }
        } elseif (!$script_view) {
            //没有自定义设置页面，则显示默认的页面
            $script_view = V('eq_charge:edit/setup/reserv/default', ['equipment' => $equipment]);
        }
        $tabs->content = $script_view;
    }

    public static function edit_charge_record($e, $tabs)
    {
        $equipment            = $tabs->equipment;
        $properties           = Properties::factory($equipment);
        $charge_type          = $equipment->charge_template['record'];
        $enable_charge_script = Config::get('equipment.enable_charge_script', false);

        $form = Form::filter(Input::form());
        //如果不是自定义则trigger对应的事件，如果存在则显示
        if ($charge_type && $charge_type != 'custom_reserv') {
            $script_view = Event::trigger('template[' . $charge_type . '].setting_view', $equipment, $form);
        }

        if (!$script_view && Input::form('submit')) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备使用收费信息更新失败'));
                URI::redirect();
            }

            $success = true;

            if (!$charge_type) {
            } elseif ($charge_type == 'custom_record' && $enable_charge_script) {
                $equipment->charge_merge_duration = $form['charge_merge_duration'] ? true : false;

                $script = $form['record_charge_script'];
                if (!$script) {
                    $script = '--' . I18N::T('billing', '自定义脚本');
                }

                $script_array             = $equipment->charge_script;
                $script_array['record']   = $script;
                $equipment->charge_script = $script_array;

                //lua脚本是否非genee用户可见
                $script_array = $equipment->display_reserv_script;
                if (!is_array($script_array)) $script_array = [];
                if ($form['display_record_reserv_script'] == 'on') $script_array['record_charge_script'] = 1;
                else $script_array['record_charge_script'] = 0;
                $equipment->display_reserv_script = $script_array;

                $tag_array              = (array) $equipment->charge_tags;
                $tag_array['record']    = array_values((array) @json_decode($form['record_charge_tags'], true));
                $equipment->charge_tags = $tag_array;

                $params['%script']        = $script;
                $params['%options']       = '\'\'';
                $params['%template_type'] = $charge_type;

                $template_standard = self::update_template_standard($equipment, 'record', $params);

                $equipment->template_standard = $template_standard;

                if (!EQ_Charge_LUA::check_syntax($equipment->charge_script['record'], 'record', $err)) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '计费脚本问题: %lua', ['%lua' => $err]));
                    $success = false;
                }

                if ($success) {
                    Event::trigger("equipment.custom_content",$script,$form,$equipment);

					$equipment->save();
					
					if (Module::is_installed('yiqikong')) {
						CLI_YiQiKong::update_equipment_setting($equipment->id);
					}

                    Log::add(strtr('[eq_charge] %user_name[%user_id]修改了%equipment_name[%equipment_id]的使用计费设置', [
                        '%user_name'      => L('ME')->name,
                        '%user_id'        => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id'   => $equipment->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备使用收费信息已更新'));
                }
            }
        }

        if ($charge_type == 'custom_record') {
            if ($enable_charge_script) {
                $script_view = V('eq_charge:edit/setup/record/custom', ['equipment' => $equipment]);
            }
        } elseif (!$script_view) {
            $script_view = V('eq_charge:edit/setup/record/default', ['equipment' => $equipment]);
        }

        $tabs->content = $script_view;
    }

    public static function edit_charge_sample($e, $tabs)
    {
        $equipment            = $tabs->equipment;
        $accept_sample        = $equipment->accept_sample;
        $charge_type          = $equipment->charge_template['sample'];
        $properties           = Properties::factory($equipment);
        $enable_charge_script = Config::get('equipment.enable_charge_script', false);

        $form = Form::filter(Input::form());
        //如果不是自定义则trigger对应的事件，如果存在则显示
        if ($charge_type && $charge_type != 'custom_reserv') {
            $script_view = Event::trigger('template[' . $charge_type . '].setting_view', $equipment, $form);
        }
        if (Input::form('submit')) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备送样收费信息更新失败'));
                URI::redirect();
            }

            $form = Form::filter(Input::form());

            $success = true;

            if (!$charge_type) {
            } elseif ($charge_type == 'custom_sample' && $enable_charge_script) {
                $script = $form['sample_charge_script'];
                if (!$script) {
                    $script = '--' . I18N::T('billing', '自定义脚本');
                }

                $script_array           = $equipment->charge_script;
                $script_array['sample'] = $script;

                $equipment->charge_script = $script_array;

                //lua脚本是否非genee用户可见
                $script_array = $equipment->display_reserv_script;
                if (!is_array($script_array)) $script_array = [];
                if ($form['display_sample_charge_script'] == 'on') $script_array['sample_charge_script'] = 1;
                else $script_array['sample_charge_script'] = 0;
                $equipment->display_reserv_script = $script_array;

                $tag_array           = (array) $equipment->charge_tags;
                $tag_array['sample'] = array_values((array) @json_decode($form['sample_charge_tags'], true));

                $params['%script']        = $script;
                $params['%template_type'] = $charge_type;
                $params['%options']       = '\'\'';

                $equipment->charge_tags       = $tag_array;
                $template_standard            = self::update_template_standard($equipment, 'sample', $params);
                $equipment->template_standard = $template_standard;

                if (!EQ_Charge_LUA::check_syntax($equipment->charge_script['sample'], 'sample', $err)) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '计费脚本问题: %lua', ['%lua' => $err]));
                    $success = false;
                }

                if ($success) {

                    Event::trigger("equipment.custom_content",$script,$form,$equipment);

                    $equipment->save();

                    Log::add(strtr('[eq_charge] %user_name[%user_id]修改了%equipment_name[%equipment_id]的送样计费设置', [
                        '%user_name'      => L('ME')->name,
                        '%user_id'        => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id'   => $equipment->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备送样收费信息已更新'));
                }
            }
        }

        if ($charge_type == 'custom_sample') {
            if ($enable_charge_script) {
                $script_view = V('eq_charge:edit/setup/sample/custom', ['equipment' => $equipment]);
            }
        } elseif (!$script_view) {
            $script_view = V('eq_charge:edit/setup/sample/default', ['equipment' => $equipment]);
        }

        $tabs->content = $script_view;
    }

    //更新仪器的计费脚本,type为reserv,record,sample
    public static function update_charge_script($equipment, $type, $params)
    {
        if (!$equipment->id || !$type) {
            return;
        }

        $charge_type = $equipment->charge_template[$type];
        if (!$charge_type) {
            return;
        }

        $script_array      = $equipment->charge_script;
        $template          = Config::get('eq_charge.template');
        $content           = $template[$charge_type]['content'];
        $template_standard = $equipment->template_standard;
        $compiled          = true;
        foreach ((array) $content as $key => $c) {
            $script         = EQ_Charge_LUA::get_lua_content('eq_charge', $c['script']);
            $convert_script = EQ_Charge_LUA::convert_script($script, $params);

            if ($convert_script) {
                $script_array[$key] = $convert_script;

                $params['%template_title'] = $template[$charge_type]['title'];
                $params['%script']         = '';
                $params['%template_type']  = $charge_type;

                $tstandard                = EQ_Lua::get_lua_content('eq_charge', "private:{$type}_template.lua");
                $tstandard                = EQ_Charge_LUA::convert_script($tstandard, $params);
                $template_standard[$type] = $tstandard;
            } else {
                $compiled = false;
            }
        }

        if ($compiled) {
            $equipment->template_standard = $template_standard;
            $equipment->charge_script     = $script_array;
            $equipment->save();

            return true;
        }
    }

    public static function update_template_standard($equipment, $type = null, $params = null)
    {
        if (!$type) {
            return;
        }

        //将脚本信息存入显示脚本中
        $template_standard        = $equipment->template_standard;
        $tstandard                = EQ_Lua::get_lua_content('eq_charge', "private:{$type}_template.lua");
        $tstandard                = EQ_Charge_LUA::convert_script($tstandard, (array) $params);
        $template_standard[$type] = $tstandard;
        return $template_standard;
    }

    public static function cannot_access_equipment($e, $equipment, $params)
    {
        $me  = $params[0];
        $now = $params[1];

        if ($equipment->charge_script['record']) {
            if (!Module::is_installed('billing')) {
                return;
            }
            $department = $equipment->billing_dept;
            if (!$department->id) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '该仪器未指定财务部门, 不可使用该仪器'));
                $e->return_value = true;
                return false;
            }

            $account = Q("$me lab billing_account[department=$department]");
            if (!$account->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '您实验室在该设备指定的财务部门无帐号'));
                $e->return_value = true;
                return false;
            }

            $can_reserv = false;
            foreach ($account as $acc) {
                if (($acc->sum('balance') + $acc->sum('credit_line')) >= ($equipment->record_limit ? $equipment->record_balance_required : 0)) {
                    $can_reserv = true;
                    break;
                }
                continue;
            }
            if (!$can_reserv) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室余额不足, 您目前无法预约该设备。'));
                $e->return_value = true;
                return false;
            }
        }
    }

    public static function cannot_reserv_equipment($e, $equipment, $params)
    {
        $me = L('ME');
        if ($equipment->charge_script['reserv']) {
            if (!Module::is_installed('billing')) {
                return;
            }
            $department = $equipment->billing_dept;
            if (!$department->id) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '该设备未指定财务部门, 您目前无法预约该设备。'));
                $e->return_value = true;
                return false;
            }
            $account = Q("$me lab billing_account[department=$department]");
            if (!$account->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室在该设备指定财务部门内无帐号, 您目前无法预约该设备。'));
                $e->return_value = true;
                return false;
            }

            $can_reserv = false;
            $limit      = $equipment->record_limit ? $equipment->record_balance_required : 0;
            foreach ($account as $acc) {
                if (($acc->balance + $acc->credit_line) >= $limit) {
                    $can_reserv = true;
                    break;
                }
                continue;
            }
            if (!$can_reserv) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室余额不足, 您目前无法预约该设备。'));
                $e->return_value = true;
                return false;
            }
        }
    }

    public static function record_edit_view($e, $record, $form, $sections)
    {
        $sections[] = V('eq_charge:edit/record_section', ['record' => $record, 'form' => $form]);
    }

    public static function on_record_before_save($e, $record, $new_data)
    {
        if (!$record->id) {
            return true;
        }

        $charge = O('eq_charge', ['source' => $record]);
        if (!$charge->id) {
            $charge = O('eq_charge', ['source' => $record->reserv]);
        }

        if (!$charge->id) {
            return true;
        }

        $equipment    = $record->equipment;
        $template_old = $charge->charge_template;
        $type_old     = $charge->charge_type;
        $template_new = $equipment->charge_template['reserv'] ?? $equipment->charge_template['record'];
        $type_new     = $equipment->charge_template['reserv'] ? 'reserv' : 'record';
        if ($template_old == $template_new || $type_old == $type_new) {
            return true;
        }

        if (!$record->reserv->id) {
            return true;
        }

        $records = Q("eq_record[reserv={$record->reserv}][id!={$record->id}]")->total_count();
        if ($records > 0) {
            return true;
        } else {
            $charge = O('eq_charge', ['source' => $record->reserv]);
            if ($charge->id) {
                $charge->delete();
            }

        }
    }

    //仪器使用记录删除时触发的事件，修改相应的使用设备费用
    public static function on_record_deleted($e, $record)
    {
        $charge = O('eq_charge', ['source' => $record]);

        $user      = $record->user;
        $equipment = $record->equipment;
        $dtstart   = $record->dtstart;
        $dtend     = $record->dtend;
        $lab_owner = $charge->lab->owner;

        Notification::send('eq_charge.record_delete_charge_to_user', $user, [
            '%user'           => Markup::encode_Q($user),
            '%equipment'      => Markup::encode_Q($equipment),
            '%edit_user'      => Markup::encode_Q(L('ME')),
            '%transaction_id' => Number::fill($charge->id, 6),
            '%record_id'      => Number::fill($record->id, 6),
            '%old_amount'     => Number::currency($charge->amount),
        ]);

        if ($lab_owner->id) {
            Notification::send('eq_charge.record_delete_charge_to_pi', $lab_owner, [
                '%pi'             => Markup::encode_Q($lab_owner),
                '%user'           => Markup::encode_Q($user),
                '%equipment'      => Markup::encode_Q($equipment),
                '%edit_user'      => Markup::encode_Q(L('ME')),
                '%new_amount'     => Number::currency($charge->amount),
                '%record_id'      => Number::fill($record->id, 6),
                '%transaction_id' => Number::fill($charge->id, 6),
                '%old_amount'     => Number::currency($charge->amount),
            ]);
        }

        if ($charge->id) {
            $charge->delete();
        }
    }

    /*
    仪器使用记录保存时触发的事件，
    根据使用记录及相关预约，生成计费

    首先假设新生成一条记录
     */
    public static function on_record_saved($e, $record, $old_data, $new_data)
    {
        $equipment = $record->equipment;
        $user      = $record->user;
        $lab       = $GLOBALS['preload']['people.multi_lab'] ? $record->project->lab : Q("$user lab")->current();

        //如果没有使用记录计费脚本，且使用不计费，则不进行使用计费
        if (!$equipment->charge_script['record']) {
            //如果从计费状态切换为不计费，编辑使用记录,如果charge存在且没锁应该将原来的收费删除
            $charge = O('eq_charge', ['source' => $record]);
            if ($charge->id && !$charge->is_locked) {
                $charge->delete();
            }
        } else {
            $dtstart = $record->dtstart;
            $dtend = $record->dtend;
            $me = L('ME');

			$condition = false;
			// 当确定需要检测的key变动之后才进行计费
			foreach (Config::get('eq_charge.calculate_keys')['record'] as $key) {
                if ($key == 'cooling' || $key == 'preheat') {
                    $condition = $condition || ($new_data[$key] != $old_data[$key]);
                } else {
                    $condition = $condition || ($new_data[$key] && $new_data[$key] != $old_data[$key]);
                }
			}

            if ($condition) {
                //离线同步记录可能user为空, 不予进行收费
                if ($record->user->id && $dtend > 0) {
                    $charge            = O('eq_charge', ['source' => $record]);
                    $charge->equipment = $equipment;
                    $charge->source    = $record;

                    //如果修改了$record的user,则设定$charge->lab为$record->user->lab
                    if (!$charge->lab->id || $charge->user->id != $record->user->id
                        || $record->project->lab->id != $charge->lab->id) {
                        $charge->lab = $lab;
                    }

                    $charge->user = $user;
                    $charge->calculate_amount()->save();
                    //在使用记录编辑页面，设置关联预约的自定义计费
                }
            }

            if (Config::get('eq_charge.foul_charge') && $record->reserv->id) {
                $dtstart = $record->dtstart;
                $dtend   = $record->dtend;
                $reservs = Q("eq_reserv[dtend={$dtstart}~{$dtenc}|dtstart={$dtstart}~{$dtend}][user={$user}][equipment=$equipment]");
                if ($reservs) {
                    foreach ($reservs as $reserv) {
                        $charge = O('eq_charge', ['source_id' => -$reserv->id]);
                        if ($charge->id && $charge->user->id == $user->id) {
                            $charge->delete();
                            Log::add(strtr('[eq_charge] 因用户%me_name[%me_id]补充使用记录[%record_id] 使用者%user_name[%user_id] 爽约收费记录被删除', [
                                '%me_name'   => $me->name,
                                '%me_id'     => $me->id,
                                '%record_id' => $record->id,
                                '%user_name' => $user->name,
                                '%user_id'   => $user->id,
                            ]), 'record');
                        }
                    }
                }
            }

            $db     = Database::factory();
            $charge = O('eq_charge', ['source' => $record]);
            // 锁定时data['is_locked'] == 1 故 set is_locked = 1
            // 解锁时时data['is_locked'] == 0 若remoteLock == 0 才set is_locked = 0
            $locked = (int) ($charge->remoteLock || $new_data['is_locked']);
            $db->query("UPDATE eq_charge SET is_locked = {$locked} WHERE id = %d", $charge->id);
            Log::add(strtr(
                '[eq_charge] SET eq_charge:is_locked[%id] = %locked',
                ['%id' => $charge->id, '%locked' => $new_data['is_locked']]
            ), 'journal');
        }

        // 更新完自己本身的record之后，会继续更新相关联的所有预约的charge
        $dtstart = $record->dtstart;
        $dtend   = $record->dtend;
        $ostart  = $old_data['dtstart'] ?: $dtstart;
        $oend    = $old_data['dtend'] ?: $dtend;

        foreach (Q("eq_reserv[equipment={$equipment}][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend|dtstart~dtend=$ostart|dtstart~dtend=$oend|dtstart=$ostart~$oend]") as $reserv) {
            $c = O('eq_charge', ['source' => $reserv]);
            //如果没有charge，且仪器不计费，则跳过
            if (!$c->id && !$equipment->charge_script['reserv']) {
                continue;
            }

            if (!$c->source->id) {
                $c->source = $reserv;
            }

            $c->user      = $reserv->user;
            $c->lab       = $GLOBALS['preload']['people.multi_lab'] ? $reserv->project->lab : Q("$reserv->user lab")->current();
            $c->equipment = $reserv->equipment;
            $c->calculate_amount()->save();
            $reserv->save();
        }

        $samples = Q("$record eq_sample");
        foreach ($samples as $sample) {
            $sc = O('eq_charge', ['source' => $sample]);
            if (!$sc->id && !$equipment->charge_script['sample']) {
                continue;
            }

            if (!$sc->source->id) {
                $sc->source = $sample;
            }

            $sc->user      = $sample->sender;
            $sc->lab       = $GLOBALS['preload']['people.multi_lab'] ? $sample->project->lab : Q("$sample->sender lab")->current();
            $sc->equipment = $sample->equipment;
            $sc->calculate_amount()->save();
            $sample->save();
        }
    }

    //预约表单提交事件，需要发送消息变化的提醒。
    public static function component_form_submit($e, $form, $component)
    {
        $parent = $component->calendar->parent;
        if ($parent->name() != 'equipment') {
            return;
        }

        //不是新添加的预约
        if ($form['component_id']) {
            $equipment = $parent;

            //预约是收费的
            $reserv = O('eq_reserv', ['component' => $component]);
            if ($reserv->id && $equipment->charge_script['reserv']) {
                $charge = O('eq_charge', ['source' => $reserv]);

                if (!$charge->id) {
                    $charge = O('eq_charge');
                }

                $charge->source    = $reserv;
                $charge->equipment = $reserv->equipment;
                $charge->user      = $reserv->user;
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $charge->lab = $reserv->project->lab;
                } else {
                    $charge->lab = Q("$reserv user lab")->current();
                }

                if ($form['reserv_charge_tags'] && $equipment->charge_tags['reserv']) {
                    $charge_tags = $form['reserv_charge_tags'];

                    $tags = [];
                    foreach ((array) $charge_tags as $k => $v) {
                        if ($v['checked'] == 'on') {
                            $k        = rawurldecode($k);
                            $tags[$k] = $v['value'];
                        }
                    }
                    $charge->charge_tags = $tags;
                }

                if ($form['reserv_custom_charge'] == 'on') {
                    $charge->custom = true;
                    $charge->amount = (float) $form['reserv_amount'];
                } else {
                    $charge->custom = false;
                }

                $charge->save();
            }
        }
    }

    public static function record_form_submit($e, $record, $form)
    {
        if ($record->name() == 'eq_sample' || $record->name() == 'eq_reserv') {
            return;
        }

        $equipment = $record->equipment;
        //仪器不是免费使用，才进行计费相关的计算
        if ($record->id && $record->dtend && $equipment->charge_script['record']) {
            //目前是生成使用记录后才能编辑计费，所以这时候一定会有charge
            $charge = O('eq_charge', ['source' => $record]);
            //如果开始仪器是免费使用，改为收费后，点击使用记录，编辑标签，应该先生成charge
            if (!$charge->id) {
                $charge = O('eq_charge');
            }

            $_custom = $charge->custom;
			$_amount = $charge->amount;

            $charge->source    = $record;
            $charge->equipment = $record->equipment;
            $charge->user      = $record->user;
            if ($GLOBALS['preload']['people.multi_lab']) {
                $charge->lab = $record->project->lab;
            } else {
                $charge->lab = Q("$record user lab")->current();
            }
            if (!$charge->lab) {
                return;
            }

            if ($form['charge_tags'] && $equipment->charge_tags['record']) {
                $charge_tags = (array) $form['charge_tags'];
                $tags        = [];
                foreach ((array) $charge_tags as $k => $v) {
                    if ($v['checked'] == 'on') {
                        $k        = rawurldecode($k);
                        $tags[$k] = $v['value'];
                    }
                }
                $charge->charge_tags = $tags;
            }

            if ($form['record_custom_charge'] == 'on') {
                $charge->custom = true;
                $charge->amount = (float) $form['record_amount'];
            } else {
				$charge->custom = $_custom ?: 0;
				if ($_custom) {
					$charge->amount = $_amount;
				}
            }

            $charge->calculate_amount()->save();
        }

        //修改预约的自定义金额
        $reserv = $record->reserv;
        if ($reserv->id && ($equipment->charge_script['reserv'] || $equipment->charge_template['reserv'])) {
            $reserv_charge = O('eq_charge', ['source' => $reserv]);
            if (!$reserv_charge->id) {
                $reserv_charge = O('eq_charge');
            }

			$_custom = $reserv_charge->custom;
			$_amount = $reserv_charge->amount;

            $reserv_charge->source    = $reserv;
            $reserv_charge->equipment = $reserv->equipment;
            $reserv_charge->user      = $reserv->user;
            if ($GLOBALS['preload']['people.multi_lab']) {
                $reserv_charge->lab = $reserv->project->lab;
            } else {
                $reserv_charge->lab = Q("{$reserv->user} lab")->current();
            }

            if ($form['reserv_charge_tags']) {
                $reserv_charge_tags = (array) $form['reserv_charge_tags'];
                $reserv_tags        = [];
                foreach ((array) $reserv_charge_tags as $k => $v) {
                    if ($v['checked'] == 'on') {
                        $k               = rawurldecode($k);
                        $reserv_tags[$k] = $v['value'];
                    }
                }

                $reserv_charge->charge_tags = $reserv_tags;
            }

            if ($form['reserv_custom_charge'] == 'on') {
                $reserv_charge->custom = true;
                $reserv_charge->amount = (float) $form['reserv_amount'];
            } else {
				$reserv_charge->custom = $_custom ?: 0;
				if ($_custom) {
					$reserv_charge->amount = $_amount;
				}
            }

            $reserv_charge->calculate_amount()->save();
        }
    }

    //仪器使用记录显示计费部分
    public static function record_description($e, $record, $current_user = null)
    {
        $equipment = $record->equipment;
        $user      = $record->user;
        $dtstart   = $record->dtstart;

        $charge        = O("eq_charge", ['source' => $record]);
        $reserv_charge = O('eq_charge', ['source' => $record->reserv]);

        $e->return_value[] = V('eq_charge:record.notes', ['charge' => $charge, 'reserv_charge' => $reserv_charge, 'record' => $record, 'current_user' => $current_user]);
    }

    public static function record_notes_csv($e, $record, $current_user = null)
    {
        $equipment = $record->equipment;
        $user      = $record->user;
        $dtstart   = $record->dtstart;

        $charge        = O('eq_charge', ['source' => $record]);
        $reserv_charge = O('eq_charge', ['source' => $record->reserv]);

        $equipment          = $record->equipment;
        $amount             = $charge->amount;
        $auto_amount        = $charge->auto_amount;
        $charge_description = $charge->description;

        if ($reserv_charge->id) {
            $amount += $reserv_charge->amount;
            $auto_amount += $reserv_charge->auto_amount;
            $charge_description = $reserv_charge->description . $charge_description;
        }

        $description = "\n" . I18N::T('eq_charge', '收费 %fee', ['%fee' => Number::currency($amount)]);
        //TODO 需要修改href, 可进行搜索
        $tid = [];
        if ($reserv_charge->id && $reserv_charge->amount) {
            $tid[] = $reserv_charge->transaction->id;
        }

        if ($charge->id && $charge->amount) {
            $tid[] = $charge->transaction->id;
        }

        if (count($tid)) {
            $_t = [];
            foreach ($tid as $t) {
                $_t[] = '#' . Number::fill($t);
            }

            $_t = join(', ', $_t);

            $description .= "\n" . I18N::T('eq_charge', '计费编号 %tid', ['%tid' => $_t]);
        }
        $me = L('ME');
        if (!L('ME')->id) {
            $me = O('user', $current_user);
        }
        if (($charge->custom || $reserv_charge->custom) && $me->is_allowed_to('查看估计收费', $charge->equipment)) {
            $description .= "\n" . I18N::T('eq_charge', '估计收费 %fee', [
                '%fee' => Number::currency($auto_amount),
            ]);
        }
        $description .= $charge_description;
        $description       = str_replace(' <span>', ' ', $description);
        $description       = str_replace('<span>', '', $description);
        $description       = str_replace('</span>', '', $description);
        $description       = str_replace('<p>', "\n", $description);
        $description       = str_replace('</p>', '', $description);
        $e->return_value[] = $description;
        //$e->return_value[] = 123;//V('eq_charge:record.notes_csv', array('charge'=>$charge, 'reserv_charge'=>$reserv_charge));
    }

    public static function get_update_parameter($e, $object, array $old_data = [], array $new_data = [])
    {
        if ($object->name() != 'equipment') {
            return;
        }

        $difference     = array_diff_assoc($new_data, $old_data);
        $old_difference = array_diff_assoc($old_data, $new_data);

        foreach ($old_data as $key => $value) {
            if (is_array($value)) {
                $old_difference[$key] = array_diff_assoc($value, (array) $new_data[$key]);
            }
        }

        foreach ($new_data as $key => $value) {
            if (is_array($value)) {
                $difference[$key] = array_diff_assoc($value, (array) $old_data[$key]);
            }
        }

        $arr = array_keys($difference);

        $keys = array_keys(EQ_Charge::$equipment_charge);
        if (!count(array_intersect($arr, $keys))) {
            return;
        }

        $data = $e->return_value;

        $delta            = [];
        $subject          = L('ME');
        $delta['subject'] = $subject;
        $delta['object']  = $object;
        $delta['action']  = 'edit_charge';

        //得到变动了的计费模板设置
        foreach ((array) $old_difference['charge_template'] as $type => $mode) {
            switch ($type) {
                case 'reserv':
                    $type_name = '预约';
                    break;
                case 'record':
                    $type_name = '使用';
                    break;
                case 'sample':
                    $type_name = '送样';
                    break;
            }
            if ($mode && !$difference['charge_template'][$type]) {
                $difference['charge_template_' . $type] = '不进行' . $type_name . '计费';
            }
        }

        foreach ((array) $difference['charge_template'] as $type => $mode) {
            if ($mode == 'custom_' . $type) {
                $difference['charge_template_' . $type] = '自定义计费';
            } else {
                $templates                              = Config::get('eq_charge.template');
                $title                                  = $templates[$mode]['title'];
                $difference['charge_template_' . $type] = $title;
            }
        }

        if (in_array('reserv_balance_required', $arr)) {
            $difference['reserv_balance_required'] = Number::currency($difference['reserv_balance_required']);
        }
        if (in_array('record_balance_required', $arr)) {
            $difference['record_balance_required'] = Number::currency($difference['record_balance_required']);
        }
        if (in_array('sample_balance_required', $arr)) {
            $difference['sample_balance_required'] = Number::currency($difference['sample_balance_required']);
        }

        //如果lua脚本发生改变。显示lua脚本 ...
        if (in_array('charge_script', $arr)) {
            if ($difference['charge_script'] || $old_difference['charge_script']) {
                $difference['charge_script'] = '...';
            }
        }

        $key               = Misc::key((string) $subject, $delta['action'], (string) $object);
        $data[$key]        = (array) $data[$key];
        $delta['new_data'] = $difference;
        $delta['old_data'] = $old_difference;

        Misc::array_merge_deep($data[$key], $delta);

        $e->return_value = $data;
    }

    public static $equipment_charge = [
        'reserv_balance_required' => '预约所需的最低余额',
        'record_balance_required' => '使用所需的最低余额',
        'sample_balance_required' => '送样所需的最低余额',
        'charge_template_reserv'  => '预约设置',
        'charge_template_record'  => '使用设置',
        'charge_template_sample'  => '送样设置',
        'charge_script'           => 'LUA脚本',
        'billing_dept'            => '收费部门',
    ];

    public static function get_update_message($e, $update)
    {
        if ($update->object->name() != 'equipment' || $update->action != 'edit_charge') {
            return;
        }

        $me       = L('ME');
        $subject  = $update->subject->name;
        $old_data = json_decode($update->old_data, true);
        $object   = $old_data['name'] ? $old_data['name'] : $update->object->name;
        /*
        if ($me->id == $update->subject->id) {
        $subject = I18N::T('eq_charge', '我');
        }*/
        $config = 'eq_charge.equipment.msg.model';
        $opt    = Lab::get($config);
        $msg    = I18N::T('eq_charge', $opt['body'], [
            '%subject'   => URI::anchor($update->subject->url(), $subject, 'class="blue label"'),
            '%date'      => '<strong>' . Date::fuzzy($update->ctime, 'TRUE') . '</strong>',
            '%equipment' => URI::anchor($update->object->url(), $object, 'class="blue label"'),
        ]);
        $e->return_value = $msg;
        return false;
    }

    public static function get_update_message_view($e, $update)
    {
        $action     = $update->action;
        $properties = [];
        if ($action != 'edit_charge' || $update->object->name() != 'equipment') {
            return;
        }
        $properties      = EQ_Charge::$equipment_charge;
        $e->return_value = V('eq_charge:update/show_msg', ['update' => $update, 'properties' => $properties]);
        return false;
    }

    //传入对象$object为equipment
    public static function equipment_ACL($e, $user, $perm_name, $equipment, $options)
    {
        /*
        BUG #402 (cheng.liu@2011.03.21)
        该处equipment不应该检测ID，查看所有仪器使用收费时会传入字符串equipment
         */
        if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }

        switch ($perm_name) {
            case '查看计费设置':
                if ($user->access('修改所有仪器的计费设置')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->group->id && $user->access('修改下属机构仪器的计费设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('修改负责仪器的计费设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改计费设置':
                /*
                BUG#403(xiaopei.li@2011.03.21)
                修改计费设置与修改使用设置无关
                 */
                if ($user->access('修改所有仪器的计费设置')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->group->id && $user->access('修改下属机构仪器的计费设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
                    $e->return_value = true;
                    return false;
                }
                if (!$equipment->charge_lock && $user->access('修改负责仪器的计费设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看收费情况':
                if ($equipment == 'equipment') {
                    $e->return_value = true;
                    return false;
                }
                if (Equipments::user_is_eq_incharge($user, $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看所有仪器的使用收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->group->id && $user->access('查看下属机构仪器的使用收费情况') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看估计收费':
                if ($user->access('查看估计收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('查看下属仪器的估计收费情况')
                    && $user->group->id
                    && $equipment->group->id
                    && $user->group->is_itself_or_ancestor_of($equipment->group)

                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '锁定计费':
                if ($user->access('添加/修改所有机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    //传入对象$object为lab
    public static function lab_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '查看收费情况':
                if ((Q("$me $object")->total_count()) && $me->access('查看本实验室的仪器使用收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                if ((Q("$me<pi $object")->total_count()) && $me->access('查看负责实验室的仪器使用收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->group->id && $me->access('查看下属机构实验室的仪器使用收费情况') && $me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('查看所有仪器的使用收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看估计收费':
                if ($me->access('查看估计收费情况')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    //传入对象$object为user
    public static function user_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($me->id == $object->id) {
            $e->return_value = true;
            return false;
        }

        if (Q("($me, $object) lab")->total_count() && $me->access('查看本实验室的仪器使用收费情况')) {
            $e->return_value = true;
            return false;
        }
        if (Q("($me<pi, $object) lab")->total_count() && $me->access('查看负责实验室的仪器使用收费情况')) {
            $e->return_value = true;
            return false;
        }

        if ($me->group->id && $me->access('查看下属机构仪器的使用收费情况') && $me->group->is_itself_or_ancestor_of($object->group)) {
            $e->return_value = true;
            return false;
        }

        if ($me->access('查看所有仪器的使用收费情况')) {
            $e->return_value = true;
            return false;
        }
    }

    //传入对象$object为record
    public static function record_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '修改使用计费':
                $e->return_value = true;
                return true;
                break;
            default:
                break;
        }
    }

    //传入对象$object为sample
    public static function sample_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '修改送样计费':
                $e->return_value = true;
                return true;
                break;
            default:
                break;
        }
    }

    //传入对象$object为cal_component
    public static function cal_component_ACL($e, $user, $perm_name, $component, $options)
    {
        $calendar  = $component->calendar;
        $equipment = $calendar->parent;

        if (!(($calendar->type == 'eq_reserv'
            || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))
            && $calendar->id && $component->id)) {
            return;
        }

        switch ($perm_name) {
            case '修改预约计费':
                if ($user->access('修改所有仪器的预约')) {
                    $e->return_value = true;
                    return false;
                }

                if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
                    $e->return_value = true;
                    return false;
                }

                if ($user->access('修改负责仪器的预约') && Equipments::user_is_eq_incharge($user, $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    public static function get_equipments_updates_configs($e)
    {
        $configs        = $e->return_value;
        $charge_configs = [
            'eq_charge.equipment.msg.model',
        ];
        $e->return_value = array_merge((array) $configs, $charge_configs);
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }

//        if (Q("$user<pi lab")->total_count()) {
//            $perms['查看负责实验室的仪器使用收费情况'] = 'on';
//        }
    }

    public static function charge_is_locked($e, $charge)
    {
        if ($charge->id && $charge->is_locked) {
            $e->return_value = true;
            return false;
        }

        $transaction = $charge->transaction;
        if ($transaction->id && $transaction->is_locked()) {
            $e->return_value = true;
            return false;
        }
    }

    public static function record_is_locked($e, $record)
    {
        if ($record->id && $record->is_locked) {
            $e->return_value = true;
            return false;
        }

        $charge = O('eq_charge', ['source' => $record]);
        if ($charge->id && $charge->is_locked()) {
            $e->return_value = true;
            return false;
        }
    }

    public static function sample_is_locked($e, $sample)
    {
        $charge = O('eq_charge', ['source' => $sample]);
        if ($charge->id && $charge->is_locked()) {
            $e->return_value = true;
            return false;
        }
    }

    public static function reserv_is_locked($e, $reserv)
    {
        $charge = O('eq_charge', ['source' => $reserv]);
        if ($charge->id && $charge->is_locked()) {
            $e->return_value = true;
            return false;
        }
    }

    public static function transaction_description($e, $transaction)
    {
        if ($transaction->description['module'] == 'eq_charge') {
            $description = new Markup(I18N::T('eq_charge', $transaction->description['template'], [
                '%user'      => $transaction->description['%user'],
                '%equipment' => $transaction->description['%equipment'],
            ]), true);
            if ($transaction->description['amend']) {
                $description .= '<br>-----<br>' . $transaction->description['amend'];
            }

            $e->return_value = $description;
            return false;
        }
    }

    public static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        $equipment = $reserv->equipment;
        $user      = $reserv->user;
        $project   = $reserv->project;

        $has_relative_charge = Event::trigger('eq_reserv.has_relative_charge', $equipment);
        if (!$equipment->charge_script['reserv'] && !$has_relative_charge) {
            //如果从计费状态切换为不计费，编辑预约,如果charge存在且没锁应该将原来的收费删除
            $charge = O('eq_charge', ['source' => $reserv]);
            if ($charge->id && !$charge->is_locked) {
                $charge->delete();
            }
            return;
        }

        if (($new_data['user'] != $old_data['user']) &&
            ($new_data['component'] != $old_data['component']) &&
            ($new_data['status'] != $old_data['status']) &&
            ($new_data['dtstart'] != $old_data['dtstart']) &&
            ($new_data['dtend'] != $old_data['dtend']) &&
            ($new_data['project'] != $old_data['project'])) {
            return;
        }

        //仪器预约时候的自定义收费

        $component = $reserv->component;

        /* TODO 目前预约更新之后只需要更新自己的charge */
        $charge     = O('eq_charge', ['source' => $reserv]);
        $old_amount = $charge->amount;

        if (!$charge->id) {
            $charge = O('eq_charge');
        }

        if (!$charge->lab->id || $charge->user->id != $user->id) {
            if ($GLOBALS['preload']['people.multi_lab']) {
                $charge->lab = $new_data['project']->lab;
            } else {
                $charge->lab = Q("$user lab")->current();
            }
        }

        $charge->user      = $user;
        $charge->equipment = $reserv->equipment;
        $charge->source    = $reserv;
        $charge->calculate_amount()->save();

        /* 更新完自己本身的reserv之后，会继续更新相关联的所有使用记录的charge */
        $dtstart = $reserv->dtstart;
        $dtend   = $reserv->dtend;

        $ostart = $old_data['dtstart'] ?: $dtstart;
        $oend   = $old_data['dtend'] ?: $dtend;

        foreach (Q("eq_record[equipment={$equipment}][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend|dtstart~dtend=$ostart|dtstart~dtend=$oend|dtstart=$ostart~$oend]") as $record) {
            $c = O('eq_charge', ['source' => $record]);
            //如果没有charge，且不计费，则跳过
            if (!$c->id && !$equipment->charge_script['record']) {
                continue;
            }

            if (!$c->source->id) {
                $c->source = $record;
            }

            $c->calculate_amount()->save();
        }
    }

    public static function on_eq_reserv_deleted($e, $reserv)
    {
        $charge = O('eq_charge', ['source' => $reserv]);
        if (!$charge->id) {
            return;
        }

        if ($charge->amount == 0 && $charge->auto_amount == 0) {
            $charge->delete();
            return;
        }

        $can_send     = true;
        $cal_rrule_id = $reserv->component->cal_rrule->id;
        if ($cal_rrule_id) {
            $rrule_key = 'reserv_deleted_can_send_' . $cal_rrule_id;
            if (L($rrule_key)) {
                $can_send = false;
            } else {
                Cache::L($rrule_key, true);
            }
        }
        if ($can_send) {
            $user      = $reserv->user;
            $equipment = $reserv->equipment;
            $lab       = $charge->lab;
            $lab_owner = $lab->owner;

            /**
             * PMS 24865 预约收费被删除的消息提醒，消息内容中删除人显示错误
             * 这里是假期设置后cli删除了用户假期时段的预约及计费
             * cli模式中没有L('ME'),调用当前用户进行展示时出错
             */
            Notification::send('eq_charge.reserv_delete_charge_to_user', $user, [
                '%user'       => Markup::encode_Q($user),
                '%equipment'  => Markup::encode_Q($equipment),
                '%edit_user'  => L('ME')->id ? Markup::encode_Q(L('ME')) : '系统',
                '%id'         => Number::fill($charge->id, 6),
                '%old_amount' => Number::currency($charge->amount),
                '%dtstart'    => Date::format($reserv->dtstart, 'Y/m/d H:i:s'),
                '%dtend'      => Date::format($reserv->dtend, 'Y/m/d H:i:s'),
            ]);

            if ($lab_owner->id) {
                Notification::send('eq_charge.reserv_delete_charge_to_pi', $lab_owner, [
                    '%pi'         => Markup::encode_Q($lab_owner),
                    '%user'       => Markup::encode_Q($user),
                    '%equipment'  => Markup::encode_Q($equipment),
                    '%edit_user'  => L('ME')->id ? Markup::encode_Q(L('ME')) : '系统',
                    '%new_amount' => Number::currency($charge->amount),
                    '%id'         => Number::fill($charge->id, 6),
                    '%old_amount' => Number::currency($charge->amount),
                    '%dtstart'    => Date::format($reserv->dtstart, 'Y/m/d H:i:s'),
                    '%dtend'      => Date::format($reserv->dtend, 'Y/m/d H:i:s'),
                ]);
            }
        }

        $charge->delete();
    }

    public static function sample_form_submit($e, $sample, $form)
    {
        if ($form['sample_charge_tags']) {
            Cache::L("charge_tags_{$sample}", (array) $form['sample_charge_tags']);
        }
        if ($form['sample_custom_charge'] == 'on') {
            Cache::L("custom_charge_{$sample}", (float) $form['sample_amount']);
        }
    }

    public static function on_eq_sample_saved($e, $sample, $old_data, $new_data)
    {
        $equipment = $sample->equipment;

        if (Module::is_installed('billing') && !$equipment->billing_dept->id && !$equipment->billing_dept_id) {
            return;
        }

        $user = $sample->sender;

        //送样是否有其他关联收费 比如耗材收费
        $has_relative_charge = Event::trigger('eq_sample.has_relative_charge', $equipment);
        if (!$equipment->charge_script['sample'] && !$has_relative_charge) {
            $charge = O('eq_charge', ['source' => $sample]);
            if ($charge->id && !$charge->is_locked) {
                $charge->delete();
            }

            /* 如果仪器免费使用，则不产生计费 */
            return;
        }

        if (!$new_data['sender'] && !$new_data['count'] && !$new_data['status']) {
            return;
        }

        /* TODO 目前送样预约更新之后只需要更新自己的charge */
        $charge = O('eq_charge', ['source' => $sample]);

        /* 当送样更改了新状态为申请中之后，之前已存在的charge应该被删除 */
        if (isset($old_data['status']) && isset($new_data['status']) &&
            $old_data['status'] != $new_data['status'] &&
            !in_array($new_data['status'], EQ_Sample_Model::$charge_status)
        ) {
            if ($charge->id) {
                $charge->delete();
            }

            return;
        }

        if (in_array($sample->status, EQ_Sample_Model::$charge_status)) {
            $charge = O('eq_charge', ['source' => $sample]);
            //如果开始仪器是免费使用，改为收费后，点击使用记录，编辑标签，应该先生成charge
            if (!$charge->id) {
                $charge = O('eq_charge');
            }

            $charge->source    = $sample;
            $charge->equipment = $sample->equipment;
            $charge->user      = $sample->sender;
            if ($GLOBALS['preload']['people.multi_lab']) {
                $charge->lab = $sample->project->lab;
            } else {
                $charge->lab = Q("{$sample->sender} lab")->current();
            }

            if (!$old_data['id'] && $new_data['id']) {
                if (!is_null(L("charge_tags_sample#0"))) {
                    $charge_tags_sample = L("charge_tags_sample#0");
                    Cache::L("charge_tags_eq_sample#0", null);
                }
                if (!is_null(L("custom_charge_sample#0"))) {
                    $custom_charge_sample = L("custom_charge_sample#0");
                    Cache::L("custom_charge_sample#0", null);
                }
            } else {
                if (!is_null(L("charge_tags_{$sample}"))) {
                    $charge_tags_sample = L("charge_tags_{$sample}");
                    Cache::L("charge_tags_{$sample}", null);
                }
                if (!is_null(L("custom_charge_{$sample}"))) {
                    $custom_charge_sample = L("custom_charge_{$sample}");
                    Cache::L("custom_charge_{$sample}", null);
                }
            }

            if ($charge_tags_sample && $equipment->charge_tags['sample']) {
                $charge_tags = (array) $charge_tags_sample;
                $tags        = [];
                foreach ((array) $charge_tags as $k => $v) {
                    if ($v['checked'] == 'on') {
                        $k        = rawurldecode($k);
                        $tags[$k] = $v['value'];
                    }
                }
                $charge->charge_tags = $tags;
            }

            if (isset($custom_charge_sample)) {
                $charge->custom = 1;
                $charge->amount = (float) $custom_charge_sample;
            } else {
                $charge->custom = 0;
            }
            $charge->user = $sample->sender;
            $charge->calculate_amount()->save();
        }
    }

    public static function on_eq_sample_deleted($e, $sample)
    {
        $charge = O('eq_charge', ['source' => $sample]);
        if (!$charge->id) {
            return;
        }

        if ($charge->amount == 0 && $charge->auto_amount == 0) {
            return;
        }

        $sender    = $sample->sender;
        $equipment = $sample->equipment;
        $now       = Date::time();
        $me        = L('ME');
        $lab_owner = $charge->lab->owner;

        Notification::send('eq_charge.delete_sample_charge.sender', $sender, [
            '%eq_name'        => Markup::encode_Q($equipment),
            '%id'             => Number::fill($sample->id),
            '%time'           => Date::format($now, 'Y/m/d H:i:s'),
            '%user'           => Markup::encode_Q($me),
            '%amount'         => Number::currency($charge->amount),
            '%transaction_id' => Number::fill($charge->transaction->id, 6),
        ]);

        if ($lab_owner->id) {
            Notification::send('eq_charge.delete_sample_charge.pi', $lab_owner, [
                '%sender'         => Markup::encode_Q($sender),
                '%eq_name'        => Markup::encode_Q($equipment),
                '%id'             => Number::fill($sample->id),
                '%time'           => Date::format($now, 'Y/m/d H:i:s'),
                '%user'           => Markup::encode_Q($me),
                '%amount'         => Number::currency($charge->amount),
                '%transaction_id' => Number::fill($charge->transaction->id, 6),
            ]);
        }

        $charge->delete();
    }

    public static function prerender_component($e, $view)
    {
        $form = $view->component_form ?: [];

        $parent = $view->component->calendar->parent;
        $me     = L('ME');

        if ($me->is_allowed_to('修改预约计费', $view->component)) {
            $form['charge_input'] = [
                'label'  => I18N::T('eq_charge', '计费标签'),
                'path'   => [
                    'form' => 'eq_charge:edit/reserv/',
                ],
                'weight' => 55,
            ];
        }

        uasort($form, 'Cal_Component_Model::cmp');
        $view->component_form = $form;
    }

    public static function on_eq_charge_before_save($e, $charge, $new_data)
    {
        // 在收费中保存状态
        if (!$charge->source->id) {
            return true;
        }

        $equipment = $charge->source->equipment;
        switch ($charge->source->name()) {
            case 'eq_record':
                $reserv = $charge->source->reserv;
                if ($equipment->charge_template['record']) {
                    $charge->charge_template = $equipment->charge_template['record'];
                    $charge->charge_type = 'record';
                }
                else {
                    $charge->charge_template = $equipment->charge_template['reserv'];
                    $charge->charge_type = 'reserv';
                }
                break;
            case 'eq_reserv':
                $reserv = $charge->source;
                if ($equipment->charge_template['record']) {
                    $charge->charge_template = $equipment->charge_template['record'];
                    $charge->charge_type = 'record';
                }
                else {
                    $charge->charge_template = $equipment->charge_template['reserv'];
                    $charge->charge_type = 'reserv';
                }
                break;
            case 'eq_sample':
                $charge->charge_template = $equipment->charge_template['sample'] ?: 'custom_sample';
                $charge->charge_type = 'sample';
                break;
        }

        if (!$charge->charge_template) $charge->charge_template = '';

        if (!$reserv->id) {
            $charge->status = EQ_Reserv_Model::NORMAL;
        } else {
            $charge->status = $reserv->status ?: EQ_Reserv_Model::PENDING;
        }
    }

    public static function on_eq_charge_saved($e, $charge, $old_data, $new_data)
    {
        $source    = $charge->source;
        $equipment = $charge->equipment;
        $user      = $charge->user;
        $link      = $equipment->url('charge');
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab = $source->project->lab;
        } else {
            $lab = Q("$user lab")->current();
        }
        $lab_owner  = $lab->owner;
        $old_amount = $old_data['amount'];

        if ($source->id) {
            switch ($source->name()) {
                case 'eq_reserv':
                    $can_send     = true;
                    $cal_rrule_id = $source->component->cal_rrule->id;
                    if ($cal_rrule_id) {
                        $rrule_key = 'charge_saved_can_send_' . $cal_rrule_id;
                        if (L($rrule_key)) {
                            $can_send = false;
                        } else {
                            Cache::L($rrule_key, true);
                        }
                    }
                    if (($old_data['amount'] || $new_data['amount']) && $can_send) {
                        //新添加的预约,导致使用费用变动
                        if (!$old_data['id'] && $new_data['id']) {
                            Notification::send('eq_charge.reserv_add_charge_to_user', $user, [
                                '%user'      => Markup::encode_Q($user),
                                '%equipment' => Markup::encode_Q($equipment),
                                '%amount'    => Number::currency($charge->amount),
                                '%id'        => Number::fill($charge->transaction->id, 6),
                                '%link'      => $link,
                                '%dtstart'   => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                                '%dtend'     => Date::format($source->dtend, 'Y/m/d H:i:s'),
                            ]);

                            if ($lab_owner->id && $lab_owner->id != $user->id
                                && $lab->charge_notification_required// lab设定需进行消息提醒
                                 && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                            ) {
                                Notification::send('eq_charge.reserv_add_charge_to_pi', $lab_owner, [
                                    '%pi'                   => Markup::encode_Q($lab_owner),
                                    '%user'                 => Markup::encode_Q($user),
                                    '%equipment'            => Markup::encode_Q($equipment),
                                    '%amount'               => Number::currency($charge->amount),
                                    '%id'                   => Number::fill($charge->transaction->id, 6),
                                    '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                    '%dtstart'              => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                                    '%dtend'                => Date::format($source->dtend, 'Y/m/d H:i:s'),
                                ]);
                            }
                        } else {
                            if ($old_amount != $charge->amount) {
                                //有component_id则是修改操作
                                //预约的修改导致的使用费用的变动发送的消息提醒暂时合并成一个，
                                Notification::send('eq_charge.reserv_edit_charge_to_user', $user, [
                                    '%user'       => Markup::encode_Q($user),
                                    '%equipment'  => Markup::encode_Q($equipment),
                                    '%edit_user'  => Markup::encode_Q(L('ME')),
                                    '%new_amount' => Number::currency($charge->amount),
                                    '%id'         => Number::fill($charge->transaction->id, 6),
                                    '%old_amount' => Number::currency($old_amount),
                                    '%new_amount' => Number::currency($charge->amount),
                                    '%link'       => $link,
                                    '%dtstart'    => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                                    '%dtend'      => Date::format($source->dtend, 'Y/m/d H:i:s'),
                                ]);

                                // //如果用户的
                                if ($lab_owner->id && $lab_owner->id != $user->id
                                    && $lab->charge_notification_required// lab设定需进行消息提醒
                                     && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                                ) {
                                    Notification::send('eq_charge.reserv_edit_charge_to_pi', $lab_owner, [
                                        '%pi'                   => Markup::encode_Q($lab_owner),
                                        '%user'                 => Markup::encode_Q($user),
                                        '%equipment'            => Markup::encode_Q($equipment),
                                        '%edit_user'            => Markup::encode_Q(L('ME')),
                                        '%old_amount'           => Number::currency($old_amount),
                                        '%new_amount'           => Number::currency($charge->amount),
                                        '%id'                   => Number::fill($charge->transaction->id, 6),
                                        '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                        '%dtstart'              => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                                        '%dtend'                => Date::format($source->dtend, 'Y/m/d H:i:s'),
                                    ]);
                                }
                            }
                        }
                    }
                    break;
                case 'eq_record':
                    if ($old_data['amount'] || $new_data['amount']) {
                        //新添加的预约,导致使用费用变动
                        if (!$old_data['id'] && $new_data['id']) {
                            //新添加的使用记录，区分是管理员添加还是用户使用仪器添加。管理员添加发送消息
                            //如果自己是管理员，自己给自己添加，本身不会产生费用
                            if (L('ME')->id && L('ME')->id != $user->id) {
                                Notification::send('eq_charge.record_add_charge_to_user', $user, [
                                    '%user'           => Markup::encode_Q($user),
                                    '%edit_user'      => Markup::encode_Q(L('ME')),
                                    '%equipment'      => Markup::encode_Q($equipment),
                                    '%amount'         => Number::currency($charge->amount),
                                    '%record_id'      => Number::fill($source->id, 6),
                                    '%transaction_id' => Number::fill($charge->transaction->id, 6),
                                    '%link'           => $link,
                                ]);

                                if ($lab_owner->id && $lab_owner->id != $user->id
                                    && $lab->charge_notification_required// lab设定需进行消息提醒
                                     && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                                ) {
                                    Notification::send('eq_charge.record_add_charge_to_pi', $lab_owner, [
                                        '%pi'                   => Markup::encode_Q($lab_owner),
                                        '%user'                 => Markup::encode_Q($user),
                                        '%edit_user'            => Markup::encode_Q(L('ME')),
                                        '%equipment'            => Markup::encode_Q($equipment),
                                        '%amount'               => Number::currency($charge->amount),
                                        '%record_id'            => Number::fill($source->id, 6),
                                        '%transaction_id'       => Number::fill($charge->transaction->id, 6),
                                        '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                    ]);
                                }
                            }
                        } else {
                            if ($old_amount != $charge->amount) {
                                //有record_id则是修改操作
                                //使用记录的修改导致的使用费用的变动发送的消息提醒暂时合并成一个，
                                Notification::send('eq_charge.record_edit_charge_to_user', $user, [
                                    '%user'           => Markup::encode_Q($user),
                                    '%equipment'      => Markup::encode_Q($equipment),
                                    '%edit_user'      => Markup::encode_Q(L('ME')),
                                    '%new_amount'     => Number::currency($charge->amount),
                                    '%record_id'      => Number::fill($source->id, 6),
                                    '%transaction_id' => Number::fill($charge->transaction->id, 6),
                                    '%old_amount'     => Number::currency($old_amount),
                                    '%new_amount'     => Number::currency($charge->amount),
                                    '%link'           => $link,
                                ]);

                                if ($lab_owner->id && $lab_owner->id != $user->id
                                    && $lab->charge_notification_required// lab设定需进行消息提醒
                                     && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                                ) {
                                    Notification::send('eq_charge.record_edit_charge_to_pi', $lab_owner, [
                                        '%pi'                   => Markup::encode_Q($lab_owner),
                                        '%user'                 => Markup::encode_Q($user),
                                        '%equipment'            => Markup::encode_Q($equipment),
                                        '%edit_user'            => Markup::encode_Q(L('ME')),
                                        '%old_amount'           => Number::currency($old_amount),
                                        '%new_amount'           => Number::currency($charge->amount),
                                        '%record_id'            => Number::fill($source->id, 6),
                                        '%transaction_id'       => Number::fill($charge->transaction->id, 6),
                                        '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                    ]);
                                }
                            }
                        }
                    }
                    break;
                case 'eq_sample':
                    if ($old_data['amount'] || $new_data['amount']) {
                        //新添加的预约,导致使用费用变动
                        if (!$old_data['id'] && $new_data['id']) {
                            //目前直接添加非申请中的送样，产生计费没用发送消息
                            Notification::send('eq_charge.add_sample.sender', $user, [
                                '%sender'  => Markup::encode_Q($user),
                                '%eq_name' => Markup::encode_Q($equipment),
                                '%id'      => Number::fill($source->id),
                                '%amount'  => Number::currency($charge->amount),
                            ]);

                            if ($lab_owner->id && $lab_owner->id != $user->id
                                && $lab->charge_notification_required// lab设定需进行消息提醒
                                 && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                            ) {
                                Notification::send('eq_charge.add_sample.pi', $lab->owner, [
                                    '%pi'                   => Markup::encode_Q($lab_owner),
                                    '%sender'               => Markup::encode_Q($user),
                                    '%eq_name'              => Markup::encode_Q($equipment),
                                    '%id'                   => Number::fill($source->id),
                                    '%amount'               => Number::currency($charge->amount),
                                    '%transaction_id'       => Number::fill($charge->transaction->id),
                                    '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                ]);
                            }
                        } else {
                            if ($old_amount != $charge->amount) {
                                Notification::send('eq_charge.edit_sample_charge.sender', $user, [
                                    '%sender'         => Markup::encode_Q($user),
                                    '%eq_name'        => Markup::encode_Q($equipment),
                                    '%user'           => Markup::encode_Q(L('ME')),
                                    '%id'             => Number::fill($source->id),
                                    '%time'           => Date::format($now, 'Y/m/d H:i:s'),
                                    '%old_amount'     => Number::currency($old_amount),
                                    '%new_amount'     => Number::currency($charge->amount),
                                    '%transaction_id' => Number::fill($charge->transaction->id, 6),
                                ]);

                                if ($lab_owner->id && $lab_owner->id != $user->id
                                    && $lab->charge_notification_required// lab设定需进行消息提醒
                                     && (float) $charge->amount > (float) $lab->min_notification_fee//超过lab设定
                                ) {
                                    Notification::send('eq_charge.edit_sample_charge.pi', $lab_owner, [
                                        '%pi'                   => Markup::encode_Q($lab_owner),
                                        '%sender'               => Markup::encode_Q($user),
                                        '%edit_user'            => Markup::encode_Q(L('ME')),
                                        '%eq_name'              => Markup::encode_Q($equipment),
                                        '%id'                   => Number::fill($source->id),
                                        '%user'                 => Markup::encode_Q(L('ME')),
                                        '%time'                 => Date::format($now, 'Y/m/d H:i:s'),
                                        '%old_amount'           => Number::currency($old_amount),
                                        '%new_amount'           => Number::currency($charge->amount),
                                        '%transaction_id'       => Number::fill($charge->transaction->id, 6),
                                        '%min_notification_fee' => Number::currency((float) $lab->min_notification_fee),
                                    ]);
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }

    //得到仪器的计费设置
    public static function get_charge_setting($e)
    {
        return P($e)->charge_setting;
    }

    // 将仪器的计费设置，存入文件
    /*
    seeting需是一个数组，
    $setting = array(
    'xx'=>xx,
    'xx'=>array(.....),
    )
    如果不传setting，则清空对应的type
     */
    public static function put_charge_setting($equipment, $type, $setting = null)
    {
        $old_setting = self::get_charge_setting($equipment);
        if (!$setting) {
            // 清除旧数据
            unset($old_setting[$type]);
        } else {
            $old_setting[$type] = $setting;
        }
        $new_setting = (array) $old_setting;

        return P($equipment)->set('charge_setting', $new_setting)->save();
    }

    public static function eq_sample_mail_content($e, $sample)
    {
        $e->return_value[] = (string) V('eq_charge:mail/sample_report', ['sample' => $sample]);
    }

    public static function eq_sample_view_print($e, $sample)
    {
        $charge   = O('eq_charge', ['source' => $sample]);
        $category = I18N::T('eq_charge', '收费金额');
        $values   = [
            $category => [
                I18N::T('eq_charge', '收费金额') => Number::currency($charge->amount),
            ],
        ];
        $e->return_value = $values;
        return false;
    }

    public static function equipment_accept_reserv_change($e, $equipment, $accept_reserv)
    {
        //如果需要预约被勾选掉
        //原来的设置如果是按综合计费，则应该将计费方式改变为按使用时间计费，计费设置移动到使用这边
        if ($equipment->accept_reserv && !$accept_reserv) {
            $reserv_charge_type = $equipment->charge_template['reserv'];
            if ($reserv_charge_type) {
                switch ($reserv_charge_type) {
                    case 'time_reserv_record':
                        //当前设置的预约使用计费
                        $charge_template = $equipment->charge_template;
                        unset($charge_template['reserv']);
                        $charge_template['record']  = 'record_time';
                        $equipment->charge_template = $charge_template;

                        //清空reserv的脚本
                        $charge_script = $equipment->charge_script;
                        unset($charge_script['reserv']);
                        $equipment->charge_script = $charge_script;
                        $equipment->save();

                        //使用计费的默认设置
                        $template               = Config::get('eq_charge.template')['record_time'];
                        $charge_default_setting = $template['content']['record']['params']['%options'];

                        //设置计费
                        $charge_setting = EQ_Charge::get_charge_setting($equipment);
                        $setting        = $charge_setting['reserv'] ?: $charge_default_setting;

                        $params = EQ_Lua::array_p2l($setting);
                        if (EQ_Charge::update_charge_script($equipment, 'record', ['%options' => $params])) {
                            self::put_charge_setting($equipment, 'reserv');
                            self::put_charge_setting($equipment, 'record', $setting);
                        }
                        break;
                    default:
                        //清空reserv设置
                        $charge_template = $equipment->charge_template;
                        unset($charge_template['reserv']);
                        $equipment->charge_template = $charge_template;
                        self::put_charge_setting($equipment, 'reserv');

                        //清空reserv的脚本
                        $charge_script = $equipment->charge_script;
                        unset($charge_script['reserv']);
                        $equipment->charge_script = $charge_script;
                        $equipment->save();
                        break;
                }
            }
        }
    }

    public static function equipment_accept_sample_change($e, $equipment, $accept_sample)
    {
        if ($equipment->accept_sample && !$accept_sample) {
            $sample_charge_type = $equipment->charge_template['sample'];
            if ($sample_charge_type) {
                switch ($sample_charge_type) {
                    default:
                        //清空sample设置
                        $charge_template = $equipment->charge_template;
                        unset($charge_template['sample']);
                        $equipment->charge_template = $charge_template;

                        //清空sample的脚本
                        $charge_script = $equipment->charge_script;
                        unset($charge_script['sample']);
                        $equipment->charge_script = $charge_script;
                        $equipment->save();
                        break;
                }
            }
        }
    }

    public static function view_sample_dialog($e, $sample_status_id, $equipment, $sample, $message = null)
    {
        $e->return_value = JS::load('eq_charge:toggle_sample_charge', ['sample_status_id' => $sample_status_id, 'sample_id' => is_object($sample) ? $sample->id : null, 'equipment_id' => $equipment->id, 'trigger_url' => URI::url('!eq_charge/charge'), 'can_charge' => (int) isset($message) ? !($message['is_deadly']) : false]);
        return false;
    }

    public static function sample_table_list_columns($e, $form, $columns)
    {
        $columns['charge_time'] = [
			'title' => I18N::T('eq_charge','计费时间'),
			'nowrap'=>TRUE,
			'weight' => 21
		];

		return TRUE;
    }

    static function sample_table_list_row($e, $row, $sample) {
        $row['charge_time'] = V('eq_charge:charges_table/data/charge_time', ['sample'=>$sample]);
        return TRUE;
    }

    static function charge_template_standards($equipment, $type=null,$user = null) {

    	$me = L('ME');

    	if($type){
	    	$lua = new EQ_Charge_LUA_Template($equipment, $type);
	    	$result = $lua->run(['template_description']);
	    	return $result['template_description'];
	    }
	    else{
	    	$type_array = ['reserv', 'record', 'sample','service', 'test_project'];
	    	foreach ($type_array as $type) {

		    	if(!$equipment->charge_script[$type]) {
		    		switch ($type) {
	    				case 'sample':
	    					$result[$type] = I18N::T('eq_charge', '免费检测');
	    				break;
	    				default:
	    					$result[$type] = I18N::T('eq_charge', '免费使用');
	    				break;
		    		}
		    		continue;
		    	}
                $user = $user === null ? $me : $user;
				if ($user->is_allowed_to('管理使用', $equipment) && !Lab::get('eq_charge.incharges_fee')) {
	    			$result[$type] = I18N::T('eq_charge', '免费使用');
	    		}
	    		else {
	    			$lua = new EQ_Charge_LUA_Template($equipment, $type);
	    			$tmp_result = $lua->run(['template_description']);
	    			$result[$type] = $tmp_result['template_description'];
	    		}
	    	}
	    	return $result;
	    }
    }

	static function calculate_amount($e, $charge) {
		// 案例: 20191188 哈尔滨工业大学仪器收费因未知原因变更
		// 如若该收费被锁定的话, 不应该重新计算收费判断规则如下：
		// source 是否锁定 || 时间段被锁定
		$time = Lab::get('transaction_locked_deadline');
		if ($charge->id && $time && $charge->ctime <= $time) {
			Log::add(strtr('[eq_charge] 计费调整失败 - [%charge_id]计费编号[%transaction_id] 收费 %amount 元，超出系统默认锁定时间限制段。', [
				'%charge_id' => $charge->id,
				'%transaction_id' => $charge->transaction->id,
				'%amount' => $charge->amount
			]), 'charge');
			$e->return_value = $charge;
			return TRUE;
		}

		$source = $charge->source;
		if ($source->id && $source->is_locked()) {
			Log::add(strtr('[eq_charge] 计费调整失败 - [%charge_id]计费编号[%transaction_id] 收费 %amount 元，相关资源%source_name[%source_id] 被锁定。', [
				'%charge_id' => $charge->id,
				'%transaction_id' => $charge->transaction->id,
				'%amount' => $charge->amount,
				'%source_name' => $source->name(),
				'%source_id' => $source->id
			]), 'charge');
			$e->return_value = $charge;
			return TRUE;
		}

        if ($charge->user->id && $charge->user->is_allowed_to('管理使用', $charge->equipment)
            && !Lab::get('eq_charge.incharges_fee')) {
            $fee = 0;
        } else {
        	$charge_lua = new EQ_Charge_LUA($charge);
			$result = $charge_lua->run(['charge_tags','fee', 'description', 'start_time', 'end_time', 'charge_duration_blocks']);

			if (Module::is_installed('material') || Module::is_installed('test_project')) {
                $result = Event::trigger('charge_lua_result.after.calculate_amount', $charge, $result) ?: $result;
            }

        	$fee = (float) round($result['fee'], 2);
			$description = $result['description'] ?: '';
			$charge->charge_duration_blocks = strip_tags($result['charge_duration_blocks'] ? : '');
			$charge->dtstart = $result['start_time'] ? : 0;
			$charge->dtend = $result['end_time'] ? : 0;

			$charge_tags_lua = [];
			if (isset($result['charge_tags']) && $result['charge_tags']){
			    foreach ($result['charge_tags'] as $item){
			        $charge_tags_lua[$item['name']] = $item['price'];
                }
            }
			$charge->charge_tags_lua = $charge_tags_lua;

        }

        $charge->auto_amount = $fee;
		$charge->description = $description ?: '';

		if (!$charge->custom) {
			$charge->amount = $charge->auto_amount;
		}

        $e->return_value = $charge;
        return true;
    }

    public static function charge_forecast($e, $sample, $equipment)
    {

        if (!Config::get('eq_sample.charge_forecast')) {
            return false;
        }

        $e->return_value = V('eq_charge:edit/sample/charge_forecast', ['sample' => $sample, 'equipment' => $equipment]);
        return false;
    }

    //RQ181907-机主编辑使用记录时有一个可以填写的备注框
    public static function charge_desc_view($e, $charge, $record, $record_id)
    {
        if (Config::get('eq_record.charge_desc')) {
            $e->return_value .= V('eq_charge:edit/charge_desc_view', [
                'record' => $record,
            ]);
        }
    }

    public static function accept_sample_extra_value($e, $value)
    {
        $e->return_value = true;
        return true;
    }



    static function charge_table_list_columns($e, $form, $columns)
    {
        $equipment = O('equipment', $form['equipment']);
        if ($equipment->require_dteacher) {
            $columns['duty_teacher'] = [
                'title' => I18N::T('equipments', '值班老师'),
                'nowrap' => TRUE,
                'align' => 'center',
                'weight' => 45,
            ];
        }
    }

    static function charge_table_list_row($e, $row, $charge)
    {

        if ($charge->equipment->require_dteacher) {
            $row['duty_teacher'] = V('eq_charge:charges_table/data/duty_teacher', ['charge' => $charge]);
        }
        $e->return_value = $row;
    }

    static function charge_export($e, $charge, $valid_columns, $data)
    {

        if (array_key_exists('duty_teacher', $valid_columns)) {

            if ($charge->source_name == 'eq_record') {
                $record = $charge->source;
                $dteacher = $record->duty_teacher->id ? $record->duty_teacher->name : '--';
            } else if ($charge->source_name == 'eq_reserv') {
                $records = Q("eq_record[reserv_id=$charge->source_id]")->to_assoc('id', 'duty_teacher_id');

                $dt = [];
                if (count($records)) foreach ($records as $record) {
                    $dteacher = O('user', $record);
                    if ($dteacher->id) {
                        $dt[] = $dteacher->name;
                    }
                }

                $dteacher = (count($dt) ? join(', ', $dt) : '--');

            } else if ($charge->source_name == 'eq_sample') {
                $sample = $charge->source;
                $dteacher = $sample->duty_teacher->id ? $sample->duty_teacher->name : '--';
            }

            $data[] = $dteacher;
        }

        $e->return_value = $data;
    }

    static function export_colums($e, $columns, $oname, $oid) {

        $equipment = O('equipment', $oid);

        if ($equipment->id && !$equipment->require_dteacher) {
            unset($columns['duty_teacher']);
        }
        $e->return_value = $columns;
    }

}
