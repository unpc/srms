<?php

class DC_Record
{

    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'DC_Record::index_profile_tab');
        Event::bind('profile.view.content', 'DC_Record::index_profile_content', 0, 'dc_record');
        Event::bind('profile.view.tool_box', 'DC_Record::_tool_box_records', 0, 'dc_record');
    }

    public static function setup_lab()
    {
        if (Module::is_installed('labs')) {
            Event::bind('lab.view.tab', 'DC_Record::index_lab_tab');
            Event::bind('lab.view.content', 'DC_Record::index_lab_content', 0, 'dc_record');
            Event::bind('lab.view.tool_box', 'DC_Record::_tool_box_lab', 0, 'dc_record');
        }
    }

    public static function index_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if ($me->is_allowed_to('列表门禁记录', $user)) {
            $tabs->add_tab('dc_record', [
                'url'   => $user->url('dc_record'),
                'title' => I18N::T('entrance', '进出记录')]
            );
        }
    }

    public static function index_profile_content($e, $tabs)
    {
        //进门者搜索条件没有用到？
        $user = $tabs->user;
        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['direction'] == '-1') {
                unset($old_form['direction']); // 状态再调回-1时old入侵now
                unset($form['direction']);
            }

        });
        
        $start    = (int) $form['st'];
        $per_page = 15;
        $start    = $start - ($start % $per_page);

        $me = L('ME');

        $pre_selector = [];

        if ($form['name'] || $form['location1'] || $form['location2']) {
            if ($form['name']) {
                $name = Q::quote(trim($form['name']));
                $door_selector .= "[name*=$name]";
            }

            if ($form['location1']) {
                $location1 = Q::quote($form['location1']);
                $door_selector .= "[location1*=$location1]";
            }

            if ($form['location2']) {
                $location2 = Q::quote(trim($form['location2']));
                $door_selector .= "[location2*=$location2]";
            }

            $pre_selector['door'] = 'door' . $door_selector;
        }

        if (count($pre_selector) > 0) {
            $selector = '(' . implode(',', $pre_selector) . ') ';
        }

        $selector .= "dc_record[user={$user}]";

        if ($form['dtstart']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
            $selector .= "[time>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtend']));
            $selector .= "[time>0][time<=$dtend]";
        }

        /* if (!$form['dtstart'] && !$form['dtend']) {
            $dtend_date      = getdate(time());
            $form['dtend']   = mktime(0, 0, 0, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['dtstart'] = $form['dtend'] - 2592000;
        } */

        if ($form['direction'] || $form['direction'] === '0') {
            $direction = Q::quote($form['direction']);
            $selector .= "[direction=$direction]";
        }

        switch ($form['attendance']) {
            case DC_Record_Model::FILTER_EARLIEST:
                $selector .= ':daymin(time|door_id,user_id)';
                break;
            case DC_Record_Model::FILTER_LATEST:
                $selector .= ':daymax(time|door_id,user_id)';
                break;
        }

        $sort_by   = $form['sort'] ?: 'time';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = Event::trigger('entrance.dc_record.extra_selector', $selector, $form) ? : $selector;

        $selector .= ":sort({$sort_by} {$sort_flag})";
        $dc_records            = Q($selector);
        $form['selector']      = $selector;
        $tabs->form_token = $form_token            = Session::temp_token('dc_record_', 300);
        $_SESSION[$form_token] = $form;

        $fields        = self::get_lab_fields($form, ['type' => 'personal']);
        $tabs->columns = new ArrayObject($fields);

        $pagination = Lab::pagination($dc_records, $start, $per_page);

        /* $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text' => '',
            'tip'  => I18N::T('entrance', '导出Excel'),
            'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
            '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
            '" class="button button_save middle"',
        ];
        $panel_buttons[] = [
            'text' => '',
            'tip'  => I18N::T('entrance', '打印'),
            'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
            '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
            '" class="button button_print middle"',
        ]; */
        $content = V('entrance:profile/records', [
            'records'       => $dc_records,
            'pagination'    => $pagination,
            'form'          => $form,
            'form_token'    => $form_token,
            'sort_asc'      => $sort_asc,
            'sort_by'       => $sort_by,
            'panel_buttons' => $panel_buttons,
        ]);
        $tabs->content = $content;
    }

    public static function index_lab_tab($e, $tabs)
    {
        $lab = $tabs->lab;
        $me  = L('ME');
        if ($me->is_allowed_to('列表门禁记录', $lab)) {
            $tabs->add_tab('dc_record', [
                'url'   => $lab->url('dc_record'),
                'title' => I18N::T('entrance', '进出记录')]
            );
        }
    }

    public static function index_lab_content($e, $tabs)
    {
        $lab  = $tabs->lab;
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $start    = (int) $form['st'];
        $per_page = 25;
        $start    = $start - ($start % $per_page);

        $pre_selector = [];

        if ($form['name'] || $form['location_id'] || $form['site']) {
            if ($form['name']) {
                $name = Q::quote(trim($form['name']));
                $door_selector .= "[name*=$name]";
            }

            $location_root = Tag_Model::root('location');
            if ($form['location_id'] && $form['location_id'] != $location_root->id) {
                $location = o("tag_location", $form['location_id']);
                $pre_selector['location_door'] = "$location door";
            }

            if($form['type']) {
                $pre_selector['doortype'] = "$location door[type*=" . $form['type'] . "]";
            }

            if ($form['site']) {
                $location2 = Q::quote($form['site']);
                $door_selector .= "[site={$form['site']}]";
            }

            $pre_selector['door'] = 'door'.$door_selector;
        }

        $pre_selector['user'] = "{$lab} user";

        if (Module::is_installed('db_sync') && Db_Sync::is_slave()) {
            if ($pre_selector['door']) {
                $pre_selector['door'] .= '[site=' . LAB_ID . ']';
            } else {
                $pre_selector['door'] = 'door[site=' . LAB_ID . ']';
            }
        }

        if ($form['user']) {
            $user = Q::quote(trim($form['user']));
            $pre_selector['user'] .= "[name*={$user}]";
        }

        if (count($pre_selector) > 0) {
            $selector = '(' . implode(',', $pre_selector) . ') ';
        }

        $selector .= 'dc_record';

        if ($form['dtstart']) {
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[time>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote($form['dtend']);
            $selector .= "[time>0][time<=$dtend]";
        }

        if ($form['status'] >= '0') {
            $status = Q::quote($form['status']);
            $selector .= "[status=$status]";
        }

        if ($form['direction'] >= 0 && isset($form['direction'])) { // 0：出门  1：进门
            $direction = Q::quote($form['direction']);
            $selector .= "[direction=$direction]";
        }

        switch ($form['attendance']) {
            case DC_Record_Model::FILTER_EARLIEST:
                $selector .= ':daymin(time|door_id,user_id)';
                break;
            case DC_Record_Model::FILTER_LATEST:
                $selector .= ':daymax(time|door_id,user_id)';
                break;
        }

        $sort_by   = $form['sort'] ?: 'time';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector .= ":sort({$sort_by} {$sort_flag})";
        $dc_records            = Q($selector);

        $form['selector']      = $selector;
        $form_token            = Session::temp_token('dc_record_', 300);
        $_SESSION[$form_token] = $form;

        $tabs->form_token = $form_token;

        $pagination = Lab::pagination($dc_records, $start, $per_page);

        $fields        = self::get_lab_fields($form);
        $tabs->columns = new ArrayObject($fields);

        $content = V('entrance:lab/records', [
            'records'    => $dc_records,
            'pagination' => $pagination,
            'columns'    => $tabs->columns,
            'form'       => $form,
            'form_token' => $form_token,
            'sort_asc'   => $sort_asc,
            'sort_by'    => $sort_by,
        ]);
        $tabs->content = $content;
    }

    public static function in_door_record($e, $door)
    {
        $dc_record            = O('dc_record');
        $dc_record->door      = $door;
        $dc_record->user      = L('ME');
        $dc_record->time      = time();
        $dc_record->direction = DC_Record_Model::IN_DOOR;
        if ($dc_record->save()) {
            /* 记录日志 */
            Log::add(strtr('[entrance] %user_name[%user_id] 于 %date %direction %door_name[%door_id]', [
                '%user_name' => $dc_record->user->name,
                '%user_id'   => $dc_record->user->id,
                '%date'      => Date::format($dc_record->time, 'Y/m/d H:m:s'),
                '%direction' => DC_Record_Model::$direction[$dc_record->direction],
                '%door_name' => $dc_record->door->name,
                '%door_id'   => $dc_record->door->id,
            ]), 'journal');

            $e->return_value = true;
        } else {
            $e->return_value = false;
        }
    }

    public static function out_door_record($e, $door)
    {
        $dc_record            = O('dc_record');
        $dc_record->door      = $door;
        $dc_record->user      = L('ME');
        $dc_record->time      = time();
        $dc_record->direction = DC_Record_Model::OUT_DOOR;
        if ($dc_record->save()) {
            /* 记录日志 */
            Log::add(strtr('[entrance] %user_name[%user_id] 于 %date %direction %door_name[%door_id]', [
                '%user_name' => $dc_record->user->name,
                '%user_id'   => $dc_record->user->id,
                '%date'      => Date::format($dc_record->time, 'Y/m/d H:m:s'),
                '%direction' => DC_Record_Model::$direction[$dc_record->direction],
                '%door_name' => $dc_record->door->name,
                '%door_id'   => $dc_record->door->id,
            ]), 'journal');

            $e->return_value = true;
        } else {
            $e->return_value = false;
        }
    }

    public static function index_records_get($door = null, $type = null)
    {
        //多栏搜索
        $form = Lab::form(function (&$old_form, &$form) {
                if ($form['direction'] == '-1') {
                    unset($old_form['direction']);
                    unset($form['direction']);
                }
        });

        $start = (int) $form['st'];
        $per_page = Config::get('per_page.dc_record', 25);
        $start = $start - ($start % $per_page);

        $me = L('ME');

        $pre_selector = [];
        if (!$door->id || $form['name'] || $form['location_id'] || $form['type']) {
            /*
            进出总列表页面需要的权限就是 管理所有门禁 或者 查看所有门禁的进出记录，查了下，没发现哪个地方在用，暂时注释掉
            if (!$door->id && !$me->access('管理所有门禁') && !$me->access('查看所有门禁的进出记录')) {
            $door_selector .= "[incharger=$me]";
            }
             */

            if ($form['name']) {
                $name = Q::quote(trim($form['name']));
                $door_selector .= "[name*=$name]";
            }

            $location_root = Tag_Model::root('location');
            if ($form['location_id'] && $form['location_id'] != $location_root->id) {
                $location = O("tag_location", $form['location_id']);
                $pre_selector['door'] = "$location door".$door_selector;
            } else {
                $pre_selector['door'] = "door".$door_selector;
            }

            if($form['type']) {
                $pre_selector['doortype'] = "$location door[type*=" . $form['type'] . "]";
            }
        }

        if (!$me->access('管理所有门禁') && !$me->access('查看所有门禁的进出记录')) {
            if ($me->access('查看负责仪器关联的进出记录') && !Q("$me<incharge $door")->total_count()) {
                $pre_selector['eq_door'] = "$me<incharge equipment door.asso";
            }
            $pre_selector['door_incharge'] = "$me<incharge door";
        }

        /*if (Module::is_installed('db_sync') && Db_Sync::is_slave()) {
            if ($pre_selector['door']) {
                $pre_selector['door'] .= '[site=' . LAB_ID . ']';
            } else {
                $pre_selector['door'] = 'door[site=' . LAB_ID . ']';
            }
        }*/

        if ($form['user']) {
            $user                 = Q::quote(trim($form['user']));
            $pre_selector['user'] = "user[name*=$user]";
        }

        if ($form['lab']) {
            $lab                 = Q::quote(trim($form['lab']));
            $pre_selector['lab'] = "lab[name*=$lab] user";
        }

        if (count($pre_selector) > 0) {
            $selector = '(' . implode(',', $pre_selector) . ') ';
        }

        $selector .= 'dc_record';

        if ($door->id) {
            $selector .= "[door=$door]";
        }

        if ($form['dtstart']) {
            $dtstart =  Date::get_day_start(Q::quote($form['dtstart']));
            $selector .= "[time>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Date::get_day_end(Q::quote($form['dtend']));
            $selector .= "[time>0][time<=$dtend]";
        }

        /* if (!$form['dtstart'] && !$form['dtend']) {
            $dtend_date      = getdate(time());
            $form['dtend']   = mktime(0, 0, 0, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
            $form['dtstart'] = $form['dtend'] - 2592000;
        } */

        if ($form['direction'] >= '0') {
            $direction = Q::quote($form['direction']);
            $selector .= "[direction=$direction]";
        }

        if ($form['status'] >= '0') {
            $status = Q::quote($form['status']);
            $selector .= "[status=$status]";
        }

        switch ($form['attendance']) {
            case DC_Record_Model::FILTER_EARLIEST:
                $selector .= ':daymin(time|door_id,user_id)';
                break;
            case DC_Record_Model::FILTER_LATEST:
                $selector .= ':daymax(time|door_id,user_id)';
                break;
        }

        /*
        BUG #433 (Cheng.liu@2011.03.24)
        将date改成time，因为数据库中存储的是time字段，用date无法查询，且转换麻烦
         */
        $sort_by   = $form['sort'] ?: 'time';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector .= ":sort({$sort_by} {$sort_flag})";

        //$form['selector'] = $selector;
        $records = Q($selector);

        //生成 session token
        $form['selector']      = $selector;
        $form_token            = Session::temp_token('dc_record_', 300);
        $_SESSION[$form_token] = $form;
        $pagination            = Lab::pagination($records, $start, $per_page);
        /*
        NO.BUG#108（guoping.zhang@2010.11.12)
        因为打印按钮和导出CSV按钮，用Widget显示
        所以panel_buttons键结构是url，text，extra
         */
        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text'  => I18N::T('entrance', '导出'),
            'tip'   => I18N::T('entrance', '导出Excel'),
            'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
            '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token, 'door' => $door->id]) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text'  => I18N::T('entrance', '打印'),
            'tip'   => I18N::T('entrance', '打印'),
            'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
            '" q-static="' . H(['type' => 'print', 'form_token' => $form_token, 'door' => $door->id]) .
            '" class="button button_print  middle"',
        ];
        $columns = self::get_dc_records_fields($form, $door);

        if ($type == 'record') {
            $search_box   = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['user'], 'columns' => $columns]);
            $records_data = [
                'view' => V('records', ['records' => $records,
                    'pagination'                      => $pagination,
                    'door'                            => $door,
                    'form'                            => $form,
                    'form_token'                      => $form_token,
                    'sort_asc'                        => $sort_asc,
                    'sort_by'                         => $sort_by,
                    'columns'                         => $columns,
                    'type'                            => $type,
                ]),
                'data' => [
                    'search_box'    => $search_box,
                ],
            ];
            return $records_data;
        } else {
            $search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'columns' => $columns]);
            return V('records', ['records' => $records,
                'pagination'                   => $pagination,
                'door'                         => $door,
                'form'                         => $form,
                'form_token'                   => $form_token,
                'sort_asc'                     => $sort_asc,
                'sort_by'                      => $sort_by,
                'search_box'                   => $search_box,
                'columns'                      => $columns]);
        }
    }

    public static function get_dc_records_fields($form, $door = null)
    {
        $location_root = Tag_Model::root('location');
        if ($form['location_id']) {
            $location = o("tag_location", $form['location_id']);
        }

        $columns = [];
        if (!$door->id) {
            $columns = [
                'name'     => [
                    'title'  => I18N::T('entrance', '门禁名称'),
                    'filter' => [
                        'form'  => V('entrance:records_table/filters/name', ['value' => $form['name']]),
                        'value' => $form['name'] ? H($form['name']) : null,
                    ],
                    'nowrap' => true,
                ],
                'type'=>[
                    'title'=>I18N::T('entrance', '门禁类型'),
                    'align'=>'left',
                    'invisible' => true,
                    'filter' => [
                        'form'  => V('entrance:records_table/filters/type', ['form' => $form]),
                        'value' => I18N::T('entrance', $form['type']),
                    ],
                    'nowrap'=>TRUE
                ],
                'location' => [
                    'title'  => I18N::T('entrance', '地理位置'),
                    'filter' => [
                        'form' => V('entrance:records_table/filters/location', [
                            'name' => 'location_id',
                            'tag' => $location,
                            'root' => $location_root,
                            'field_title' => I18N::T('people', '请选择地理位置'),
                        ]),
                        'value' => $location->id ? H($location->name): NULL,
                        'field' => 'location_id',
                    ],
                    'nowrap' => true,
                ],
            ];
        }

        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if ($form['direction'] || $form['direction'] === '0') {
            $form['direction_str'] = DC_Record_Model::$direction[$form['direction']];
        }

        if (Module::is_installed('labs')) {
            $columns += [
                'lab' => [
                    'title'     => I18N::T('labs', '实验室'),
                    'invisible' => true,
                    'filter'    => [
                        'form'  => V('labs:lab/lab_filter', ['lab' => $form['lab']]),
                        'value' => $form['lab'] ? H($form['lab']) : null,
                    ],
                ],
            ];
        }

        $columns += [
            'user'       => [
                'title'  => I18N::T('entrance', '刷卡者'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/user', ['user' => $form['user']]),
                    'value' => $form['user'] ? H($form['user']) : null,
                ],
                'nowrap' => true,
            ],
            'time'       => [
                'title'    => I18N::T('entrance', '刷卡时间'),
                'sortable' => true,
                'filter'   => [
                    'form'  => V('entrance:records_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['time'] ? H($form['time']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap'   => true,
            ],
            'direction'  => [
                'title'  => I18N::T('entrance', '方向'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/direction', ['direction' => $form['direction']]),
                    'value' => $form['direction_str'] ? H($form['direction_str']) : null,
                ],
                'nowrap' => true,
            ],
            'status'  => [
                'title'  => I18N::T('entrance', '刷卡结果'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/status', ['status' => $form['status']]),
                    'value' => $form['status'] ? H($form['status']) : null,
                ],
                'nowrap' => true,
            ],
            'remark'  => [
                'title'  => I18N::T('entrance', '备注'),
                'nowrap' => true,
            ],
            'attendance' => [
                'title'     => I18N::T('entrance', '考勤'),
                'filter'    => [
                    'form'  => V('entrance:records_table/filters/attendance.form', ['form' => $form]),
                    'value' => $form['attendance'] ? (string) V('entrance:records_table/filters/attendance.value', ['form' => $form]) : null,
                ],
                'invisible' => true,
            ],
            'rest'       => [
                'title'  => '操作',
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];
        $columns = new ArrayIterator($columns);
        Event::trigger('extra.dc_record.column', $columns, $record, $form);
        return (array)$columns;
    }

    public static function get_records_fields($form, $search_box_need_param = [])
    {
        $me = L('me');

        if (is_array($search_box_need_param)) {
            extract($search_box_need_param);
        }

        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if ($form['direction'] || '0' === $form['direction']) {
            $form['direction_str'] = DC_Record_Model::$direction[$form['direction']];
        }

        $location_root = Tag_Model::root('location');
        if ($form['location_id']) {
            $location = o("tag_location", $form['location_id']);
        }

        $fields = [
            'name'       => [
                'title'  => I18N::T('entrance', '门禁名称'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/name', ['name' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
                'nowrap' => true,
            ],
            'type'=>[
                'title'=>I18N::T('entrance', '门禁类型'),
                'align'=>'left',
                'invisible' => true,
                'filter' => [
                    'form'  => V('entrance:records_table/filters/type', ['form' => $form]),
                    'value' => I18N::T('entrance', $form['type']),
                ],
                'nowrap'=>TRUE
            ],
            'location'   => [
                'title'  => I18N::T('entrance', '地理位置'),
                'filter' => [
                    'form' => V('entrance:records_table/filters/location', [
                        'name' => 'location_id',
                        'tag' => $location,
                        'root' => $location_root,
                        'field_title' => I18N::T('people', '请选择地理位置'),
                    ]),
                    'value' => $location->id ? H($location->name): NULL,
                    'field' => 'location_id',
                ],
                'nowrap' => true,
            ],
            'time'       => [
                'title'    => I18N::T('entrance', '刷卡时间'),
                'sortable' => true,
                'filter'   => [
                    'form'  => V('entrance:records_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['time'] ? H($form['time']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap'   => true,
            ],
            'direction'  => [
                'title'  => I18N::T('entrance', '方向'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/direction', ['direction' => $form['direction']]),
                    'value' => $form['direction_str'] ? H($form['direction_str']) : null,
                ],
                'nowrap' => true,
            ],
            'status'  => [
                'title'  => I18N::T('entrance', '刷卡结果'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/status', ['status' => $form['status']]),
                    'value' => $form['status'] ? H($form['status']) : null,
                ],
                'nowrap' => true,
            ],
            'remark'  => [
                'title'  => I18N::T('entrance', '备注'),
                'nowrap' => true,
            ],
            'attendance' => [
                'title'     => I18N::T('entrance', '考勤'),
                'filter'    => [
                    'form'  => V('entrance:records_table/filters/attendance.form', ['form' => $form]),
                    'value' => $form['attendance'] ? (string) V('entrance:records_table/filters/attendance.value', ['form' => $form]) : null,
                ],
                'invisible' => true,
            ],
            'rest'       => [
                'title'  => I18N::T('entrance', '操作'),
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];

        return $fields;

    }

    public static function get_lab_fields($form, $search_box_need_param = [])
    {

        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if ($form['direction'] || $form['direction'] === '0') {
            $form['direction_str'] = DC_Record_Model::$direction[$form['direction']];
        }

        $location_root = Tag_Model::root('location');
        if ($form['location_id']) {
            $location = o("tag_location", $form['location_id']);
        }

        $fields = [
            'name'       => [
                'title'  => I18N::T('entrance', '门禁名称'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/name', ['name' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
                'nowrap' => true,
            ],
            'type'=>[
                'title'=>I18N::T('entrance', '门禁类型'),
                'align'=>'left',
                'invisible' => true,
                'filter' => [
                    'form'  => V('entrance:records_table/filters/type', ['form' => $form]),
                    'value' => I18N::T('entrance', $form['type']),
                ],
                'nowrap'=>TRUE
            ],
            'location'   => [
                'title'  => I18N::T('entrance', '地理位置'),
                'filter' => [
                    'form' => V('entrance:records_table/filters/location', [
                        'name' => 'location_id',
                        'tag' => $location,
                        'root' => $location_root,
                        'field_title' => I18N::T('people', '请选择地理位置'),
                    ]),
                    'value' => $location->id ? H($location->name): NULL,
                    'field' => 'location_id',
                ],
                'nowrap' => true,
            ],
            'user'       => [
                'title'  => I18N::T('entrance', '刷卡者'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/user', ['user' => $form['user'], 'disabled' => L('ME')->access('管理所有内容') ? '':'disabled']),
                    'value' => $form['user'] ? H($form['user']) : null,
                ],
                'nowrap' => true,
            ],
            'time'       => [
                'title'    => I18N::T('entrance', '刷卡时间'),
                'sortable' => true,
                'filter'   => [
                    'form'  => V('entrance:records_table/filters/date', [
                        'dtstart' => $form['dtstart'],
                        'dtend'   => $form['dtend'],
                    ]),
                    'value' => $form['time'] ? H($form['time']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap'   => true,
            ],
            'direction'  => [
                'title'  => I18N::T('entrance', '方向'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/direction', ['direction' => $form['direction']]),
                    'value' => $form['direction_str'] ? I18N::HT('entrance', $form['direction_str']) : null,
                ],
                'nowrap' => true,
            ],
            'status'  => [
                'title'  => I18N::T('entrance', '刷卡结果'),
                'filter' => [
                    'form'  => V('entrance:records_table/filters/status', ['status' => $form['status']]),
                    'value' => $form['status'] ? H($form['status']) : null,
                ],
                'nowrap' => true,
            ],
            'remark'  => [
                'title'  => I18N::T('entrance', '备注'),
                'nowrap' => true,
            ],
            'attendance' => [
                'title'     => I18N::T('entrance', '考勤'),
                'filter'    => [
                    'form'  => V('entrance:records_table/filters/attendance.form', ['form' => $form]),
                    'value' => $form['attendance'] ? (string) V('entrance:records_table/filters/attendance.value', ['form' => $form]) : null,
                ],
                'invisible' => true,
            ],
            'rest'       => [
                'title'  => I18N::T('eq_charge', '操作'),
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];

        if ($search_box_need_param['type'] == 'personal') {
            unset($fields['user']);
        }
        $columns = new ArrayIterator($fields);
        Event::trigger('extra.dc_record.column', $columns, $record, $form);
        return (array) $fields;
    }

    public static function _tool_box_records($e, $tabs)
    {
        $me               = L('ME');

        $form_token = $tabs->form_token;
        unset($tabs->form_token);

        if (L('ME')->is_allowed_to('导出记录', 'door')) {
            $panel_buttons   = new ArrayIterator;
            $panel_buttons[] = [
                'text' => I18N::T('entrance', '导出'),
                'tip'   => I18N::T('entrance', '导出Excel'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
                '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                '" class="button button_save "',
            ];
            $panel_buttons[] = [
                'text' => I18N::T('entrance', '打印'),
                'tip'   => I18N::T('entrance', '打印'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
                '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                '" class="button button_print  middle"',
            ];
        }

        $tabs->search_box = V('application:search_box', [
            'panel_buttons' => $panel_buttons, 
            'top_input_arr' => ['name'], 
            'columns' => (array) $tabs->columns
            ]);
    }

    public static function _tool_box_lab($e, $tabs)
    {
        $lab              = $tabs->lab;

        $form_token = $tabs->form_token;

        if (L('ME')->is_allowed_to('导出记录', 'door')) {

            $panel_buttons   = new ArrayIterator;
            $panel_buttons[] = [
                'text' => I18N::T('entrance', '导出'),
                'tip'   => I18N::T('entrance', '导出Excel'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
                '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token, 'lab_id' => $lab->id]) .
                '" class="button button_save "',
            ];
            $panel_buttons[] = [
                'text' => I18N::T('entrance', '打印'),
                'tip'   => I18N::T('entrance', '打印'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!entrance/dc_record') .
                '" q-static="' . H(['type' => 'print', 'form_token' => $form_token, 'lab_id' => $lab->id]) .
                '" class="button button_print  middle"',
            ];
        }

        $tabs->search_box = V('application:search_box', ['top_input_arr' => ['name'], 'columns' => (array) $tabs->columns, 'panel_buttons' => $panel_buttons]);

    }


	static function setup_equipment() {
		Event::bind('equipment.index.tab', 'DC_Record::index_equipment_tab');
		Event::bind('equipment.index.tab.content', 'DC_Record::index_equipment_content', 0, 'dc_record');
        Event::bind('equipment.index.tab.tool_box', 'DC_Record::_tool_box_records', 0, 'dc_record');
	}


	static function index_equipment_tab($e, $tabs) {
		$me = L('ME');
		$equipment = $tabs->equipment;
		if ($me->is_allowed_to('列表门禁记录', $equipment)) {
			$tabs->add_tab('dc_record', [
                'url'   => $equipment->url('dc_record'),
                'title' => I18N::T('entrance', '进出记录'),
                'weight' => 110
                ]
            );
		}
	}


	static function index_equipment_content($e, $tabs) {
        $equipment  = $tabs->equipment;
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $start    = (int) $form['st'];
        $per_page = 25;
        $start    = $start - ($start % $per_page);

        $pre_selector = [];

        if ($form['name'] || $form['location_id'] || $form['site']) {
            if ($form['name']) {
                $name = Q::quote(trim($form['name']));
                $door_selector .= "[name*=$name]";
            }

            $location_root = Tag_Model::root('location');
            if ($form['location_id'] && $form['location_id'] != $location_root->id) {
                $location = o("tag_location", $form['location_id']);
                $pre_selector['location_door'] = "$location door";
            }

            if($form['type']) {
                $pre_selector['doortype'] = "$location door[type*=" . $form['type'] . "]";
            }

            if ($form['site']) {
                $location2 = Q::quote($form['site']);
                $door_selector .= "[site={$form['site']}]";
            }

            $pre_selector['door'] = 'door'.$door_selector;
        }

        $pre_selector['eq_door'] = "$equipment door.asso";

        if (Module::is_installed('db_sync') && Db_Sync::is_slave()) {
            if ($pre_selector['door']) {
                $pre_selector['door'] .= '[site=' . LAB_ID . ']';
            } else {
                $pre_selector['door'] = 'door[site=' . LAB_ID . ']';
            }
        }

        if ($form['user']) {
            $user = Q::quote(trim($form['user']));
            $pre_selector['user'] = "user[name*={$user}]";
        }

        if (count($pre_selector) > 0) {
            $selector = '(' . implode(',', $pre_selector) . ') ';
        }

        $selector .= 'dc_record';

        if ($form['dtstart']) {
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[time>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote($form['dtend']);
            $selector .= "[time>0][time<=$dtend]";
        }

        if ($form['status'] >= '0') {
            $status = Q::quote($form['status']);
            $selector .= "[status=$status]";
        }

        if ($form['direction'] >= 0 && isset($form['direction'])) { // 0：出门  1：进门
            $direction = Q::quote($form['direction']);
            $selector .= "[direction=$direction]";
        }

        switch ($form['attendance']) {
            case DC_Record_Model::FILTER_EARLIEST:
                $selector .= ':daymin(time|door_id,user_id)';
                break;
            case DC_Record_Model::FILTER_LATEST:
                $selector .= ':daymax(time|door_id,user_id)';
                break;
        }

        $sort_by   = $form['sort'] ?: 'time';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector .= ":sort({$sort_by} {$sort_flag})";

        $dc_records            = Q($selector);

        $form['selector']      = $selector;
        $form_token            = Session::temp_token('dc_record_', 300);
        $_SESSION[$form_token] = $form;

        $tabs->form_token = $form_token;

        $pagination = Lab::pagination($dc_records, $start, $per_page);

        $fields        = self::get_dc_records_fields($form);
        $tabs->columns = new ArrayObject($fields);

        $content = V('entrance:equipment/records', [
            'records'    => $dc_records,
            'pagination' => $pagination,
            'columns'    => $tabs->columns,
            'form'       => $form,
            'form_token' => $form_token,
            'sort_asc'   => $sort_asc,
            'sort_by'    => $sort_by,
        ]);
        $tabs->content = $content;
	}

}
