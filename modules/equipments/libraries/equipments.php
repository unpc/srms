<?php

class Equipments
{

    static function is_accessible($e, $name) {
        $me = L('ME');
        switch ($name) {
            case 'equipments_records':
                if ($me->is_allowed_to('列表所有仪器使用记录', 'equipment')) {
                    $e->return_value = TRUE;
                    return TRUE;
                } else {
                    $e->return_value = FALSE;
                    return FALSE;
                }
                break;
        }
    }

    //default_lab
    public static function default_lab()
    {
        $db                    = Database::factory();
        $defaultLabID          = 0;
        $findDefaultLabSQL     = "SELECT `val` FROM `_config` WHERE `key` = 'equipment.temp_lab_id'";
        $ret                   = $db->value($findDefaultLabSQL);
        $ret and $defaultLabID = @unserialize($ret);

        $lab = O('lab', $defaultLabID);
        if (!$lab->id && !defined('CLI_MODE')) {
            header("Status: 423 Locked");
            die;
        }
        return $lab;
    }

    /* TODO 增加I18N翻译(kai.wu@2011.11.18) */
    static $control_status_tooltip = [
        'power'        => '电源控制',
        'computer'     => '电脑控制',
        'on'           => '使用中',
        'off'          => '未使用',
        'connected'    => '联网',
        'disconnected' => '网络状况不可知',
    ];
    /* @param $len 指定要显示的tip数
     * 地理监控的只显示两个tip，不显示网络状态
     */
    public static function get_tool_tip($control_class, $len = 2)
    {
        $st_arr = explode('_', $control_class);
        if (count($st_arr) != 3 && $len == 3) {
            $st_arr[2] = 'connected';
        }
        $tooltip = '';
        $st_el   = array_shift($st_arr);
        $tooltip = I18N::T('equipments', self::$control_status_tooltip[$st_el]);
        while ($st_el = array_shift($st_arr)) {
            $tooltip .= '<br />';
            $tooltip .= I18N::T('equipments', self::$control_status_tooltip[$st_el]);
        }
        return $tooltip;
    }

    public static function setup_update()
    {
        Event::bind('update.index.tab', 'Equipments::_index_update_tab');
    }

    public static function _index_update_tab($e, $tabs)
    {
        $tabs->add_tab('equipment', [
            'url'   => URI::url('!update/index.equipment'),
            'title' => I18N::T('equipments', '仪器更新'),
        ]);
    }

    public static function setup_profile($e)
    {
        Event::bind('profile.view.tab', 'Equipments::user_record_tab');
        Event::bind('profile.view.content', 'Equipments::user_record_content', 0, 'eq_record');
        Event::bind('profile.view.tool_box', 'Equipments::_tool_box_records', 0, 'eq_record');

        Event::bind('profile.follow.tab', [__CLASS__, '_index_follow_equipments_tab'], 0, 'equipment');
        Event::bind('profile.follow.content', [__CLASS__, '_index_follow_equipments_content'], 0, 'equipment');

        Event::bind('profile.view.tab', 'Training::user_profile_tab');
        Event::bind('profile.view.content', 'Training::user_profile_content', 0, 'eq_training');
        // Event::bind('profile.view.tool_box', 'Training::_tool_box_training_with_user_profile', 0, 'eq_training');

        // 于机主个人信息页面，增加“负责仪器培训/授权”页卡
        Event::bind('profile.view.tab', 'Training::incharge_profile_tab');
        Event::bind('profile.view.content', 'Training::incharge_profile_content', 0, 'eq_incharge_training');

        if (Module::is_installed('exam')) {
            Event::bind('profile.view.tab', 'Training::exam_profile_tab');
            Event::bind('profile.view.content', 'Training::exam_profile_content', 0, 'eq_exam');
        }
    }

    public static function _tool_box_records($e, $tabs)
    {
        $me               = L('ME');

        $equipment  = $tabs->equipment;
        $form_token = $tabs->form_token;
        unset($tabs->form_token);

        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '导出'),
            'tip'   => I18N::T('equipments', '导出Excel'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '打印'),
            'text' => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['serial_number'], 'columns' => (array) $tabs->columns]);

    }

    public static function setup_lab()
    {
        Event::bind('lab.view.tab', 'Equipments::lab_view_tab', 0, 'eq_record');
        Event::bind('lab.view.content', 'Equipments::lab_view_content', 0, 'eq_record');
        Event::bind('lab.view.tool_box', 'Equipments::lab_view_tool', 0, 'eq_record');
    }

    public static function lab_view_tab($e, $tabs)
    {
        $lab = $tabs->lab;
        $me  = L('ME');
        if ($me->is_allowed_to('列表仪器使用记录', $lab)) {
            $tabs
                ->add_tab('eq_record', [
                    'url'   => $lab->url('eq_record'),
                    'title' => I18N::T('equipments', '仪器使用记录'),
                ]);
        }
    }

    public static function lab_view_content($e, $tabs)
    {
        $type       = Input::form('type');
        $form_token = Input::form('form_token');
        if (!$form_token) {
            $form_token = Session::temp_token('eq_record_', 300);
        }
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form = Lab::form(function (&$old_form, &$form) {
                unset($form['type']);
            });
            $_SESSION[$form_token] = $form;
        }

        $tabs->form_token = $form_token;

        $me       = L('ME');
        $lab      = $tabs->lab;
        $selector = '';

        $pre_selectors = new ArrayIterator;
        if ($GLOBALS['preload']['people.multi_lab']) {
            $pre_selectors['project'] = "$lab lab_project<project";
            $pre_selectors['user']    = "user";
        } else {
            $pre_selectors['user'] = "$lab user";
        }

        if ($form['user_name']) {
            $user_name = Q::quote(trim($form['user_name']));
            $pre_selectors['user'] .= "[name*=$user_name]";
        }

        if ($form['equipment_name']) {
            $equipment_name  = Q::quote(trim($form['equipment_name']));
            $pre_selectors['equipment'] = "equipment[name*={$equipment_name}]";
        }

        if ($form['equipment_ref']) {
            $equipment_ref  = Q::quote(trim($form['equipment_ref']));
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= "[ref_no*={$equipment_ref}]";
            } else {
                $pre_selectors['equipment'] = "equipment[ref_no*={$equipment_ref}]";
            }
        }

        $now = time();
        $selector .= " eq_record[dtend<=$now]";

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        // 按时间搜索
        if ($form['dtstart']) {
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[dtend>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote($form['dtend']);
            $selector .= "[dtend>0][dtend<=$dtend]";
        }

        if (isset($form['lock_status']) && $form['lock_status'] != -1) {
            $is_locked = !!$form['lock_status'] ? 1 : 0;
            $selector .= "[is_locked=$is_locked]";
        }

        $new_selector = Event::trigger('eq_record.search_filter.submit', $form, $selector, $pre_selectors);
        if (null !== $new_selector) {
            $selector = $new_selector;
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', (array) $pre_selectors) . ') ' . $selector;
        }
        $sort_by   = $form['sort'] ?: (Config::get('equipment.sort_reserv') ? 'reserv' : '');
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        $sort_str  = ':sort(dtstart D)';

        $new_sort_str = Event::trigger('eq_record.sort_str_factory', $form, $sort_str, $type);
        if (null !== $new_sort_str) {
            $sort_str = $new_sort_str;
        }

        $selector .= $sort_str;

        $records               = Q($selector);
        $form['selector']      = $selector;
        $_SESSION[$form_token] = $form;
        $types                 = ['print', 'csv'];
        if (!in_array($type, $types)) {
            $type = 'html';
        }

        $fields = self::get_fields($form, $tabs);

        $fields['lock_status']    =  [
            'title'     => I18N::T('equipments', ''),
            'weight' => 10,
            'filter'    => [
            'form'  => V('equipments:records_table/filters/lock_status', ['lock_status' => $form['lock_status']]),
            'value' => isset($form['lock_status']) ? ($form['lock_status'] ? I18N::HT('equipments', '已锁定') : I18N::HT('equipments', '未锁定')) : I18N::HT('equipments', '全部'),
            ]
        ];
        $tabs->columns = new ArrayObject($fields);

        $tabs->content = V('equipments:equipment/lab_records', [
            'columns'   => $tabs->columns,
            'form'      => $form,
            'object'    => $lab,
            'sort_flag' => $sort_flag,
            'sort_by'   => $sort_by,
        ]);

        call_user_func('Equipments::list_records_' . $type, $records, $form, $tabs);
    }

    public static function lab_view_tool($e, $tabs)
    {

        $form_token = $tabs->form_token;
        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '导出'),
            'tip'   => I18N::T('equipments', '导出Excel'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text' => I18N::T('equipments', '打印'),
            'tip'   => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];
        $tabs->search_box = V('application:search_box', ['top_input_arr' => ['serial_number', 'user_name', 'equipment_ref'], 'columns' => (array) $tabs->columns, 'panel_buttons' => $panel_buttons]);

    }

    public static function get_fields($form)
    {

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }

        $columns = [
            'serial_number'  => [
                'title'  => I18N::T('equipments', '编号'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/serial_number', ['value' => $form['id']]),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'nowrap' => true,
                'weight' => 10,
            ],
            /* '@lock_status' => [
            'title'  => I18N::T('equipments', '锁定状态'),
            'nowrap'=>TRUE,
            ], */
            'user_name'      => [
                'title'  => I18N::T('equipments', '使用者'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/user_name', ['user_name' => $form['user_name']]),
                    'value' => $form['user_name'] ? H($form['user_name']) : null,
                ],
                'weight' => 20,
                'nowrap' => true,
            ],
            'equipment_name' => [
                'title'  => I18N::T('equipments', '仪器'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/equipment_name', ['equipment_name' => $form['equipment_name']]),
                    'value' => $form['equipment_name'] ? H($form['equipment_name']) : null,
                ],
                'weight' => 40,
                'nowrap' => true,
            ],
            'equipment_ref' => [
                'title'  => I18N::T('equipments', '仪器编号'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/equipment_ref', ['equipment_ref' => $form['equipment_ref']]),
                    'value' => $form['equipment_ref'] ? H($form['equipment_ref']) : null,
                ],
                'weight' => 45,
                'nowrap' => true,
            ],
            'date'           => [
                'title'     => I18N::T('equipments', '时间'),
                'filter'    => [
                    'form'  => V('equipments:records_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'weight' => 50,
                'invisible' => true,
                'nowrap'    => true,
            ],
            'samples'        => [
                'title'  => I18N::T('equipments', '样品数'),
                'align'  => 'center',
                'weight' => 60,
                'nowrap' => true,
            ],
            'agent'          => [
                'title'  => I18N::T('equipments', '代开'),
                'align'  => 'center',
                'weight' => 70,
                'nowrap' => true,
            ],
            'feedback'       => [
                'title'  => I18N::T('equipments', '反馈'),
                'weight' => 80,
                'nowrap' => true,
            ],
            'description'    => [
                'title'  => I18N::T('equipments', '备注'),
                'weight' => 90,
                'nowrap' => true,
            ],
            'rest'           => [
                'title'  => I18N::T('eq_charge', '操作'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
            ],
            'lock_status'    => [
                'title'     => I18N::T('equipments', '锁定状态'),
                // 'invisible' => true,
                'filter'    => [
                    'value' => isset($form['lock_status']) ? ($form['lock_status'] ? I18N::HT('equipments', '已锁定') : I18N::HT('equipments', '未锁定')) : null,
                ],
                'weight' => 45,
            ],
        ];

        if (Module::is_installed('eq_charge')) {
            $columns['charge_amount'] = [
                'title'  => I18N::T('equipments', '收费金额'),
                'align'  => 'center',
                'nowrap' => true,
                'weight' => 30,
            ];
        }

        $columns = new ArrayObject($columns);
        Event::trigger('eq_record.list.columns', $form, $columns, 'lab_records');
        return (array)$columns;
    }

    public static function list_records_html($records, $form, $tabs)
    {
        $me = L('ME');
        // 分页查找
        $start    = (int) $form['st'];
        $per_page = 15;
        $start    = $start - ($start % $per_page);
        if ($start > 0) {
            $last = floor($records->total_count() / $per_page) * $per_page;
            if ($last == $records->total_count()) {
                $last = max(0, $last - $per_page);
            }

            if ($start > $last) {
                $start = $last;
            }
            $records = $records->limit($start, $per_page);
        } else {
            $records = $records->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'    => $start,
            'per_page' => $per_page,
            'total'    => $records->total_count(),
        ]);

        $search_filters = new ArrayIterator;
        Event::trigger('eq_record.search_filter.view', $form, $search_filters);

        $tabs->content->search_filters = $search_filters;
        $tabs->content->records        = $records;
        $tabs->content->pagination     = $pagination;
    }

    public static function list_records_print($records, $form, $tabs)
    {
        $current_controller         = Controller::$CURRENT;
        $current_controller->layout = Event::trigger('print.equipment.records', $records, $form);
        // 记录日志
        $me = L('ME');
        Log::add(strtr('[equipments] %user_name[%user_id]打印了仪器的使用记录', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
    }

    public static function list_records_csv($records, $form)
    {
        Event::trigger('export.equipment.records', $records);
    }

    public static function _index_follow_equipments_tab($e, $tabs)
    {
        $me   = L('ME');
        $user = $tabs->user;

        if ($me->is_allowed_to('列表关注的仪器', $user)) {
            $count = $user->get_follows_count('equipment');
            $tabs
                ->add_tab('equipment', [
                    'url'   => $user->url('follow.equipment'),
                    'title' => I18N::T('equipments', '仪器 [%count]', ['%count' => $count]),
                ]);
        }
    }

    public static function _index_follow_equipments_content($e, $tabs)
    {
        $user          = $tabs->user;
        $follows       = $user->followings('equipment');
        $extra_follows = Event::trigger('equipment.extra.follows', $follows) ?: [];
        $follows       = count($extra_follows) ? $extra_follows : $follows;

        $start      = (int) Input::form('st');
        $per_page   = 20;
        $pagination = Lab::pagination($follows, $start, $per_page);

        $tabs->content = V('equipments:follow/equipments', [
            'follows'    => $follows,
            'pagination' => $pagination,
        ]);
    }

    public static function user_record_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if ($me->is_allowed_to('列表个人仪器使用记录', $user)) {
            $tabs
                ->add_tab('eq_record', [
                    'url'   => $tabs->user->url('eq_record'),
                    'title' => I18N::T('equipments', '仪器使用记录'), //用户
                ]);
        }
    }

    public static function user_record_content($e, $tabs)
    {
        $user = $tabs->user;

        // 查找某台设备的使用记录
        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['reserv_status'][0] == -1) {
                unset($form['reserv_status'][0]);
            }
            
        });
        
        $selector = 'eq_record';

        $pre_selectors   = [];
        $pre_selectors[] = $user;
        $search          = false;

        if ($form['equipment_name']) {
            $equipment_name  = Q::quote(trim($form['equipment_name']));
            $pre_selectors[] = "equipment[name*=$equipment_name]";
        }

        if ($form['reserv_status'] && !is_array($form['reserv_status'])) {
            if ($form['reserv_status'] == 'abnormal') {
                $form['reserv_status'] = [
                    EQ_Reserv_Model::MISSED => 'on',
                    EQ_Reserv_Model::LATE => 'on',
                    EQ_Reserv_Model::LEAVE_EARLY => 'on',
                    EQ_Reserv_Model::OVERTIME => 'on',
                    EQ_Reserv_Model::LATE_OVERTIME => 'on',
                    EQ_Reserv_Model::LATE_LEAVE_EARLY => 'on'
                ];
            } else {
                $form['reserv_status'] = [
                    $form['reserv_status'] => 'on',
                ];
            }
        }

        if (count((array) $form['reserv_status'])) {
            $reserv_status = $form['reserv_status'];
            $late = EQ_Reserv_Model::LATE;
            $overtime = EQ_Reserv_Model::OVERTIME;
            $leave_early = EQ_Reserv_Model::LEAVE_EARLY;
            $late_leave_early = EQ_Reserv_Model::LATE_LEAVE_EARLY;
            $late_overtime = EQ_Reserv_Model::LATE_OVERTIME;
            if (in_array($late, array_keys($form['reserv_status']))) {
                $reserv_status[$late_overtime] = 'on';
                $reserv_status[$late_leave_early] = 'on';
            }
            if (in_array($overtime, array_keys($form['reserv_status']))) {
                $reserv_status[$late_overtime] = 'on';
            }
            if (in_array($leave_early, array_keys($form['reserv_status']))) {
                $reserv_status[$late_leave_early] = 'on';
            }
            $flag = Q::quote(array_keys($reserv_status));
            $selector .= "[flag={$flag}]";
        }

        // 按时间搜索
        if ($form['dtstart']) {
            $form['dtstart'] = Date::get_day_start($form['dtstart']);
            $dtstart         = Q::quote($form['dtstart']);
            $selector .= "[dtend>=$dtstart]";
        }

        if ($form['dtend']) {
            $form['dtend'] = Date::get_day_end($form['dtend']);
            $dtend         = Q::quote($form['dtend']);
            $selector .= "[dtend>0][dtend<=$dtend]";
        }

        if (isset($form['lock_status']) && $form['lock_status'] != -1) {
            $is_locked = !!$form['lock_status'] ? 1 : 0;
            $selector .= "[is_locked=$is_locked]";
        }

        if (!empty($form)) {
            $search = true;
        }

        $now = time();
        $selector .= "[dtstart<=$now]";

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', $pre_selectors) . ') ' . $selector;
        }

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        // Hook
        $new_selector = Event::trigger('eq_record.search_filter.submit', $form, $selector);

        if (!is_null($new_selector)) {
            $selector = $new_selector;
        }

        //分页查找
        $start    = (int) $form['st'];
        $per_page = 15;
        $start    = $start - ($start % $per_page);

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        $sort_str  = ':sort(dtstart D)';

        $new_sort_str = Event::trigger('eq_record.sort_str_factory', $form, $sort_str, $type);
        if (null !== $new_sort_str) {
            $sort_str = $new_sort_str;
        }

        $selector .= $sort_str;

        $records = Q($selector);

        //生成 session token
        $tabs->form_token = $form_token = Session::temp_token('records_', 300);

        //将搜索条件存入session
        $_SESSION[$form_token] = ['selector' => $selector, 'form' => $form];

        $pagination = Lab::pagination($records, $start, $per_page);

        $fields        = self::get_records_fields($form);
        $tabs->columns = new ArrayObject($fields);

        $tabs->content = V('equipments:user_records', [
            'panel_buttons' => $panel_buttons,
            'user'          => $user,
            'records'       => $records,
            'columns'       => $tabs->columns,
            'pagination'    => $pagination,
            'form'          => $form,
            'search'        => $search,
        ]);
    }

    public static function get_records_fields($form, $search_box_need_param = [])
    {
        $me = L('me');

        if (is_array($search_box_need_param)) {
            extract($search_box_need_param);
        }

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }
        
        
        $columns = [
            'checkbox' => [
                'center' => true,
            ],
            'serial_number'  => [
                'title'  => I18N::T('equipments', '编号'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/serial_number', ['id' => $form['id']]),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'nowrap' => true,
                'weight' => 10,
            ],
            'lock_status'   => [
                'nowrap' => true,
                'weight' => 20,
            ],
            'equipment_name' => [
                'title'  => I18N::T('equipments', '仪器'),
                'align'  => 'left',
                'filter' => [
                    'form'  => V('equipments:records_table/filters/equipment_name', ['equipment_name' => $form['equipment_name']]),
                    'value' => $form['equipment_name'] ? H($form['equipment_name']) : null,
                ],
                'nowrap' => true,
                'weight' => 30,
            ],
            'status'         => [
                'title'     => I18N::T('equipments', '状态'),
                'filter'    => [
                    'form'   => V('equipments:records_table/filters/status', ['form_reserv_status' => $form['reserv_status']]),
                    'value'  => $form['reserv_status'] ? (implode(', ', array_map(function ($k) {
                        return EQ_Reserv_Model::$reserv_status[$k] == '正常使用' ? '正常' : EQ_Reserv_Model::$reserv_status[$k];
                    }, array_keys($form['reserv_status'])))) : '',
                    'field'  => 'reserv_status',
                    'nowrap' => false,
                ],
                'invisible' => true,
            ],
            'date'           => [
                'title'     => I18N::T('equipments', '时间'),
                'filter'    => [
                    'form'  => V('equipments:records_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'invisible' => true,
                'nowrap'    => true,
            ],
            'samples'        => [
                'title'  => I18N::T('equipments', '样品数'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 40,
            ],
            'agent'          => [
                'title'  => I18N::T('equipments', '代开'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 15,
            ],
            'description'    => [
                'title'       => I18N::T('equipments', '备注'),
                'invisible'   => false,
                'nowrap'      => true,
                'weight'      => 70,
            ],
            'rest'           => [
                'title'  => I18N::T('equipments', '操作'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 100,
            ],
            'lock_status'    => [
                'title'     => I18N::T('equipments', '锁定状态'),
                //'invisible' => true,
                'filter'    => [
                    'form'  => V('equipments:records_table/filters/lock_status', ['lock_status' => $form['lock_status']]),
                    'value' => $form['lock_status'] == 1 ? I18N::HT('equipments', '未锁定') : ($form['lock_status'] == 0 ? I18N::HT('equipments', '已锁定') : null),
                ],
                'weight'    => 90,
            ],
        ];
        if(Module::is_installed('eq_evaluate') || Module::is_installed('eq_comment')){
            unset($columns['checkbox']); 
        }
        if (Module::is_installed('eq_charge')) {
            $columns['charge_amount'] = [
                'title'  => I18N::T('equipments', '收费金额'),
                // 'align'  => 'center',
                'nowrap' => true,
                'weight' => 45,
            ];
        }
        $columns = new ArrayObject($columns);
        Event::trigger('eq_record.list.columns', $form, $columns, 'user_records');
        return (array)$columns;

    }

    public static function feedback_status($e, $status, $eq_record)
    {
        if ($status == EQ_Record_Model::FEEDBACK_PROBLEM) {
            $eq_status              = O('eq_status');
            $eq_status->dtstart     = time();
            $eq_status->status      = EQ_Status_Model::OUT_OF_SERVICE;
            $eq_status->equipment   = $eq_record->equipment;
            $eq_status->description = T('%name提交了仪器故障报告"%description"', ['%name' => H($eq_record->user->name), '%description' => H($eq_record->feedback)]);
            $eq_status->save();
            $eq_record->equipment->status = EQ_Status_Model::OUT_OF_SERVICE;
            $eq_record->equipment->save();
        }
    }

    public static function cannot_access_equipment($e, $equipment, $params)
    {

        $me = $params[0];

        if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '仪器故障，不可使用仪器'));
            $e->return_value = true;
            return false;
        }

        //你实验室信息有误，无法使用设备。
        if (!Q("$me lab")->total_count()) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您没有指定的实验室，不可使用仪器。'));
            $e->return_value = true;
            return false;
        } elseif (class_exists('Lab_Project_Model')) {
            $status = Lab_Project_Model::STATUS_ACTIVED;
            if (Config::get('eq_record.must_connect_lab_project')) {
                $projects = [];
                foreach (Q("$me lab") as $lab) {
                    $projects = array_merge($lab->get_project_items() ? : [], $projects);
                }
                if (!count($projects)) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您所在的实验室没有项目, 不可使用仪器.'));
                    $e->return_value = true;
                    return false;
                }
            }
        }
        //该设备需要通过培训后方能使用。
        if ($equipment->require_training) {
            if (!L('skip_training_check')) {
                $training = O('ue_training', ['user' => $me, 'equipment' => $equipment, 'status' => UE_Training_Model::STATUS_APPROVED]);
                if (!$training->id) {
                    if (!Q("{$me} {$equipment}.incharge")->total_count()) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您还未通过培训, 请申请该仪器培训'));
                        $e->return_value = true;
                        return false;
                    }
                }
            }
        }
        $deadline = (int) Config::get('equipment.feedback_deadline', 1);
        if ($deadline >= 0) {
            $now = min(Date::time(), $params[1]) - 86400 * $deadline;
            //用户没有填写一天最近一次使用的反馈信息
            if (Q("eq_record[dtend>0][dtend<=$now][!status][user={$me}]")->total_count() > 0) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您未提交使用反馈信息, 不可使用任何仪器'));
                $e->return_value = true;
                return false;
            }
        }
    }

    public static function nofeedback_record($e, $user, $equipment = null)
    {

        if ($user->id && $user->is_active() && !$user->is_allowed_to('管理使用', $equipment)) {
            $now = time();
            if ($equipment->id) {
                $e->return_value = Q("eq_record[equipment={$equipment}][user={$user}][dtend>0][dtend<$now][status=0]:sort(dtend D):limit(1)")->current();
            } else {
                $e->return_value = Q("eq_record[user={$user}][dtend][dtend<$now][status=0]:sort(dtend D):limit(1)")->current();
            }
        }
        return false;
    }

    public static function on_record_saved($e, $record, $old_data, $new_data)
    {
        $equipment = $record->equipment;
        $user      = $record->user;
        $incharges = Q("$equipment user.incharge");

        //用户提交仪器故障
        if ($new_data['status'] == -1) {
            $users = [];
            foreach ($incharges as $incharge) {
                Notification::send('equipments.report_problem', $incharge, [
                    '%incharge'  => Markup::encode_Q($incharge),
                    '%user'      => Markup::encode_Q($user),
                    '%equipment' => Markup::encode_Q($equipment),
                    '%report'    => $record->feedback,
                ]);
            }
        }
        //非自由使用仪器人员
        elseif (!$user->is_allowed_to('管理使用', $equipment)) {

            // 记录是否是已完成
            $now = time();
            if ($record->dtend > 0 && $record->dtend <= $now) {

                // 第一次修改
                if ($new_data['dtend'] | $new_data['user']) {

                    // 对未填写反馈的用户发送通知
                    if (!$record->status) {
                        Notification::send('equipments.nofeedback', $user, [
                            '%user'      => Markup::encode_Q($user),
                            '%equipment' => Markup::encode_Q($equipment),
                        ]);
                    }

                }

            }
        }

    }

    public static function on_achievement_validate($e, $form)
    {
        if (!$form['equipments'] || !count(json_decode($form['equipments'], true))) {
            $form->set_error('equipments', I18N::T('achievements', '请选择关联仪器后提交'));
        }
    }

    public static function on_achievement_edit($e, $object, $form)
    {
        if ($form->no_error) {
            $labs = Q("{$object} lab");
        } else {
            $labs = Q("lab[id={$form['lab']}]");
        }
        $e->return_value .= V('equipments:equipment/achievements_equipment', [
            'form'   => $form,
            'labs'   => $labs,
            'object' => $object,
        ]);
    }
    public static function before_record_save($e, $record, $new_data)
    {

        if ($new_data['dtend']) {
            //调整
            $equipment = $record->equipment;
            $dtstart   = $record->dtstart;
            $dtend     = $record->dtend;

            $rid     = $record->id;
            $records = Q("eq_record[equipment={$equipment}][id!=$rid]");

            if ($dtstart && $dtend && $records->total_count()) {

                //Q在find的时候，无法正常使用$records->find("[dtstart={$dtstart}~{$dtend}]|[dtend={$dtstart}~{$dtend}]")->total_count()进行获取
                //故进行拆分
                if ($records->find("[dtstart={$dtstart}~{$dtend}]")->total_count() || $records->find("[dtend={$dtstart}~{$dtend}]")->total_count() || $records->find("[dtend>={$dtend}][dtstart<={$dtstart}]")->total_count()) {

                    foreach (Q("{$equipment} user.incharge") as $incharge) {

                        $subject = I18N::HT('equipments', '提醒: 在您负责的[%equipment]下, 系统添加一条使用记录失败!', ['%equipment' => $equipment->name]);

                        Notification::send("#VIEW#|equipments:record/cover_email", $incharge, [
                            '#TITLE#' => $subject,
                            'incharge' => "[[Q:{$incharge}]]",
                            'equipment' => "[[Q:{$record->equipment}]]",
                            'user' => "[[Q:{$record->user}]]",
                            'dtstart' => $record->dtstart,
                            'dtend' => $record->dtend,
                            'samples' => $record->samples,
                        ]);
                    }

                    //删除该条错误记录
                    $record->delete();

                    //提前结束save
                    $e->return_value = false;
                    return false;
                }
            }

            // 用于避免保存的使用记录
            static $saving_records = [];
            if ($rid) {
                $saving_records[$rid]++;
            }

            if ($dtstart > 0) {
                foreach ($records->find("[dtstart~dtend=$dtstart]") as $r) {
                    // 检查正在保存的记录中存不存在冲突, 避免死循环
                    if (isset($saving_records[$r->id])) {
                        continue;
                    }

                    $r->dtend = $dtstart - 1;
                    $r->save();
                }
            }

            if ($dtend > 0) {
                foreach ($records->find("[dtstart~dtend=$dtend]") as $r) {
                    // 检查正在保存的记录中存不存在冲突, 避免死循环
                    if (isset($saving_records[$r->id])) {
                        continue;
                    }

                    $r->dtstart = $dtend + 1;
                    $r->save();
                }
            }

            if ($rid) {
                $saving_records[$rid]--;
                if ($saving_records[$rid] == 0) {
                    unset($saving_records[$rid]);
                }
            }

        }

        //当修改仪器使用记录为锁定时间之前, 并且该使用记录未反馈, 则自动反馈
        if ($record->dtend && $record->dtend <= Lab::get('transaction_locked_deadline', 0) && $record->status == EQ_Record_Model::FEEDBACK_NOTHING) {
            $record->status   = EQ_Record_Model::FEEDBACK_NORMAL;
            $record->feedback = I18N::T('equipments', '系统锁定记录时自动对记录进行反馈!');
        }

    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }
        //取消现默认赋予给pi的权限
//        if (Q("$user<pi lab")->total_count()) {
//            $perms['查看负责实验室成员的仪器使用情况'] = 'on';
//        }
    }

    public static function on_training_saved($e, $training, $old_data, $new_data)
    {
        $me = L('ME');
        if ($new_data['status'] != $old_data['status']) {
            $equipment = $training->equipment;
            $user = $training->user;
            $desc = $training->description ?: '';
            switch ($new_data['status']) {
                case UE_Training_Model::STATUS_APPROVED:
                    Notification::send('equipments.incharge_training_approved', $user, [
                        '%incharge'  => Markup::encode_Q($me),
                        '%user'      => Markup::encode_Q($user),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%desc' => $desc
                    ]);
                    break;

                case UE_Training_Model::STATUS_APPLIED:
                case UE_Training_Model::STATUS_AGAIN:
                    $incharges = Q("$equipment user.incharge");
                    $users     = [];
                    foreach ($incharges as $incharge) {
                        Notification::send('equipments.incharge_training_apply', $incharge, [
                            '%incharge'  => Markup::encode_Q($incharge),
                            '%user'      => Markup::encode_Q($user),
                            '%equipment' => Markup::encode_Q($equipment),
                            '%desc' => $desc
                        ]);
                    }
                    break;
                case UE_Training_Model::STATUS_REFUSE:
                    Notification::send('equipments.incharge_training_rejected', $user, [
                        '%incharge'  => Markup::encode_Q($me),
                        '%user'      => Markup::encode_Q($user),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%desc' => $desc
                    ]);
                    break;
            }
        }
    }

    public static function on_training_delete($e, $training)
    {
        $equipment = $training->equipment;
        $user      = $training->user;

        $incharges = [];

        foreach (Q("{$equipment} user.incharge") as $i) {
            $incharges[] = Markup::encode_Q($i);
        }

        Notification::send('equipments.training_deleted', $user, [
            '%incharge'  => join(', ', $incharges),
            '%user'      => Markup::encode_Q($user),
            '%equipment' => Markup::encode_Q($equipment),
        ]);
    }

    public static function on_achievements_saved($e, $form, $object)
    {
        $equipments = Q("{$object} equipment");
        foreach ($equipments as $equipment) {
            $object->disconnect($equipment);
        }

        if ($form['equipments']) {
            $eq = is_array($form['equipments']) ? $form['equipments'] : json_decode($form['equipments']);
            foreach ($eq as $key => $q) {
                $equipment = O('equipment', $key);

                if ($equipment->id) {
                    $object->connect($equipment);
                }
            }
        }

    }

    public static function project_equipments_get($e, $object, $lab, $project, $form = [])
    {
        $e->return_value = V('equipments:equipment/ac_project_equipment', [
            'project' => $project,
            'object'  => $object,
            'form'    => $form,
        ]);
    }

    //当achievement删除后 将关系删除
    public static function before_equipment_relation_delete($e, $object)
    {
        $equipments = Q("{$object} equipment");
        foreach ($equipments as $equipment) {
            $object->disconnect($equipment);
        }
    }

    public static function get_update_parameter($e, $object, array $old_data = [], array $new_data = [])
    {
        if ($object->name() != 'equipment' || !$old_data) {
            return;
        }

        $difference     = array_diff_assoc($new_data, $old_data);
        $old_difference = array_diff_assoc($old_data, $new_data);
        $info_keys      = array_keys(Equipments::$equipment_info);
        $photo_keys     = array_keys(Equipments::$equipment_photo);
        $status_keys    = array_keys(Equipments::$equipment_status);
        $use_keys       = array_keys(Equipments::$equipment_use);
        $arr            = array_keys($difference);
        $data           = $e->return_value;
        if (!count($difference)) {
            $e->return_value = $data;
            return;
        }
        $delta = [];
        if (count(array_intersect($info_keys, $arr))) {
            $delta['action'] = 'edit_info';
            if (in_array('manu_date', $arr)) {
                $difference['manu_date'] = date('Y/m/d', $difference['manu_date']);
            }
            if (in_array('purchased_date', $arr)) {
                $difference['purchased_date'] = date('Y/m/d', $difference['purchased_date']);
            }
            if (in_array('group', $arr)) {
                $difference['group'] = $difference['group']->name;
            }
            /*
        if (in_array('contact', $arr)) {
        $contact = $difference['contact'];
        if ($contact->id) {
        $difference['contact'] = Markup::encode_Q($contact);
        }
        else {
        unset($difference['contact']);
        }
        }
         */

        } elseif (count(array_intersect($photo_keys, $arr))) {
            $delta['action'] = 'edit_photo';
        } elseif (count(array_intersect($status_keys, $arr))) {
            $delta['action']      = 'edit_status';
            $difference['status'] = EQ_Status_Model::$status[$difference['status']];
        } elseif (count(array_intersect($use_keys, $arr))) {
            $delta['action'] = 'edit_use';
            if (in_array('require_training', $arr)) {
                $difference['require_training'] =
                $difference['require_training'] ? '是' : '否';
            }
            if (in_array('control_mode', $arr)) {
                $mode = $difference['control_mode'];
                if ($mode == 'power') {
                    $difference['control_mode'] = '电源控制';
                } elseif ($mode == 'computer') {
                    $difference['control_mode'] = '电脑控制';
                } else {
                    $difference['control_mode'] = '不控制';
                }
            }
        } else {
            return;
        }
        $subject           = L('ME');
        $delta['subject']  = $subject;
        $delta['object']   = $object;
        $delta['new_data'] = $difference;
        $delta['old_data'] = $old_difference;

        $key        = Misc::key((string) $subject, $delta['action'], (string) $object);
        $data[$key] = (array) $data[$key];

        Misc::array_merge_deep($data[$key], $delta);
        $e->return_value = $data;
    }

    static $equipment_info = [
        'name'           => '名称',
        'model_no'       => '型号',
        'specification'  => '规格',
        'price'          => '价格',
        'manu_at'        => '制造国家',
        'manufacturer'   => '生产厂家',
        'manu_date'      => '出厂日期',
        'purchased_date' => '购置日期',
        'group'          => '所属单位',
        'cat_no'         => '分类号',
        'ref_no'         => '编号',
        'location'       => '放置房间',
        'tech_specs'     => '主要规格及技术指标',
        'features'       => '主要功能及特色',
        'configs'        => '主要附件及配置',
        'incharges'      => '负责人',
        //'contact'        =>        '联系人',
        'tags'           => '仪器分类',
    ];

    static $equipment_photo = [
        'mtime' => '图像',
    ];

    static $equipment_status = [
        'status' => '当前状态',
    ];

    static $equipment_use = [
        'require_training' => '是否需要培训',
        'control_mode'     => '控制方式',
    ];

    static $actions = [
        'edit_info',
        'edit_photo',
        'edit_status',
        'edit_use',
    ];

    public static function get_update_message($e, $update)
    {
        if ($update->object->name() !== 'equipment') {
            return;
        }

        $me       = L('ME');
        $subject  = $update->subject->name;
        $old_data = json_decode($update->old_data, true);
        $object   = $old_data['name'] ? $old_data['name'] : $update->object->name;
        /*
        if ($me->id == $update->subject->id) {
        $subject = I18N::T('equipments', '我');
        }
         */

        switch ($update->action) {
            case 'edit_info':
                $config = 'equipment.info.msg.model';
                break;
            case 'edit_status':
                $config = 'equipment.status.msg.model';
                break;
            case 'edit_photo':
                $config = 'equipment.photo.msg.model';
                break;
            case 'edit_use':
                $config = 'equipment.use.msg.model';
                break;
            default:
                return;

        }
        $opt = Lab::get($config, Config::get($config));
        $msg = I18N::T('equipments', $opt['body'], [
            '%subject'   => URI::anchor($update->subject->url(), H($subject), 'class="blue label"'),
            '%date'      => '<strong>' . Date::fuzzy($update->ctime, 'TRUE') . '</strong>',
            '%equipment' => URI::anchor($update->object->url(), H($object), 'class="blue label"'),
        ]);
        $e->return_value = $msg;
        return false;
    }

    public static function get_update_message_view($e, $update)
    {
        $actions = Equipments::$actions;
        $action  = $update->action;
        if (in_array($action, $actions)) {
            if ($action == 'edit_info') {
                $properties = Equipments::$equipment_info;
            } elseif ($action == 'edit_photo') {
                return;
            } elseif ($action == 'edit_status') {
                $properties = Equipments::$equipment_status;
            } elseif ($action == 'edit_use') {
                $properties = Equipments::$equipment_use;
            }
            $e->return_value = V('equipments:update/show_msg', ['update' => $update, 'properties' => $properties]);
            return false;
        }
    }

    //传入参数$object为equipment
    public static function equipment_ACL($e, $me, $perm_name, $object, $options)
    {
        // 查看的权限因为移到报废的判断之上，否则会出问题的。
        if ($perm_name == '查看') {
            if (
                Config::get('equipment.enable_share') &&
                Config::get('equipment.enable_show_list_share')
            ) {
                if ($object->id && $object->cers_share) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                if ($object->id && Equipments::user_is_eq_incharge($me, $object)) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
            } else {
                $e->return_value = true;
                return true;
            }
        }

        if ($object->id && $object->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }

        if ($options) {
            $ignores = $options['@ignore'];
            if (!is_array($ignores)) {
                $ignores = [$ignores];
            }

            $ignore = in_array('修改下属机构的仪器', $ignores) ? true : false;
        }
        switch ($perm_name) {
            case '列表':
                $e->return_value = true;
                return false;
                break;
            case '修改使用设置':
                if (Equipment_ACL::use_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '锁定基本':
                if ($me->access('添加/修改所有机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改标签':
                if (Equipment_ACL::edit_tag_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '管理培训':
                if (Equipment_ACL::training_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '添加':
                // 判断是否达到最大仪器数量限制(xiaopei.li@2012-02-09)
                if (isset($GLOBALS['preload']['equipment.max_number']) &&
                    Q('equipment')->total_count() >= $GLOBALS['preload']['equipment.max_number']) {
                    $e->return_value = false;
                    return false;
                }
                if (Equipment_ACL::equipment_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '导出':
                if ($me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                $e->return_value = false;
                return false;
                break;
            case '删除':
            case '修改基本信息':
                if (Equipment_ACL::equipment_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '提交修改':
                if ($me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                if (Equipment_ACL::equipment_is_allowed($me, $object, $perm_name, $options) && !$object->info_lock) {
                    $e->return_value = true;
                    return false;
                }
                if ($object->id && !$me->access('添加/修改所有机构的仪器')
                    && $object->info_lock) {
                    $e->return_value = false;
                    return false;
                }
                break;
            case '修改':
                if ($me->is_allowed_to('修改基本信息', $object) ||
                    $me->is_allowed_to('修改使用设置', $object) ||
                    $me->is_allowed_to('修改标签', $object) ||
                    $object->status != EQ_Status_Model::NO_LONGER_IN_SERVICE && $me->is_allowed_to("修改仪器状态设置", $object)) {
                    // 以上判断参考controller/equipment.php edit方法内部的判断(即有哪些编辑tab)
                    // (xiaopei.li@2011.09.07)
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改组织机构':
                //仪器负责人有修改仪器组织机构的权限
                if ($object->id && Equipments::user_is_eq_incharge($me, $object)) {
                    $e->return_value = true;
                    return false;
                }

                //具有修改所有仪器，则可修改仪器组织机构
                if ($me->access('添加/修改所有机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }

                //具有修改下属机构的仪器、并且仪器的组织机构为me的组织机构或子组织机构。或者新增一个仪器，则可以修改组织机构
                if (!$ignore
                    && $me->access('添加/修改下属机构的仪器')
                    && $me->group->id
                    && ($me->group->is_itself_or_ancestor_of($object->group) || !$object->id)) {
                    $e->return_value = true;
                    return false;
                }
            case '查看相关人员联系方式':
                if (!$object->id) {
                    return false;
                }

                if (self::user_is_eq_incharge($me, $object) || self::user_is_eq_contact($me, $object) || $me->access('查看所有成员的联系方式')) {
                    //如果为联系人或者为负责人，则true
                    $e->return_value = true;
                }
                break;
            case '共享':
                if (Config::get('equipment.enable_share')) {
                    if (Config::get('equipment.share_need_admin') && !$me->access('管理所有内容')) {
                        $e->return_value = false;
                        return false;
                    }
                    $e->return_value = true;
                    return false;
                }
                break;
            case '进驻仪器控':
                if (Module::is_installed('yiqikong')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '隐藏':
                if ($me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                $e->return_value = false;
                return false;
                break;
            case '修改代开者':
                if ($me->access('添加/修改所有机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('添加/修改下属机构的仪器') && $me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    //检查用户是否是仪器的负责人
    public static function user_is_eq_incharge($user, $equipment)
    {
        if ($equipment->id && $user->id && Q("{$equipment} user.incharge[id=$user->id]")->total_count() > 0) {
            return true;
        }

        return false;
    }

    //检查用户是否是仪器的联系人
    public static function user_is_eq_contact($user, $equipment)
    {
        if ($equipment->id && $user->id && Q("{$equipment} user.contact[id=$user->id]")->total_count() > 0) {
            return true;
        }
        return false;
    }

    //传入对象$object为equipment
    public static function equipment_attachments_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($options['type'] != 'attachments') {
            return;
        }

//        if (self::user_is_eq_incharge($me, $object)) {
//            $e->return_value = true;
//            return false;
//        }
        $is_charge = self::user_is_eq_incharge($me, $object);

        switch ($perm_name) {
            case '列表文件':
                if ($me->access('查看所有仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                // bug: 24744（3）17Kong/Sprint-285：lims3.3全面测试：赋予院级管理员上传/创建下属机构仪器的附件，院级管理员查看下属机构仪器，看不到附件页卡
                if ($me->access('上传/创建下属机构仪器的附件') && $object->group->is_itself_or_ancestor_of($me->group)) {
                    $e->return_value = true;
                    return false;
                }
                if ($is_charge && $me->access('查看负责仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('查看下属机构仪器的附件')
                    && $me->group->id && $object->group->id
                    && $me->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '下载文件':
                if ($me->access('下载所有仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($is_charge && $me->access('下载负责仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('下载下属机构仪器的附件') && $object->group->is_itself_or_ancestor_of($me->group)) {
                    $e->return_value = true;
                    return false;
                }
                return false;
                break;
            case '上传文件':
            case '创建目录':
                if ($me->access('上传/创建所有仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($is_charge && $me->access('上传/创建负责仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('上传/创建下属机构仪器的附件') && $object->group->is_itself_or_ancestor_of($me->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改文件':
            case '修改目录':
            case '删除文件':
            case '删除目录':
                if ($me->access('更改/删除所有仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($is_charge && $me->access('更改/删除负责仪器的附件')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('更改/删除下属机构仪器的附件') && $object->group->is_itself_or_ancestor_of($me->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    public static function lab_equipments_records_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($perm_name == '列表仪器使用记录') {
            if (($object->id && Q("$me $object")->total_count() && $me->access('查看本实验室成员的仪器使用情况'))
                || ($object->id && Q("$me<pi $object")->total_count() && $me->access('查看负责实验室成员的仪器使用情况'))
                || $me->access('管理所有内容')
                || $me->access('查看下属机构实验室的仪器使用记录') && $me->group->is_itself_or_ancestor_of($object->group)
            ) {
                $e->return_value = true;
                return false;
            }

            //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价,此处直接返回即可。
            $access = Event::trigger("feedback.need_evaluate_by_source", null, 'ignore');
            if (true === $access) {
                $record = Q("eq_record[user=$me][dtend>=1541088000][dtend<=$now][status!=0][evaluate_id=0]:sort(dtend D):limit(1)")->current();
                if ($record->id) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您未提交使用反馈信息, 不可使用任何仪器'));
                    $e->return_value = true;
                    return false;
                }
            }

        }
    }

    public static function all_equipments_records_ACL($e, $me, $perm_name, $object, $options)
    {

        if ($me->access('查看所有仪器的使用记录')) {
            $e->return_value = true;
            return false;
        }
    }
    public static function group_equipments_records_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($me->access('查看下属机构的仪器使用记录') && $me->group->id) {
            $e->return_value = true;
            return false;
        }
    }
    public static function incharge_equipments_records_ACL($e, $me, $perm_name, $object, $options)
    {
        $eqs_incharge = Q("{$me} equipment.incharge");
        if (count($eqs_incharge)) {
            $e->return_value = true;
            return false;
        }
    }

    //传入对象$object为equipment
    public static function equipment_records_ACL($e, $me, $perm_name, $object, $options)
    {

        $equipment = $object;

        $is_incharge = self::user_is_eq_incharge($me, $equipment);

        switch ($perm_name) {
            case '列表仪器使用记录':

                if ($is_incharge && $me->access('查看负责仪器的使用记录')) {
                    $e->return_value = true;
                    return false;
                }

                if ($me->access('查看所有仪器的使用记录')) {
                    $e->return_value = true;
                    return false;
                }

                //仪器所属组织机构的负责人可以查看仪器的使用记录
                $my_group   = $me->group;
                $has_access = $me->access('查看下属机构的仪器使用记录');
                if ($equipment->id) {
                    $eq_group = $equipment->group;
                    if ($eq_group->id && $has_access && $my_group->is_itself_or_ancestor_of($eq_group)) {
                        $e->return_value = true;
                        return false;
                    }
                }
                break;
            case '列表仪器考试记录':
                if ($is_incharge || $me->access('管理所有内容')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '添加仪器使用记录':
            case '修改仪器使用记录':
                if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
                    $e->return_value = false;
                    return false;
                }
                if ($me->access('修改所有仪器的使用记录')) {
                    $e->return_value = true;
                    return false;
                }
                if ($is_incharge && $me->access('修改负责仪器的使用记录')) {
                    $e->return_value = true;
                    return false;
                }
                $my_group   = $me->group;
                $has_access = $me->access('修改下属机构仪器的使用记录');
                if ($equipment->id) {
                    $eq_group = $equipment->group;
                    if ($eq_group->id && $has_access && $my_group->is_itself_or_ancestor_of($eq_group)) {
                        $e->return_value = true;
                        return false;
                    }
                }
                break;
            case '管理使用':
                //如果用户未激活，则不可使用仪器
                if (!$me->is_active()) {
                    $e->return_value = false;
                    return false;
                }
                /*
                如果仪器处于故障状态，依旧可以让其有权限的人进行操作
                if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
                $e->return_value = FALSE;
                return FALSE;
                }
                 */

                if (in_array($me->token, (array) Config::get('equipment.free_access_users'))) {
                    $e->return_value = true;
                    return false;
                }

                if ($is_incharge || $me->access('修改所有仪器的使用记录')) {
                    $e->return_value = true;
                    return false;
                }

                $my_group   = $me->group;
                $has_access = $me->access('修改下属机构仪器的使用记录');
                if ($equipment->id) {
                    $eq_group = $equipment->group;
                    if ($eq_group->id && $has_access && $my_group->is_itself_or_ancestor_of($eq_group)) {
                        $e->return_value = true;
                        return false;
                    }
                }

                break;
        }
    }

    //传入对象$object为user
    public static function user_ACL($e, $me, $perm_name, $object, $options)
    {

        switch ($perm_name) {
            case '列表关注':
            case '列表关注的仪器':
                /*
                NO.BUG#310(guoping.zhang@2010.12.28)
                添加数量判断
                 */
                if ($object->get_follows_count('equipment') == 0) {
                    return;
                }

                if (Equipment_ACL::follow_equipment_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            /*
            BUG#237 (Cheng.Liu@2010.12.14)
            列表个人使用记录修改为 列表个人仪器使用记录
             */
            case '列表个人仪器使用记录':
                if (Equipment_ACL::list_user_records_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '关注':
                $e->return_value = true;
                return false;
                break;
            case '取消关注':
                $e->return_value = true;
                return false;
                break;
            default:
                break;
        }
    }

    //传入对象$object为eq_record
    public static function record_ACL($e, $me, $perm_name, $object, $options)
    {
        $equipment = $object->equipment;
        if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }

        switch ($perm_name) {
            case '删除':
                if ($object->id && !$object->dtend) {
                    $e->return_value = false;
                    return false;
                }
            case '锁定送样数':
            case '修改':
                $time = Lab::get('transaction_locked_deadline');

                /* Deadline 时间限制, 在transaction_locked_deadline范围内的记录将不予修改 */
                $dtstart = $object->dtstart;
                $dtend   = $object->dtend;

                $edit_record_before_deadline = $dtend && $dtend <= $time;

                //虽然被锁定时段内, 但是该使用记录为使用中
                //则可被修改
                if ($edit_record_before_deadline && $object->get('dtend', true)) {
                    $e->return_value = false;
                    return false;
                }

                if ($object->is_locked()) {
                    $e->return_value = false;
                    return false;
                }

                if ($me->is_allowed_to('修改仪器使用记录', $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '锁定':
                /* 如果该仪器没有被反馈, 则不能锁定该记录 */
                if ($object->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                    $e->return_value = false;
                    return false;
                }

                if ($me->is_allowed_to('修改仪器使用记录', $equipment)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '反馈':

                $time = Lab::get('transaction_locked_deadline');

                /* Deadline 时间限制, 在transaction_locked_deadline范围内的记录将不能反馈*/
                $dtend = $object->dtend;

                $edit_record_before_deadline = $dtend && $dtend <= $time;

                if ($edit_record_before_deadline) {
                    $e->return_value = false;
                    return false;
                }

                /* 如果记录被锁定的情况下, 任何人都不得对该记录进行反馈 */
                if ($object->is_locked('feedback')) {
                    $e->return_value = false;
                    return false;
                }

                if ($object->dtend > 0 && $object->dtend <= time()) {
                    /* 记录只要被反馈过, 无论是被谁进行了反馈, 普通用户都不得对其进行反馈 */
                    if ($me->id == $object->user->id && $object->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                        $e->return_value = true;
                        return false;
                    }

                    if ($me->is_allowed_to('修改仪器使用记录', $equipment)) {
                        $e->return_value = true;
                        return false;
                    }

                    //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价
                    $access = Event::trigger("feedback.need_evaluate_by_source",$object);
                    if(true === $access){
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                
                $my_group = $me->group;
                $has_access = $me->access('修改下属机构仪器的使用记录');
                if ($equipment->id) {
                    $eq_group = $equipment->group;
                    if ($eq_group->id && $has_access && $my_group->is_itself_or_ancestor_of($eq_group)) {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                break;
            case '修改代开者':
                if ($me->access('添加/修改所有机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }
                if ($me->access('添加/修改下属机构的仪器') && $me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '修改样品数':
            case '修改开始时间':
                $e->return_value = true;
                return false;
                break;
            case '修改结束时间':
                if (!$object->id || $object->dtend || L('ME')->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }

    public static function create_temp_user_ACL($e, $me, $perm_name, $object, $options)
    {
        $equipment   = $object;
        $is_incharge = self::user_is_eq_incharge($me, $object);
        switch ($perm_name) {
            case '管理仪器临时用户':
                if ($me->access('管理所有仪器的临时用户')) {
                    $e->return_value = true;
                    return false;
                }

                if ($me->access('管理下属机构仪器的临时用户') && $me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }

                if ($is_incharge && $me->access('管理负责仪器的临时用户')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
        }
    }

    /*
    NO.BUG#264(guoping.zhang@2010.12.22)
    修改仪器的状态设置权限设置
    $object:equipment对象
     */
    public static function status_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($object->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }

        switch ($perm_name) {
            case '修改仪器状态设置':
                if (self::user_is_eq_incharge($me, $object) && $me->access('修改负责仪器的状态设置')) {
                    $e->return_value = true;
                    return false;
                }

                if ($me->access('修改所有仪器的状态设置')) {
                    $e->return_value = true;
                    return false;
                }

                if ($me->group->id && $me->access('修改下属机构仪器的状态设置') && $me->group->is_itself_or_ancestor_of($object->group)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '报废仪器':

                if ($me->is_allowed_to('修改仪器状态设置', $object) && (self::user_is_eq_incharge($me, $object) && $me->access('报废负责仪器') || $me->access('报废所有仪器') || ($me->access('报废下属机构的仪器') && $me->group->is_itself_or_ancestor_of($object->group)))) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }
    /* (xiaopei.li@2011.04.11) */
    public static function announce_ACL($e, $me, $perm_name, $equipment, $options)
    {
        if ($me->is_allowed_to('修改', $equipment)) {
            $e->return_value = true;
        }
        /*
        if (self::user_is_eq_incharge($me, $object)) {
        $e->return_value = TRUE;
        }
         */
        return false;
    }

    public static function equipments_list_ACL($e, $me, $perm_name, $equipment, $options)
    {
        $e->return_value = true;
        return false;
    }

    public static function get_equipment_simple_info($e, $equipment)
    {
        if (!$equipment->id) {
            $e->return_value = false;
            return false;
        }

        $e->return_value = (string) V('equipments:equipments_table/data/simple_info', ['equipment' => $equipment]);
        return false;
    }

    public static function notif_classification_enable_callback($user)
    {
        return Q("equipment $user.incharge")->total_count();
    }

    public static function equipment_newsletter_content($e, $user)
    {

        $templates = Config::get('newsletter.template');
        $dtstart   = strtotime(date('Y-m-d')) - 86400;
        $dtend     = strtotime(date('Y-m-d'));
        $base_url  = Config::get('system.base_url');
        $db        = Database::factory();

        $template = $templates['security']['fault_count'];
        $sql      = "SELECT DISTINCT equipment_id FROM eq_status WHERE ctime>%d AND ctime<%d";
        $query    = $db->query($sql, $dtstart, $dtend);
        if (is_object($query)) {
            $results = $query->rows();
        }

        foreach ($results as $result) {
            $id    = $result->equipment_id;
            $url   = $base_url . '!equipments/equipment/index.' . $id;
            $eq    = O('equipment', $id);
            $arr[] = URI::anchor($url, $eq->name);
        }
        if (count($arr)) {
            $list = implode(',', $arr);
            $str .= V('equipments:newsletter/fault_count', [
                'list'     => $list,
                'template' => $template,
            ]);
        }

        $template = $templates['security']['still_fault'];
        $sql      = "SELECT id,name FROM equipment WHERE status=" . EQ_Status_Model::OUT_OF_SERVICE;
        $query    = $db->query($sql, $dtstart, $dtend);
        if (is_object($query)) {
            $results = $query->rows();
        }

        $str .= V('equipments:newsletter/still_fault', [
            'results'  => $results,
            'template' => $template,
        ]);

        if (strlen($str) > 0) {
            $view = V('equipments:newsletter/view', [
                'str' => $str,
            ]);
            $e->return_value .= $view;
        }
    }

    public static function send_offline_password($equipment)
    {

        //这个是support的地址
        $receivers = (array) Lab::get('equipment.offline_password_receivers');
        $me        = L('ME');

        if (count($receivers) > 0) {

            // 发送邮件
            foreach ($receivers as $receiver) {

                $email_user = O('user', ['email' => $receiver]);

                if (!$email_user->id) {
                    continue;
                }

                $receiver_view = (string) V('equipments:equipment/offline_password/support/normal', ['equipment' => $equipment]);

                $subject = I18N::T('equipments', '%lab_name 仪器离线密码更新', ['%lab_name' => Config::get('system.email_name')]);
                Notification::send("#VIEW#|equipments:equipment/offline_password/support/normal", $email_user, [
                    '#TITLE#'   => $subject,
                    'equipment' => "[[Q:{$equipment}]]",
                    'user'      => "[[Q:{$me}]]",
                ]);

                Log::add(strtr('[equipments] 离线密码邮件已发送给 %receiver', ['%receiver' => $receiver]), 'journal');
            }
        }

        //发送邮件给仪器负责人
        $incharges = Q("$equipment user.incharge");

        $title = I18N::T('equipments', '您负责的仪器 [%equipment] 的离线密码已更新', ['%equipment' => $equipment->name]);

        foreach ($incharges as $incharge) {

            Notification::send("#VIEW#|equipments:equipment/offline_password/incharge/normal", $incharge, [
                '#TITLE#'   => $title,
                'equipment' => "[[Q:{$equipment}]]",
                'incharge'  => "[[Q:{$incharge}]]",
                'user'      => "[[Q:{$me}]]",
            ]);

            Log::add(strtr('[equipments] 离线密码邮件已发送给 %incharge', ['%incharge' => $incharge]), 'journal');
        }
    }

    //离线密码初始化时消息发送
    public static function send_offline_password_init($equipment)
    {

        //这个是support的地址
        $receivers = (array) Lab::get('equipment.offline_password_receivers');

        if (count($receivers) > 0) {

            // 发送邮件
            foreach ($receivers as $receiver) {

                $email_user = O('user', ['email' => $receiver]);

                if (!$email_user->id) {
                    continue;
                }

                $title = Config::get('system.email_name');

                Notification::send("#VIEW#|equipments:equipment/offline_password/support/init", $email_user, [
                    '#TITLE#'   => $title,
                    'equipment' => "[[Q:{$equipment}]]",
                ]);

                Log::add(strtr('[equipments] 离线密码邮件已发送给 %receiver', ['%receiver' => $receiver]), 'journal');
            }
        }

        //发送邮件给仪器负责人
        $incharges = Q("$equipment user.incharge");

        $title = I18N::T('equipments', '您负责的仪器 [%equipment] 的离线密码初始化', ['%equipment' => $equipment->name]);
        foreach ($incharges as $incharge) {

            Notification::send("#VIEW#|equipments:equipment/offline_password/incharge/init", $incharge, [
                '#TITLE#'   => $title,
                'equipment' => "[[Q:{$equipment}]]",
                'incharge'  => "[[Q:{$incharge}]]",
            ]);

            Log::add(strtr('[equipments] 离线密码邮件已发送给 %incharge', ['%incharge' => $incharge]), 'journal');
        }
    }

    public static function create_temporary_lab()
    {
        if (Module::is_installed('labs')) {
            return self::default_lab();
        }
        return [];
    }

    public static function heartbeat_control($id, $container_id)
    {
        $equipment                         = O('equipment', $id);
        Output::$AJAX['#' . $container_id] = [
            'data' => (string) V('equipments:equipment/control_status', ['equipment' => $equipment]),
            'mode' => 'replace',
        ];

    }

    //equipment extra_setting view breadcrub get function
    public static function extra_setting_breadcrumb($e, $equipment, $type)
    {
        if ($type != 'use') {
            return;
        }

        $e->return_value = [
            [
                'url'   => $equipment->url(),
                'title' => H($equipment->name),
            ],
            [
                'url'   => $equipment->url(null, null, null, 'edit'),
                'title' => I18N::T('eq_sample', '设置'),
            ],
            [
                'url'   => $equipment->url('use', null, null, 'extra_setting'),
                'title' => I18N::T('eq_sample', '使用表单'),
            ],
        ];
    }

    public static function extra_setting_content($e, $equipment, $type)
    {
        if ($type != 'use') {
            return;
        }

        $e->return_value = (string) V('extra_setting', ['equipment' => $equipment]);
    }

    public static function default_extra_setting_view($e, $uniqid, $field, $object, $prefix)
    {
        $e->return_value = (string) V('equipments:extra/setting/' . $uniqid, ['field' => $field, 'object' => $object, 'prefix' => $prefix]);
        return false;
    }

    public static function extra_check_field_title($e, $title, $extra)
    {

        if ($extra->type == 'use') {

            //存储系统默认locale
            $default_locale = $_SESSION['system.locale'];
            $self_fields = Config::get('extra.equipment.use', []);

            foreach ($self_fields as $category => $fields) {

                unset($fields['#i18n_module']);
                foreach ($fields as $f) {

                    $_title = $f['title'];

                    foreach (Config::get('system.locales') as $locale => $name) {
                        //清除自身模块I18N
                        I18N::clear_cache('use');
                        //设定locale
                        I18N::set_locale($locale);

                        //如果I18N后发现现有翻译已存在该传入title, 发现问题 break;
                        if (I18N::T('eq_sample', $_title) == $title) {
                            //纠正
                            Config::set('system.locale', $default_locale);
                            I18N::set_locale($default_locale);
                            $e->return_value = true;
                            return false;
                        }
                    }
                }
            }

            //纠正
            Config::set('system.locale', $default_locale);
            I18N::set_locale($default_locale);
        }
    }

    public static function get_equipment_count()
    {
        $cache = Cache::factory();
        if (!$cache->get('equipment_count')) {
            $selector_pattern = 'equipment[status=%s]';
            $equipment_count  = [
                EQ_Status_Model::IN_SERVICE           => Q(sprintf($selector_pattern, EQ_Status_Model::IN_SERVICE))->total_count(),
                EQ_Status_Model::OUT_OF_SERVICE       => Q(sprintf($selector_pattern, EQ_Status_Model::OUT_OF_SERVICE))->total_count(),
                EQ_Status_Model::NO_LONGER_IN_SERVICE => Q(sprintf($selector_pattern, EQ_Status_Model::NO_LONGER_IN_SERVICE))->total_count(),
                'total'                               => Q('equipment')->total_count(),
            ];

            $cache->set('equipment_count', $equipment_count, 3600);
        }
    }

    public static function get_user_from_sec_card($e, $card_no)
    {
        $e->return_value = Q("user[card_no={$card_no}|card_no_sec={$card_no}]:limit(1)")->current();
    }

    public static function get_user_from_sec_card_s($e, $card_no_s)
    {
        $e->return_value = Q("user[card_no_s={$card_no_s}|card_no_sec_s={$card_no_s}]:limit(1)")->current();
    }

    public static function before_equipment_save($e, $equipment, $new_data)
    {

        if (isset($new_data['name'])) {
            $users    = Q("{$equipment} user.contact");
            $contacts = [];
            foreach ($users as $user) {
                $contacts[] = $user->name_abbr;
            }
            $equipment->contacts_abbr = join(', ', $contacts);
            $equipment->location_abbr = PinYin::code($equipment->location);
        }

        if (!$equipment->id || !$new_data['control_address']) {
            return;
        }

        $control_power_address = $new_data['control_address'];
        $sign                  = 'gmeter://';
        $ret                   = stripos($control_power_address, $sign);
        if ($ret === 0) {
            $arr  = explode($sign, $control_power_address, 2);
            $uuid = $arr[1];

            $eq_meter = O('eq_meter', ['uuid' => $uuid]);
            if ($eq_meter->id) {
                if ($eq_meter->equipment->id == $equipment->id) {
                    $e->return_value = true;
                } else {
                    $old_equipments           = $eq_meter->old_equipments;
                    $old_equipments[]         = $eq_meter->equipment->id;
                    $eq_meter->old_equipments = $old_equipments;
                    $eq_meter->equipment      = $equipment;
                    $e->return_value          = $eq_meter->save();
                }
                return false;
            }
            $eq_meter = Q("eq_meter[equipment={$equipment}]:limit(1)")->current();
            if ($eq_meter->id) {
                $eq_meter->uuid  = $uuid;
                $e->return_value = $eq_meter->save();
                return false;
            } else {
                $eq_meter            = O('eq_meter');
                $eq_meter->uuid      = $uuid;
                $eq_meter->equipment = $equipment;
                $e->return_value     = $eq_meter->save();
                return false;
            }
        } else {
            return;
        }

    }

    public static function post_submit_saved($e, $form, $equipment)
    {
        $contacts = json_decode($form['contacts'], true);
        foreach ($contacts as $contact) {
            $c[] = PinYin::code($contact);
        }
        $equipment->contacts_abbr = join(', ', $c);
        $equipment->location_abbr = PinYin::code($equipment->location);
        $equipment->save();
        $e->return_value = true;
        return true;
    }

    public static function on_equipment_deleted($e, $equipment)
    {
        $sign            = 'gmeter://';
        $control_address = $equipment->control_address;
        if (stripos($control_address, $sign) === 0) {
            $arr  = explode($sign, $control_address, 2);
            $uuid = $arr[1];

            $eq_meter = O('eq_meter', ['uuid' => $uuid]);

            if ($eq_meter->id) {
                $eq_meter->delete();
            }

        }
    }

    public static function delete_equipment_gmeter($e, $equipment, $old_data, $new_data)
    {

        // bugfix16206 【Mac OS,firefox】17Kong/Sprint-132：仪器目录，开启eq_meter模块，gmeter页卡会因为点击两次电源控制器的“更新”按钮而消失
        // 开启eq_meter模块时， 使用设置-电源控制-终端地址不更改保存时，仪器详情-Gmeter页卡会消失
        // 但是怕影响南开，先注释了
        // if ($old_data['control_address'] == $new_data['control_address'])
        // {
        //     return true;
        // }
        if ($old_data['control_mode'] === 'power') {
            $sign                = 'gmeter://';
            $old_control_address = $old_data['control_address'];
            if (stripos($old_control_address, $sign) === 0) {
                $arr      = explode($sign, $old_control_address, 2);
                $old_uuid = $arr[1];
                $eq_meter = O('eq_meter', ['uuid' => $old_uuid]);
                if ($eq_meter->id) {
                    $eq_meter->delete();
                }

            }
        }
    }

    public static function share_equipment_price_conditions($e, $equipment)
    {
        $sample        = Config::get('share_conditions.accept_sample');
        $sample_bottom = $sample['bottom'];
        $sample_top    = $sample['top'];
        $reserv        = Config::get('share_conditions.accept_reserv');
        $reserv_bottom = $reserv['bottom'];
        $reserv_top    = $reserv['top'];
        if ($price = $equipment->price) {
            if ((isset($sample['force']) && $sample['force']) || (!$equipment->accept_sample_manually)) {
                if (isset($sample_bottom['price']) && (double) $sample_bottom['price']) {
                    if (isset($sample_top['price']) && (double) $sample_top['price']) {
                        if ($price < (double) $sample_top['price'] && $price >= (double) $sample_bottom['price']) {
                            $equipment->accept_sample = true;
                        } else {
                            $equipment->accept_sample = false;
                        }
                    } else {
                        if ($price >= (double) $sample_bottom['price']) {
                            $equipment->accept_sample = true;
                        } else {
                            $equipment->accept_sample = false;
                        }
                    }
                } else {
                    if (isset($sample_top['price']) && (double) $sample_top['price']) {
                        if ($price < (double) $sample_top['price']) {
                            $equipment->accept_sample = true;
                        } else {
                            $equipment->accept_sample = false;
                        }
                    }
                }
            }

            if ((isset($reserv['force']) && $reserv['force']) || (!$equipment->accept_reserv_manually)) {
                if (isset($reserv_bottom['price']) && (double) $reserv_bottom['price']) {
                    if (isset($reserv_top['price']) && (double) $reserv_top['price']) {
                        if ($price < (double) $reserv_top['price'] && $price >= (double) $reserv_bottom['price']) {
                            $equipment->accept_reserv = true;
                        } else {
                            $equipment->accept_reserv = false;
                        }
                    } else {
                        if ($price >= (double) $reserv_bottom['price']) {
                            $equipment->accept_reserv = true;
                        } else {
                            $equipment->accept_reserv = false;
                        }
                    }
                } else {
                    if (isset($reserv_top['price']) && (double) $reserv_top['price']) {
                        if ($price < (double) $reserv_top['price']) {
                            $equipment->accept_reserv = true;
                        } else {
                            $equipment->accept_reserv = false;
                        }
                    }
                }
            }
        }
    }

    public static function approval_ACL($e, $me, $perm_name, $object, $options){
        $e->return_value = false;
        switch ($perm_name) {
            case '机主预约审批':
                if ($object->id && Equipments::user_is_eq_incharge($me, $object) && $me->access('审批负责仪器的预约')) {
                    $e->return_value = true;
                    return false;
                }
        }
        return false;
    }
    
	static function enable_announcemente($e, $equipment, $user) {
        $now = Date::time();
		if (Q("eq_announce[equipment=$equipment][dtstart<$now][dtend>$now]:not($user eq_announce.read)")->total_count() > 0 ) {
			$e->return_value = TRUE;
		} else {
			$e->return_value = FALSE;	
		}
    }

    /**
     * 脚本可视化，保存可视化模版，保存对应变量
     * 存入equipment扩展属性
     * @param $e
     * @param $script
     * @param $form
     * @param $equipment
     */
    static function custom_content($e,$script,$form,$equipment){
        $equipmentCustomContent = $equipment->custom_content ?? [];
        $equipmentCustomVars = $equipment->custom_vars ?? [];
        $q2nubmer = [
            '一' => '1',
            '二' => '2',
            '三' => '3',
            '四' => '4',
            '五' => '5',
            '六' => '6',
            '日' => '7',
        ];
        $customContent = $customVars = $customType = null;
        //提交了脚本和脚本可视化的类型
        if($script && isset($form['custom_type'])){
            $customContent = $form['custom_content'];
            if($customContent){
                preg_match_all('/{(\w*)?}/',$customContent,$input);
                if(isset($input[1])){
                    foreach($input[1] as $inp){
                        //转换周一周二等文字
                        if(array_key_exists($form[$inp],$q2nubmer)){
                            $customVars[$inp] = $q2nubmer[$form[$inp]];
                        }else{
                            $customVars[$inp] = $form[$inp];
                        }
                    }
                }
            }
            $equipmentCustomContent[$form['custom_type']] = $customContent;
            $equipmentCustomVars[$form['custom_type']] = $customVars;
        }
        $equipment->custom_content = $equipmentCustomContent;
        $equipment->custom_vars = $equipmentCustomVars;
        return true;
    }

    static function custom_content_render($equipment,$customType,$costumContent = ''){
        if(!$costumContent){
            $costumContent = $equipment->custom_content[$customType];
        }
        if(!$costumContent){
            return '';
        }
        $visualVars = $equipment->custom_vars[$customType];
        //寻找到模版中{}包住的变量，插入对应的input，并根据visual_vars是否有值，存入默认值
        preg_match_all('/{(\w*)?}/',$costumContent,$visualVarsMatch);
        if($visualVarsMatch && $visualVarsMatch[1]){
            foreach($visualVarsMatch[1] as $match){
                $inputV = (String) V('equipments:custom/input_template',
                    [
                        'name'=>$match,
                        'visualVars'=>$visualVars,
                        'equipment'=>$equipment,
                        'customType'=>$customType,
                    ]
                );
                $costumContent = str_replace('{'.$match.'}',trim($inputV),$costumContent);
            }
        }
        //换行替换
        $costumContent = preg_replace("/\r\n|\r|\n/","<br>",$costumContent);
        return $costumContent;
    }

    static function custom_content_empty($e,$equipment,$type){
        $custom_vars = $equipment->custom_vars ?? [];
        $custom_content = $equipment->custom_content ?? [];
        if(!$equipment->id || !$type){
            return true;
        }
        $custom_vars[$type] = null;
        $custom_content[$type] = null;
        $equipment->custom_vars = $custom_vars;
        $equipment->custom_content = $custom_content;
    }
	static function lock_incharge_control_set($e, $equipment) {
		if (L('ME')->is_allowed_to('锁定机主控制方式', $equipment)) return FALSE;
		if ($equipment->lock_incharge_control) $e->return_value = TRUE;
		else $e->return_value = FALSE;
	}

    static function force_read($e, $controller, $method, $params) {
        $force_read = Config::get('equipment.force_read');
        if($force_read && $controller instanceof Layout_Controller && $params[0]){
            $me = L('ME');
            $_SESSION['force_read_'.$params[0].'_'.$me->id] = null;
            $sql = "SELECT ea.id,r.id1 FROM `eq_announce` ea 
                      LEFT JOIN _r_user_eq_announce r 
                        ON ea.id = r.id2 AND r.type = 'read'
                        AND r.id1 = '{$me->id}'
                        WHERE ea.equipment_id = {$params[0]}
                   ";
            $db = Database::factory();
            $res = $db->query($sql);
            $unread = [];
            foreach ($res->rows() as $r){
                !$r->id1 && $r->id ? $unread[] = $r->id : '';
            }
            if(!empty($unread))
                $_SESSION['force_read_'.$params[0].'_'.$me->id] = array_shift($unread);
        }
    }

    static function equipment_sort_selector($e, $form, $selector) {
         // 默认表单排序 -> 技术支持配置置顶排序 -> 开发配置预约排序
        $sort_by = $form['sort'];
        if (!$sort_by) $sort_by = Config::get('equipments.placed_at_the_top') ? 'top' : '';
        if (!$sort_by) $sort_by = Config::get('equipment.sort_reserv') ? 'reserv' : '';

        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        switch ($sort_by) {
        case 'name':
            $selector .= ":sort(name_abbr {$sort_flag})";
            break;
        case 'price':
            $selector .= ":sort(price {$sort_flag})";
            break;
        case 'location':
            $selector .= ":sort(location_abbr {$sort_flag})";
            break;
        case 'control':
            $selector .= ":sort(is_using {$sort_flag}, connect {$sort_flag},is_monitoring {$sort_flag}, control_mode {$sort_flag}, name_abbr {$sort_flag})";
            break;
        case 'current_user':
            $selector .= ":sort(using_abbr {$sort_flag})";
            break;
        case 'contact':
            $selector .= ":sort(contacts_abbr {$sort_flag})";
            break;
        case 'reserv':
            $selector .= ":sort(accept_reserv D, accept_sample D, is_using D, connect D, is_monitoring D, control_mode D, name_abbr A)";
            break;
        case 'group':
            $selector .= ":sort(group_abbr {$sort_flag})";
            break;
        case 'top': 
            $selector .= ":sort(is_top D, top_time D)";
            break;
        default:
            if (Config::get('equipment.equipments.index.default_sort')) $selector .= Config::get('equipment.equipments.index.default_sort');
            else $selector .= ":sort(is_using D, connect D, is_monitoring D, control_mode D, name_abbr A)";
        }
        $e->return_value = $selector;
        return false;
    }

    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $me = L('ME');
        $equipment = $view->calendar->parent;

        $result = true;
        $description = "";
        if (($me->access('为所有仪器添加预约'))
                || ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
                || ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
            ) {
            $result = true;
        } else {
            $deadline = (int)Config::get('equipment.feedback_deadline', 1);
            if ($deadline >= 0) {
                $now = Date::time() - 86400 * $deadline;
                //用户没有填写一天最近一次使用的反馈信息
                $total = Q("eq_record[dtend>0][dtend<=$now][!status][user={$me}]")->total_count();
                if ($total > 0) {
                    $result = false;
                    $description = I18N::T('equipments', '有%num条待反馈记录, ', ['%num' => $total]);
                    $description .= '<a class="blue prevent_default" href="'.$equipment->url('feedback').'">'.I18N::T('equipments', '点击反馈').'</a>';
                }
            }
        }
        $check_list[] = [
            'title' => I18N::T('equipments', '使用反馈'),
            'result' => $result,
            'description' => $description
        ];
        $view->check_list = $check_list;
    }

    public static function create_orm_tables()
    {
        $upgrade_lims_accept_overtime_setting = Lab::get('upgrade_lims_accept_overtime_setting');
        if (!$upgrade_lims_accept_overtime_setting) {
            foreach (Q("equipment") as $equipment) {
                if ($equipment->accept_reserv) {
                    $equipment->accept_overtime = true;
                    $equipment->allow_over_time =  0;
                    $equipment->save();
                }
            }
            Lab::get('upgrade_lims_accept_overtime_setting', true);
        }
    }

    //页面view
    static function duty_teacher_edit_use_view($e, $equipment)
    {
        if (Config::get('eq_record.duty_teacher')){
            $e->return_value .= V('equipments:equipment/duty_teacher_view', ['equipment' => $equipment]);
        }
    }

    static function duty_teacher_edit_use_submit($e, $equipment, $form)
    {
        if (Config::get('eq_record.duty_teacher')) {
            $equipment->require_dteacher = $form['require_dteacher'] ? 1 : 0;
        }
    }

    public static function setup_equipment()
    {
        Event::bind('equipment.edit.tab', 'Equipments::_edit_use_notice_equipment_tab');
        Event::bind('equipment.edit.content', 'Equipments::_edit_use_notice_equipment_content', 0, 'use_notice');
    }

    public static function _edit_use_notice_equipment_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me = L('ME');
        if ($me->is_allowed_to('管理使用', $equipment)) {
            $tabs->add_tab('use_notice', [
                'url' => $equipment->url('use_notice', null, null, 'edit'),
                'title' => I18N::T('equipments', '预警消息设置'),
                'weight' => 101,
            ]);
        }
    }

    public static function _edit_use_notice_equipment_content($e, $tabs)
    {
        $me = L('ME');
        $equipment = $tabs->equipment;
        $configs = [
            'notification.equipments.not_use_notice',
            'notification.equipments.use_more_notice',
            'notification.equipments.use_less_notice',
        ];
        $vars = [];
        $form = Form::filter(Input::form());
        if ($form['type']) {
            $key = end(explode('.', $form['type']));
        }

        if ($form['submit']) {
            if (!$me->is_allowed_to('管理使用', $equipment)) {
                URI::redirect('error/401');
            }
            $form
                ->validate('title', 'not_empty', I18N::T('equipments', '消息标题不能为空！'))
                ->validate('body', 'not_empty', I18N::T('equipments', '消息内容不能为空！'));
            $vars['form'] = $form;
            if ($form->no_error && in_array($form['type'], $configs)) {
                $config = $quipment->use_notice_setting;
                if (is_null($config)) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                }
                $tmp = [
                    'description' => $config['description'],
                    'strtr' => $config['strtr'],
                    'title' => $form['title'],
                    'body' => $form['body'],
                ];

                $handles = Lab::get('notification.handlers');
                $new_handles['system']['class'] = null;
                $new_handles['system']['text'] = '通过系统提示弹框';
                $new_handles['system']['name'] = '系统弹框';
                $new_handles['system']['default_send'] = 1;
                $new_handles['system']['default_receive'] = 0;
                if ($handles['messages']) {
                    $new_handles['messages'] = $handles['messages'];
                }

                foreach ($new_handles as $k => $v) {
                    if (isset($form['send_by_' . $k])) {
                        $value = $form['send_by_' . $k];
                    } else {
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = [
                        $v['text'],
                        $value,
                    ];
                }

                foreach ((array) $form['receives'] as $incharge_id => $v) {
                    $v == 'on' && $tmp['receive_by'][$incharge_id] = true;
                }

                $equipment->$key = $tmp;
                // Lab::set($form['type'], $tmp);
                if ($equipment->save()) {
                    Log::add(strtr('[equipments use_notice] %user_name[%user_id]修改了%equipment_name[%equipment_id]仪器的使用预警设置', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '内容修改成功'));
                }
            }
        } elseif ($form['restore']) {
            $equipment->$key = null;
            if ($equipment->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '恢复系统默认设置成功'));
            }
        }

        foreach ($configs as $v) {
            $key = end(explode('.', $v));
            $vars[$key] = (array) $equipment->$key;
        }
        $vars['equipment'] = $equipment;
        // $vars += ['icon' => $equipment->icon('104')];
        $views = Notification::preference_views($configs, $vars, 'equipments', false);
        $tabs->content = '<div class="tab_content">' . (string) $views . '</div>';
    }

    public static function equipments_edit_use_extra_view($e, $equipment)
    {
        $me = L('ME');
        $form = Input::form();
        if ($me->is_allowed_to('管理使用', $equipment)) {
            $e->return_value = V('equipments:equipment/edit.extra.use', ['equipment' => $equipment, 'form' => $form]);
        }
    }

    public static function equipment_edit_extra_form_validate($e, $equipment, $form)
    {
        if ($form['allow_equipment_not_use_notice'] && isset($form['equipment_not_use_notice_mins']) && (int) $form['equipment_not_use_notice_mins'] <= 0) {
            $form->set_error('equipment_not_use_notice_mins', I18N::T('equipments', '仪器预警时长应该大于0'));
        }

        if ($form['allow_equipment_use_time_more'] && isset($form['equipment_use_time_more_mins']) && (int) $form['equipment_use_time_more_mins'] <= 0) {
            $form->set_error('equipment_use_time_more_mins', I18N::T('equipments', '仪器预警时长应该大于0'));
        }

        if ($form['allow_equipment_use_time_less'] && isset($form['equipment_use_time_less_mins']) && (int) $form['equipment_use_time_less_mins'] <= 0) {
            $form->set_error('equipment_use_time_less_mins', I18N::T('equipments', '仪器预警时长应该大于0'));
        }
    }

    public static function equipment_use_extra_save($e, $equipment, $form)
    {
        $me = L('ME');

        // 仪器超时未使用
        if ($form['allow_equipment_not_use_notice'] == 'on') {
            $equipment->equipment_not_use_notice_mins = Date::convert_interval($form['equipment_not_use_notice_mins'], $form['equipment_not_use_notice_format']);
            $equipment->equipment_not_use_notice_format = $form['equipment_not_use_notice_format'];
            $equipment->allow_equipment_not_use_notice = true;
        } else {
            $equipment->allow_equipment_not_use_notice = false;
            $equipment->equipment_not_use_notice_format = null;
            $equipment->equipment_not_use_notice_mins = null;
        }

        // 仪器使用时间超过
        if ($form['allow_equipment_use_time_more'] == 'on') {
            $equipment->equipment_use_time_more_mins = Date::convert_interval($form['equipment_use_time_more_mins'], $form['equipment_use_time_more_format']);
            $equipment->equipment_use_time_more_format = $form['equipment_use_time_more_format'];
            $equipment->allow_equipment_use_time_more = true;
        } else {
            $equipment->allow_equipment_use_time_more = false;
            $equipment->equipment_use_time_more_format = null;
            $equipment->equipment_use_time_more_mins = null;
        }

        // 仪器使用时长少于
        if ($form['allow_equipment_use_time_less'] == 'on') {
            $equipment->equipment_use_time_less_mins = Date::convert_interval($form['equipment_use_time_less_mins'], $form['equipment_use_time_less_format']);
            $equipment->equipment_use_time_less_format = $form['equipment_use_time_less_format'];
            $equipment->allow_equipment_use_time_less = true;
        } else {
            $equipment->allow_equipment_use_time_less = false;
            $equipment->equipment_use_time_more_format = null;
            $equipment->equipment_use_time_less_mins = null;
        }
    }

    public static function get_template($e, $conf_key)
    {
        $arr = explode('|', $conf_key);
        $info_key = end(explode('.', $arr[0]));
        if (in_array($arr[0], ['notification.equipments.not_use_notice', 'notification.equipments.use_more_notice', 'notification.equipments.use_less_notice'])) {
            if ($arr[1]) {
                $equipment = O('equipment', $arr[1]);
                if ($equipment->id) {
                    $configs = Lab::get($arr[0]) ?: Config::get($arr[0]);
                    $e->return_value = $equipment->$info_key ? $equipment->$info_key : $configs;
                }
            }
        }
    }

    public static function get_template_name($e, $conf_key)
    {
        if (in_array($arr[0], ['notification.equipments.not_use_notice', 'notification.equipments.use_more_notice', 'notification.equipments.use_less_notice'])) {
            $e->return_value = $arr[0];
        }
    }

    /**
     * 判断消息预约是否满足条件
     */
    public static function check_usenotice($equipment, $receiver, $info_key, $type = 'system')
    {
        $default_info = (array) Lab::get("notification.equipments." . $info_key) + (array) Config::get("notification.equipments." . $info_key);
        $info = $equipment->$info_key;
        $info = array_merge($default_info, (array) $info);
        $now = Date::time();
        $day_start = Date::get_day_start($now);
        $day_end = Date::get_day_end($now);
        switch ($info_key) {
            case 'not_use_notice':
                // 有正在使用中的记录，不成立长时间未使用的条件
                if (Q("eq_record[equipment={$equipment}][dtend=0]")->total_count()) {
                    return false;
                }

                // 消息发送时，检测当天是否已发送
                if ($type == 'system') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][ctime={$day_start}~{$day_end}][is_read]")->total_count()) {
                        return false;
                    }
                } elseif ($type == 'messages') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][ctime={$day_start}~{$day_end}]")->total_count()) {
                        return false;
                    }
                }

                $record = Q("eq_record[equipment={$equipment}]:sort(dtend D)")->current();
                // 仪器没有使用记录，则默认前一条时间结束时间为0
                if (!$record->id) {
                    // $record = new StdClass();
                    // $record->dtend = 0;
                    
                    // pms 23988 仪器没有使用记录，则不进行长时间未使用消息的发送了
                    return false;
                }

                // 最后使用结束的时间至当前时间大于 设置的超时时间，符合条件
                return ($now - $record->dtend) > $equipment->equipment_not_use_notice_mins;
                break;
            case 'use_more_notice':
                $records = Q("eq_record[equipment={$equipment}][dtend=0]:sort(dtstart D)");
                // 不存在使用中的使用记录
                if (!$records->total_count()) {
                    return false;
                }

                $record = $records->current();
                // 消息发送时，检测当前记录是否已发送
                if ($type == 'system') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][record={$record}][ctime={$day_start}~{$day_end}][is_read]")->total_count()) {
                        return false;
                    }
                } elseif ($type == 'messages') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][record={$record}][ctime={$day_start}~{$day_end}]")->total_count()) {
                        return false;
                    }
                }
                return ($now - $record->dtstart) > $equipment->equipment_use_time_more_mins ? $record->id : false;
                break;
            case 'use_less_notice':
                $records = Q("eq_record[equipment={$equipment}]:sort(dtend D)");
                // 不存在使用中的使用记录
                if (!$records->total_count()) {
                    return false;
                }

                $record = $records->current();
                // 消息发送时，检测当前记录是否已发送
                if ($type == 'system') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][record={$record}][is_read]")->total_count()) {
                        return false;
                    }
                } elseif ($type == 'messages') {
                    if (Q("usenotice_record[receiver={$receiver}][equipment={$equipment}][type={$type}][short_key={$info_key}][record={$record}]")->total_count()) {
                        return false;
                    }
                }
                return ($record->dtend - $record->dtstart) < $equipment->equipment_use_time_less_mins ? $record->id : false;
                break;
        }

        return false;
    }

    public static function data_init()
    {
        for($i = 0;$i < 2;$i++) {
            $equipment_technical = config::get("data.technical");
            $root = Tag_Model::root('equipment_technical');
            foreach ($equipment_technical as $code => $value) {
                $parent = Tag_Model::root('equipment_technical');
                if ($value['parent_code']) {
                    $parent = O('tag_equipment_technical', ['code' => $value['parent_code']]);
                } 
                if (!$parent->id) {
                    $parent = Tag_Model::root('equipment_technical');
                }
                $technical = O('tag_equipment_technical', ['code' => $code]);
                if (!$technical->id) {
                    $technical = O('tag_equipment_technical');
                }
                $technical->code = $code;
                $technical->name = $code." ".$value['name'];
                $technical->parent = $parent;
                $technical->root = $root;
                $technical->save();
            }
        }

        for($i = 0;$i < 2;$i++) {
            $equipment_education = config::get("data.education");
            $root = Tag_Model::root('equipment_education');
            foreach ($equipment_education as $code => $value) {
                $parent = Tag_Model::root('equipment_education');
                if ($value['parent_code']) {
                    $parent = O('tag_equipment_education', ['code' => $value['parent_code']]);
                } 
                if (!$parent->id) {
                    $parent = Tag_Model::root('equipment_education');
                }
                $education = O('tag_equipment_education', ['code' => $code]);
                if (!$education->id) {
                    $education = O('tag_equipment_education');
                }
                $education->code = $code;
                $education->name = $value['name'];
                $education->parent = $parent;
                $education->root = $root;
                $education->save();
            }
        }
    }

}
