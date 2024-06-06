<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约开发
 */
class EQ_Sample
{

    // equipment extra_setting view breadcrub get function
    public static function extra_setting_breadcrumb($e, $equipment, $type)
    {
        if ($type != 'sample') {
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
                'url'   => $equipment->url('sample', null, null, 'extra_setting'),
                'title' => I18N::T('eq_sample', '送样表单'),
            ],
        ];
    }

    public static function setup_extra_setting($e, $controller, $method, $params)
    {
        if ($params[1] != 'sample') {
            return;
        }

        Event::bind('equipment.extra_setting.content', 'EQ_Sample::extra_setting_content');
    }

    public static function extra_setting_content($e, $equipment, $type)
    {
        if ($type != 'sample') {
            return;
        }

        $me = L('ME');
        if (!$me->is_allowed_to('查看送样设置', $equipment)) {
            URI::redirect('error/401');
        }

        $e->return_value = (string) V('eq_sample:extra_setting', [
            'equipment' => $equipment,
        ]);
    }

    public static function setup_index($e, $controller, $method, $params)
    {
        if ($params[0] != 'sample' && $params[0] != 'sample_all') {
            return;
        }
        $me = L('ME');
        if ($params[0] == 'sample') {
            $length = Q("{$me}<incharge equipment")->total_count();
            if (!$length) {
                URI::redirect('error/401');
            }
        }
        //翻页勾选
        try {
            Event::trigger('registration_export.checkbox_change', $_REQUEST, 'eq_sample');
        } catch (Exception $e) {
        }
        Event::bind('equipments.primary.tab', $params[0] == 'sample_all' ? 'EQ_Sample::sample_all_primary_tab' : 'EQ_Sample::sample_primary_tab');
        Event::bind('equipments.primary.content', 'EQ_Sample::sample_primary_tab_content', 0, $params[0] == 'sample_all' ? 'sample_all' : 'sample');
    }

    public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'EQ_Sample::edit_sample_tab');
        Event::bind('equipment.edit.content', 'EQ_Sample::edit_sample_content', 0, 'sample');
    }

    public static function setup_view()
    {
        Event::bind('equipment.index.tab', 'EQ_Sample::sample_tab');
        Event::bind('equipment.index.tab.tool_box', 'EQ_Sample::_tool_box_sample', 0, 'sample');
        Event::bind('equipment.index.tab.content', 'EQ_Sample::sample_tab_content', 0, 'sample');
    }
    public static function _tool_box_sample($e, $tabs)
    {
        if ($tabs->content->fourth_tabs->selected == 'calendar') {
            return;
        }
        $equipment = $tabs->equipment;
        /*pannel_button*/
        $form          = $tabs->content->fourth_tabs->form;
        $form_token    = $form['form_token'];
        $me            = L('ME');
        $tabs->search_box  = V('application:search_box', ['top_input_arr' => ['serial_number', 'sender'], 'columns' => $tabs->content->fourth_tabs->columns]);
        return;
        /*
         * 这儿先不显示按钮了
         * */
        $panel_buttons = [];
        if ($me->is_allowed_to('添加送样记录', $equipment)) {
            $panel_buttons[] = [
                'tip'   => I18N::HT(
                    'eq_sample',
                    '添加送样记录'
                    //'添加送样记录'
                ),
                'extra' => 'q-object="add_sample_record" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                '" q-static="' . H(['id' => $equipment->id]) .
                '" class="eq_sample_button button_add"',
                'text'  => I18N::HT('eq_sample','添加送样'),
            ];
        } else {
            $panel_buttons[] = [
                'title' => I18N::HT('eq_sample', '申请送样'),
                'text'  => I18N::HT('eq_sample','申请送样'),
                'extra' => 'q-object="add_sample" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                '" q-static="' . H(['id' => $equipment->id]) .
                '" class="eq_sample_button button_add"',
            ];
        }
        if ($me->is_allowed_to('导出送样记录', $equipment)) {
            $panel_buttons[] = [
                'tip'   => I18N::T(
                    'eq_sample',
                    '导出Excel'
                ),
                'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                '" class="eq_sample_button button_save "',
                'text' => I18N::HT('eq_sample','导出'),
            ];
            $panel_buttons[] = [
                'tip'   => I18N::T(
                    'eq_sample',
                    '打印'
                ),
                'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                '" class="eq_sample_button button_print"',
                'text' => I18N::HT('eq_sample','打印'),
            ];
        }
        $new_panel_buttons = Event::trigger('eq_sample_lab_use.panel_buttons', $panel_buttons, $form_token);
        $panel_buttons     = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;
        $tabs->search_box  = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['serial_number', 'sender'], 'columns' => $tabs->content->fourth_tabs->columns]);

    }
    public static function sample_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('sample', [
            'url'   => URI::url('!equipments/extra/sample'),
            'title' => I18N::T('eq_sample', '%name负责的所有仪器的送样情况', ['%name' => H($me->name)]),
        ]);
    }

    public static function sample_all_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('sample_all', [
            'url'   => URI::url('!equipments/extra/sample_all'),
            'title' => I18N::T('eq_sample', '所有仪器的送样情况'),
        ]);
    }

    public static function sample_primary_tab_content($e, $tabs)
    {
        $me         = L('ME');
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_sample_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                // 设置默认排序(能防止第一次点击排序无效的方法)
                if (!isset($form['sort'])) {
                    $form['sort']     = 'dtstart';
                    $form['sort_asc'] = false;
                }
            });

            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;
            $pre_selectors = new ArrayIterator();

            $equipment_selector = 'equipment';
            if ($tabs->selected == 'sample') {
                $pre_selectors['basic'] = "{$me} equipment.incharge";
                $selector               = ' eq_sample';
            } else {
                if ($form['equipment_ref']) {
                    $equipment_ref = Q::quote(trim($form['equipment_ref']));
                    $equipment_selector = "equipment[ref_no*={$equipment_ref}]";
                }

                if($me->access('管理所有内容')){
                }elseif($me->access('添加/修改下属机构的仪器')){
                    $pre_selectors['me_group'] = "{$me->group} {$equipment_selector} ";
                }
                $selector = 'eq_sample';
            }

            if ($tabs->selected == 'incharge') {
                $equipment_selector = 'equipment';
                if ($form['equipment_ref']) {
                    $equipment_ref = Q::quote(trim($form['equipment_ref']));
                    $equipment_selector = "equipment[ref_no*={$equipment_ref}]";
                }

                $pre_selectors['basic'] = "{$me}<incharge {$equipment_selector}";
                $selector               = ' eq_sample';
            }
            if ($tabs->group->id) {
                $pre_selectors['me_group'] = "{$tabs->group} {$equipment_selector} ";
            }

            if (!$pre_selectors['me_group']) {
                if ($form['equipment_ref']) {
                    $equipment_ref = Q::quote(trim($form['equipment_ref']));
                    $equipment_selector = "equipment[ref_no*={$equipment_ref}]";
                    $pre_selectors['equipment'] = " {$equipment_selector} ";
                }
            }

            if ($form['id']) {
                $id = Q::quote($form['id']);
                $selector .= "[id={$id}]";
            }

            $group_root     = Tag_Model::root('group');
            $equipment_root = Tag_Model::root('equipment');

            if ($form['equipment_group']) {
                $equipment_group = O('tag_group', $form['equipment_group']);
                if ($equipment_group->id && $equipment_group->root->id == $group_root->id && $equipment_group->id != $group_root->id) {
                    $pre_selectors['equipment_group'] = "{$equipment_group} equipment";
                } else {
                    unset($form['equipment_group']);
                }
            }

            if ($form['lab_group']) {
                $lab_group = O('tag_group', $form['lab_group']);
                if ($lab_group->id && $lab_group->root->id == $group_root->id && $lab_group->id != $group_root->id) {
                    $pre_selectors['lab_group'] = "{$lab_group} lab user<sender";
                } else {
                    unset($form['lab_group']);
                }
            }

            if ($form['lab_name']) {
                $lab_name                  = Q::quote(trim($form['lab_name']));
                $pre_selectors['lab_name'] = "lab[name*={$lab_name}|name_abbr*={$lab_name}] user<sender";
            }

            if ($form['equipment_type']) {
                $equipment_type = O('tag_equipment', $form['equipment_type']);
                if ($equipment_type->id && $equipment_type->root->id == $equipment_root->id && $equipment_type->id != $equipment_root->id) {
                    $pre_selectors['equipment_type'] = "$equipment_type equipment";
                } else {
                    unset($form['equipment_type']);
                }
            }

            if ($form['sender']) {
                $sender_name             = Q::quote(trim($form['sender']));
                $pre_selectors['sender'] = "user<sender[name*=$sender_name|name_abbr*=$sender_name]";
            }

            if ($form['count']) {
                $count = Q::quote($form['count']);
                $selector .= "[count={$count}]";
            }

            if ($form['operator']) {
                $operator_name             = Q::quote(trim($form['operator']));
                $pre_selectors['operator'] = "user<operator[name*=$operator_name|name_abbr*=$operator_name]";
            }

            if ($form['status']) {
                $status = join(',', (array) $form['status']);
                $selector .= "[status={$status}]";
            }

            if ($form['dtsubmit_dtstart']) {
                $dtstart = Q::quote(Date::get_day_start($form['dtsubmit_dtstart']));
                $selector .= "[dtsubmit>={$dtstart}]";
            }

            if ($form['dtsubmit_dtend']) {
                $dtend = Q::quote(Date::get_day_end($form['dtsubmit_dtend']));
                $selector .= "[dtsubmit>0][dtsubmit<={$dtend}]";
            }

            /*
            if ($form['dtrial_dtstart_check']) {
            $dtstart = Q::quote($form['dtrial_dtstart']);
            $selector .= "[dtstart>={$dtstart}]";
            }
             */

            /*
            if($form['dtrial_dtend_check']){
            $dtend = Q::quote($form['dtrial_dtend']);
            $selector .= "[dtstart>0][dtstart<={$dtend}]";
            }
             */

            if ($form['dtpickup_dtstart']) {
                $dtstart = Q::quote(Date::get_day_start($form['dtpickup_dtstart']));
                $selector .= "[dtpickup>={$dtstart}|dtpickup=0]";
            }

            if ($form['dtpickup_dtend']) {
                $dtend = Q::quote(Date::get_day_start($form['dtpickup_dtend']));
                $selector .= "[dtpickup>0][dtpickup<=$dtend]";
            }
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'serial_number':
                $selector .= ":sort(id {$sort_flag})";
                break;
            case 'equipment':
                $selector .= ":sort(equipment_abbr {$sort_flag})";
                break;
            case 'sender':
                $selector .= ":sort(sender_abbr {$sort_flag})";
                break;
            case 'operator':
                $selector .= ":sort(operator_abbr {$sort_flag})";
                break;
            case 'ctime':
                $selector .= ":sort(ctime {$sort_flag})";
                break;
            case 'dtsubmit':
                $selector .= ":sort(dtsubmit {$sort_flag})";
                break;
            //case 'dtrial' :
            //$selector = ":sort(dtstart {$sort_flag})";
            //break;
            case 'dtpickup':
                $selector .= ":sort(dtpickup {$sort_flag})";
                break;
            case 'count':
                $selector .= ":sort(count {$sort_flag})";
                break;
            case 'status':
                $selector .= ":sort(status {$sort_flag})";
                break;
            default:
                $selector .= ':sort(id D)';
                break;
        }

        $new_selector = Event::trigger('eq_sample.search.filter.submit', $selector, $form, $pre_selectors);
        if ($new_selector) {
            $selector = $new_selector;
        }
        if (count((array)$pre_selectors)) {
            $selector = '(' . implode(', ', (array)$pre_selectors) . ') ' . $selector;
        }
        $_SESSION[$form_token]['selector'] = $selector;
        $samples                           = Q($selector);

        $pagination = Lab::pagination($samples, (int) $form['st'], 15);

        $panel_buttons   = [];
        $panel_buttons[] = [
            'text' => I18N::T('eq_sample', '导出'),
            'tip'   => I18N::T('eq_sample', '导出Excel'),
            'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
            '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text' => I18N::T('eq_smaple', '打印'),
            'tip'   => I18N::T('eq_smaple', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
            '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
            '" class = "button button_print "',
        ];
        $new_panel_buttons = Event::trigger('eq_sample_lab_use.panel_buttons', $panel_buttons, $form_token);
        $panel_buttons     = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;

        $tabs->content = V('eq_sample:incharge/sample', [
            'samples'       => $samples,
            'form'          => $form,
            'form_token'    => $form_token,
            'pagination'    => $pagination,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
            'panel_buttons' => $panel_buttons,
        ]);
    }

    public static function edit_sample_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->is_allowed_to('查看送样设置', $equipment)) {
            $tabs
                ->add_tab('sample', [
                    'url'    => URI::url('!equipments/equipment/edit.' . $equipment->id . '.sample'),
                    'title'  => I18N::T('eq_sample', '送样设置'),
                    'weight' => 30,
                ]);
        }
    }

    public static function edit_sample_content($e, $tabs)
    {
        $equipment  = $tabs->equipment;
        $properties = Properties::factory($equipment);

        if (Input::form('submit')) {
            try {
                if (!L('ME')->is_allowed_to('修改送样设置', $equipment)) {
                    throw new Exception;
                }

                $form = Form::filter(Input::form());

                $accept_sample = intval(($form['accept_sample'] == 'on'));

                if ($equipment->accept_sample != $accept_sample) {
                    Event::trigger('equipment.accept_sample.change', $equipment, $accept_sample);
                }
                $equipment->accept_sample = $accept_sample;

                if (Config::get('sample_approval.to_equipment')) {
                    $sample_approval_enable = ($form['sample_approval_enable'] == 'on');
                    $equipment->sample_approval_enable = $sample_approval_enable;
                }

                $equipment->accept_sample_manually = true;

                $equipment->sample_autoapply = ($form['accept_sample'] == 'on') && ($form['sample_autoapply'] == 'on');

                if($form['sample_counts_limit'] < 0 || (floor($form['sample_counts_limit']) - $form['sample_counts_limit'] != 0)){
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '最大可接样数须为大于等于0整数!'));
                    throw new Exception;
                }
                $equipment->sample_counts_limit = $form['sample_counts_limit'];

                if (L('ME')->is_allowed_to('锁定送样', $equipment)) {
                    $equipment->sample_lock = $form['sample_lock'];
                }
                //统一设置
                if ($form['add_sample_earliest_format'] && $form['add_sample_earliest_format'] == 'i') {
                    $tmp_value = $form['add_sample_earliest_format'];
                    if ((float) $tmp_value > (int) $tmp_value) {
                        $form->set_error('add_sample_earliest_format', I18N::T('eq_sample', '添加送样最早提前时间精确到“分”时，请填写整数值'));
                    }
                }

                if ($form['add_reserv_latest_format'] && $form['add_reserv_latest_format'] == 'i') {
                    $tmp_value = $form['add_reserv_latest_format'];
                    if ((float) $tmp_value > (int) $tmp_value) {
                        $form->set_error('add_reserv_latest_format', I18N::T('eq_reserv', '添加送样最晚提前时间精确到“分”时，请填写整数值'));
                    }
                }

                if ($form['modify_reserv_latest_format'] && $form['modify_reserv_latest_format'] == 'i') {
                    $tmp_value = $form['modify_reserv_latest_format'];
                    if ((float) $tmp_value > (int) $tmp_value) {
                        $form->set_error('modify_reserv_latest_format', I18N::T('eq_reserv', '修改 / 删除送样最晚提前时间精确到“分”时，请填写整数值'));
                    }
                }

                //验证个别
                $special_tags = $form['special_tags'];
                if ($special_tags) {
                    foreach ($special_tags as $i => $tags) {
                        $tags = @json_decode($tags, true);
                        if ($tags) {
                            foreach ($tags as $tag) {
                                if ($form['specific_add_earliest_limit'][$i] == 'customize') {
                                    $tmp_value  = $form['specific_add_earliest_time'][$i];
                                    $tmp_format = $form['specific_add_earliest_format'][$i];
                                    if ($tmp_format == 'i') {
                                        if ((float) $tmp_value > (int) $tmp_value) {
                                            $form->set_error("specific_add_earliest_limit[$i]", I18N::T('eq_sample', '添加送样最早提前时间精确到“分”时，请填写整数值'));
                                        }
                                    }
                                }
                                if ($form['specific_add_latest_limit'][$i] == 'customize') {
                                    $tmp_value  = $form['specific_add_latest_time'][$i];
                                    $tmp_format = $form['specific_add_latest_format'][$i];
                                    if ($tmp_format == 'i') {
                                        if ((float) $tmp_value > (int) $tmp_value) {
                                            $form->set_error("specific_add_latest_limit[$i]", I18N::T('eq_sample', '添加送样最晚提前时间精确到“分”时，请填写整数值'));
                                        }
                                    }
                                }
                                if ($form['modify_add_latest_limit'][$i] == 'customize') {
                                    $tmp_value  = $form['modify_add_latest_time'][$i];
                                    $tmp_format = $form['modify_add_latest_format'][$i];
                                    if ($tmp_format == 'i') {
                                        if ((float) $tmp_value > (int) $tmp_value) {
                                            $form->set_error("specific_modify_latest_limit[$i]", I18N::T('eq_sample', '修改 / 删除送样最晚提前时间精确到“分”时，请填写整数值'));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$equipment->accept_sample) {
                    $properties->set('add_sample_earliest_limit', null, '*');
                    $properties->set('add_sample_latest_limit', null, '*');
                    $properties->set('modify_sample_latest_limit', null, '*');
                }

                if ($form['default_add_earliest'] == 'customize') {
                    $equipment->add_sample_earliest_limit = Date::convert_interval($form['add_sample_earliest_time'], $form['add_sample_earliest_format']);
                } else {
                    $equipment->add_sample_earliest_limit = null;
                }

                if ($form['default_add_latest'] == 'customize') {
                    $equipment->add_sample_latest_limit = Date::convert_interval($form['add_sample_latest_time'], $form['add_sample_latest_format']);
                } else {
                    $equipment->add_sample_latest_limit = null;
                }

                if ($form['default_modify_latest'] == 'customize') {
                    $equipment->modify_sample_latest_limit = Date::convert_interval($form['modify_sample_latest_time'], $form['modify_sample_latest_format']);
                } else {
                    $equipment->modify_sample_latest_limit = null;
                }

                $equipment->sample_require_pc = intval(($form['accept_sample'] == 'on') && ($form['sample_require_pc'] == 'on'));

                $default_add_sample_earliest_limit  = Lab::get('equipment.add_sample_earliest_limit');
                $default_add_sample_latest_limit    = Lab::get('equipment.add_sample_latest_limit');
                $default_modify_sample_latest_limit = Lab::get('equipment.modify_sample_latest_limit');

                //用于清空所有@TAG的配置
                $properties->set('specific_add_earliest_limit', null, '*', '@TAG_SAMPLE');
                $properties->set('specific_add_latest_limit', null, '*', '@TAG_SAMPLE');
                $properties->set('specific_modify_latest_limit', null, '*', '@TAG_SAMPLE');

                $special_tags = $form['special_tags'];

                if ($special_tags) {
                    foreach ($special_tags as $i => $tags) {
                        $tags = @json_decode($tags, true);

                        if ($tags) {
                            foreach ($tags as $tag) {
                                if ($form['specific_add_earliest_limit'][$i] == 'customize') {
                                    $specific_add_earliest_limit = Date::convert_interval($form['specific_add_earliest_time'][$i], $form['specific_add_earliest_format'][$i]);
                                    $properties->set('specific_add_earliest_limit', $specific_add_earliest_limit, $tag, '@TAG_SAMPLE');
                                } else {
                                    $properties->set('specific_add_earliest_limit', null, $tag, '@TAG_SAMPLE');
                                }

                                if ($form['specific_add_latest_limit'][$i] == 'customize') {
                                    $specific_add_latest_limit = Date::convert_interval($form['specific_add_latest_time'][$i], $form['specific_add_latest_format'][$i]);
                                    $properties->set('specific_add_latest_limit', $specific_add_latest_limit, $tag, '@TAG_SAMPLE');
                                } else {
                                    $properties->set('specific_add_latest_limit', null, $tag, '@TAG_SAMPLE');
                                }

                                if ($form['specific_modify_latest_limit'][$i] == 'customize') {
                                    $specific_modify_latest_limit = Date::convert_interval($form['specific_modify_latest_time'][$i], $form['specific_modify_latest_format'][$i]);
                                    $properties->set('specific_modify_latest_limit', $specific_modify_latest_limit, $tag, '@TAG_SAMPLE');
                                } else {
                                    $properties->set('specific_modify_latest_limit', null, $tag, '@TAG_SAMPLE');
                                }
                            }
                        }
                    }
                }

                Event::trigger('equipment[edit].sample.post_submit', $form, $equipment);

                $times = Event::trigger('eq_sample.equipment_edit_form_submit', $equipment, 'sample');

                if ($equipment->save()) {
                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    /* 记录日志 */
                    Log::add(strtr('[eq_sample] %user_name[%user_id]修改了%equipment_name[%equipment_id]的送样设置', [
                        '%user_name'      => L('ME')->name,
                        '%user_id'        => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id'   => $equipment->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '设备送样设置已更新!'));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '设备送样设置更新失败!'));
            }
        } else {
            $times = Event::trigger('eq_sample.equipment_edit_form_submit', $equipment, 'sample');
        }

        if (!is_null($properties->get('add_sample_earliest_limit', '@'))) {
            list($add_sample_earliest_time, $add_sample_earliest_format) = Date::format_interval($properties->get('add_sample_earliest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('add_sample_latest_limit', '@'))) {
            list($add_sample_latest_time, $add_sample_latest_format) = Date::format_interval($properties->get('add_sample_latest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('modify_sample_latest_limit', '@'))) {
            list($modify_sample_latest_time, $modify_sample_latest_format) = Date::format_interval($properties->get('modify_sample_latest_limit', '@'), 'hid');
        }

        $tabs->content = V('eq_sample:edit/eq_sample', [
            'equipment'                   => $equipment,
            'add_sample_earliest_time'    => $add_sample_earliest_time,
            'add_sample_latest_time'      => $add_sample_latest_time,
            'add_sample_earliest_format'  => $add_sample_earliest_format,
            'add_sample_latest_format'    => $add_sample_latest_format,
            'modify_sample_latest_time'   => $modify_sample_latest_time,
            'modify_sample_latest_format' => $modify_sample_latest_format,
            'form'                        => $form,
            'times'                       => $times,
        ]);
    }

    public static function sample_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->is_allowed_to('列表送样请求', $equipment)) {
            $tabs
                ->add_tab('sample', [
                    'url'=>Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.sample") ?: $equipment->url('sample'),
                    'title'=>I18N::T('eq_sample', '送样预约'),
                    'weight' => 20
                ]);
        }
    }

    public static function sample_tab_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        Event::trigger('sample.tab.content.validate', $equipment);
        $params = Config::get('system.controller_params');

        Event::bind('equipment.eq_sample.tab.content', 'EQ_Sample::_sample_tab_content_list', 0, 'list');
        Event::bind('equipment.eq_sample.tab.content', 'EQ_Sample::_sample_tab_content_calendar', 10, 'calendar');

        // 仪器送样按时间搜索：如果下次未按时间搜索应取消上次的选择操作
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_sample_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                // 设置默认排序(能防止第一次点击排序无效的方法)
                if (!isset($form['sort'])) {
                    $form['sort']     = 'ctime';
                    $form['sort_asc'] = false;
                }

                if ($form['status'][0] == -1) {
                    unset($form['status'][0]);
                }

            });
        }

        // 获取search_box和table的条目
        $field                      = self::get_sample_field($equipment, $form);
        $columns                    = new ArrayObject($field);
        $tabs->content              = V('eq_sample:tabs');
        $tabs->content->calendar = O('calendar', [
            'parent' => $equipment,
            'type'   => 'eq_sample',
        ]);
        $tabs->content->fourth_tabs = Widget::factory('tabs')
            ->add_tab('list', [
                'url'    => $equipment->url('sample.list'),
                'title'  => I18N::T('eq_sample', '列表'),
                'weight' => 10,
            ])
            ->add_tab('calendar', [
                'url'    => $equipment->url('sample.calendar'),
                'title'  => I18N::T('eq_sample', '日历'),
                'weight' => 20,
            ])
            ->content_event('equipment.eq_sample.tab.content')
            ->set('class', 'fourth_tabs float_left')
            ->set('columns', $columns)
            ->set('equipment', $equipment)
            ->set('form', $form)
            ->set('status', $params[3])
            ->select($params[2]);
    }

    //送样tabcontent二级tab calendar
    public static function _sample_tab_content_calendar($e, $tabs)
    {
        $equipment = $tabs->equipment;

        $calendar = O('calendar', [
            'parent' => $equipment,
            'type'   => 'eq_sample',
        ]);

        //创建calendar
        if (!$calendar->id) {
            $calendar->parent = $equipment;
            $calendar->type   = 'eq_sample';
            $calendar->name   = I18N::T('eq_sample', '%name送样记录日历', ['%name' => $equipment->name]);
            $calendar->save();
        }
        $tabs->content = V('eq_sample:calendar', [
            'equipment'   => $equipment,
            'calendar'    => $calendar,
            'hidden_tabs' => true,
        ]);

        Controller::$CURRENT->add_css('preview');
        Controller::$CURRENT->add_js('preview');
    }

    public static function get_form()
    {
        $form       = null;
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_sample_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                // 设置默认排序(能防止第一次点击排序无效的方法)
                if (!isset($form['sort'])) {
                    $form['sort']     = 'ctime';
                    $form['sort_asc'] = false;
                }
            });
        }
        $form['form_token']    = $form_token;
        $_SESSION[$form_token] = $form;
        return $form;
    }

    // 原有列表机制
    public static function _sample_tab_content_list($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $status    = $tabs->status;
        // 多栏搜索
        // 仪器送样按时间搜索：如果下次未按时间搜索应取消上次的选择操作
        $form       = $tabs->form;
        $form_token = $form['form_token'];

        $pre_selectors = [];
        $user          = L('ME');
        $selector      = " eq_sample[equipment=$equipment]";

        // 只搜索自己的送样
        if ($user->is_allowed_to('查看所有送样记录', $equipment)) {
            if ($form['sender']) {
                $name                    = Q::quote(trim($form['sender']));
                $pre_selectors['sender'] = "user<sender[name*=$name|name_abbr*=$name]";
            }
        } else {
            $selector .= "[sender={$user}]";
        }

        if ($form['count']) {
            $count = Q::quote($form['count']);
            $selector .= "[count=$count]";
        }

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        // 按时间搜索
        // 送样时间
        if ($form['dtsubmit_dtstart']) {
            $dtstart = Q::quote($form['dtsubmit_dtstart']);
            $selector .= "[dtsubmit>=$dtstart]";
        }
        if ($form['dtsubmit_dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtsubmit_dtend']));
            $selector .= "[dtsubmit>0][dtsubmit<=$dtend]";
        }

        if ($form['lab_name']) {
            $lab_name                  = Q::quote(trim($form['lab_name']));
            $pre_selectors['lab_name'] = "lab[name*={$lab_name}|name_abbr*={$lab_name}] user<sender";
        }

        // 测样时间
        /*
        if($form['dtrial_dtstart_check']){
        $dtstart = Q::quote($form['dtrial_dtstart']);
        $selector .= "[dtstart>=$dtstart]";
        }
        if($form['dtrial_dtend_check']){
        $dtend = Q::quote($form['dtrial_dtend']);
        $selector .= "[dtstart>0][dtstart<=$dtend]";
        }
         */

        // 取样时间
        if ($form['dtpickup_dtstart']) {
            $dtstart = Q::quote($form['dtpickup_dtstart']);
            $selector .= "[dtpickup>=$dtstart|dtpickup=0]";
        }
        if ($form['dtpickup_dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtpickup_dtend']));
            $selector .= "[dtpickup>0][dtpickup<=$dtend]";
        }

        /*
        如果未按送样时间搜索
        则建议搜索条件为一个月内
        (xiaopei.li@2011.08.15)
         */
        // $today = getdate(time());
        // $recommemded_dtstart_search = mktime(23, 59, 59, $today['mon'], $today['mday'], $today['year']);
        // $recommemded_dtend_search = $recommemded_dtstart_search - 2592000;

        /*if (!$form['dtsubmit_dtstart'] && !$form['dtsubmit_dtend']) {
        $form['dtsubmit_dtend'] = $recommemded_dtstart_search;
        $form['dtsubmit_dtstart'] = $recommemded_dtend_search;
        }
        if (!$form['dtpickup_dtstart'] && !$form['dtpickup_dtend']) {
        $form['dtpickup_dtend'] = $recommemded_dtstart_search;
        $form['dtpickup_dtstart'] = $recommemded_dtend_search;
        }*/

        if ($form['operator']) {
            $name                      = Q::quote(trim($form['operator']));
            $pre_selectors['operator'] = "user<operator[name*=$name|name_abbr*=$name]";
        }

        $group = O('tag_group', $form['group']);
        $group_root = Tag_Model::root('group');

        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selectors['group'] = "{$group} user<sender";
        } else {
            unset($group);
        }

        $status_arr = [];
        $status_list = Event::trigger('sample.status') ?: EQ_Sample_Model::$status;

        if (Config::get('sample_approval.to_equipment') && !$equipment->sample_approval_enable) {
            unset($status_list[Sample_Approval_Model::STATUS_OFFICE]);
            unset($status_list[Sample_Approval_Model::STATUS_PLATFORM]);
            unset($status_list[Sample_Approval_Model::STATUS_ACCESS]);
        }

        if ($status) {
            unset($form['status']);
            $selector .= '[status=' . $status . ']';
            $status_arr[$status] = I18N::T('eq_sample', $status_list[$status]);
            /*原来的搜索栏下status是radio_button传递，格式为[status]=>"on"
            改成selector后status传递格式变为[0]=>[status],所以这里作相应修改
             */
            //$form['status'] = [$status => 'on'];
            $form['status'] = [[0] => $status];
        }
        if ($form['status']) {
            $status_selector = implode(',', $form['status']);
            $selector .= '[status=' . $status_selector . ']';
            foreach ($form['status'] as $key => $value) {
                $status_arr[$key] = I18N::T('eq_sample', $status_list[$key]);
            }
        } else {
            $status_selector = join(',', array_keys($status_list));
            $selector .= '[status=' . $status_selector . ']';
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }
        //排序
        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'serial_number':
                $selector .= ":sort(id {$sort_flag})";
                break;
            case 'equipment':
                $selector .= ":sort(equipment_abbr {$sort_flag})";
                break;
            case 'sender':
                $selector .= ":sort(sender_abbr {$sort_flag})";
                break;
            case 'operator':
                $selector .= ":sort(operator_abbr {$sort_flag})";
                break;
            case 'ctime':
                $selector .= ":sort(ctime {$sort_flag})";
                break;
            case 'dtsubmit':
                $selector .= ":sort(dtsubmit {$sort_flag})";
                break;
            case 'dtctime':
                $selector .= ":sort(ctime {$sort_flag})";
                break;
            //case 'dtrial' :
            //$selector = ":sort(dtstart {$sort_flag})";
            //break;
            case 'dtpickup':
                $selector .= ":sort(dtpickup {$sort_flag})";
                break;
            case 'count':
                $selector .= ":sort(count {$sort_flag})";
                break;
            case 'status':
                $selector .= ":sort(status {$sort_flag})";
                break;
            default:
                $selector .= ':sort(id D)';
                break;
        }

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $form['equipment_id'] = $equipment->id;
        $form['page'] = 'equipment_sample_list';
        $_SESSION[$form_token] = $form;

        $me = L('ME');
        $panel_buttons = [];
        if ($me->is_allowed_to('添加送样记录', $equipment)) {
            if (Module::is_installed('db_sync')
                && DB_SYNC::is_slave()
                && DB_SYNC::is_module_unify_manage('eq_sample')) {
                $panel_buttons[] = [
                    'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params'=>[
                        'q-object' => 'add_sample_record',
                        'q-event' => 'click',
                        'q-static' => ['id' => $equipment->id],
                        'q-src' => Event::trigger('db_sync.transfer_to_master_url', '!eq_sample/index', '', true),
                    ]]),
                    'text'=>I18N::HT('eq_sample', '添加送样记录'),
                    'extra'=>'class="button button_add middle"',
                ];
            }else{
                $panel_buttons[] = [
                    'text'=>I18N::HT('eq_sample', '添加送样记录'),
                    'extra'=>'q-object="add_sample_record" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                        '" q-static="' . H(['id'=>$equipment->id]) .
                        '" class="button button_add middle"',
                ];
            }
        } else {
            if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage()) {
                $panel_buttons[] = [
                    'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params'=>[
                        'q-object' => 'add_sample',
                        'q-event' => 'click',
                        'q-static' => ['id' => $equipment->id],
                        'q-src' => Event::trigger('db_sync.transfer_to_master_url', '!eq_sample/index', '', true),
                    ]]),
                    'text'=>I18N::HT('eq_sample', '申请送样'),
                    'extra'=>'class="button button_add middle"',
                ];
            }else{
                $panel_buttons[] = [
                    'text'=>I18N::HT('eq_sample', '申请送样'),
                    'extra'=>'q-object="add_sample" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                        '" q-static="' . H(['id'=>$equipment->id]) .
                        '" class="button button_add middle"',
                ];
            }
        }
        if ($me->is_allowed_to('导出送样记录', $equipment)) {
            $panel_buttons[] = [
                'text'=>I18N::T('eq_sample', '导出Excel'),
                'extra'=>'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                    '" q-static="' . H(['type'=>'csv', 'form_token'=>$form_token]) .
                    '" class="button button_save middle"',
            ];
            $panel_buttons[] = [
                'text'=>I18N::T('eq_sample', '打印'),
                'extra'=>'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                    '" q-static="' . H(['type'=>'print', 'form_token'=>$form_token]) .
                    '" class="button button_print middle"',
            ];
        }

        $form['form_token']    = $form_token;
        $form['selector']      = $selector;
        $form['equipment_id']  = $equipment->id;
        $form['page']          = 'equipment_sample_list';
        $_SESSION[$form_token] = $form;

        $samples    = Q($selector);
        $pagination = Lab::pagination($samples, (int) $form['st'], 15);
        //field
        $field   = self::get_sample_field($equipment, $form);
        $columns = new ArrayObject($field);
        Controller::$CURRENT->add_css('eq_sample:common');

        $tabs->content = V('eq_sample:list', [
            'equipment'  => $equipment,
            'samples'    => $samples,
            'pagination' => $pagination,
            'form'       => $form,
            'columns'    => $columns,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'time'       => strtotime('+1 day'),
            'status_arr' => $status_arr,
            'form_token' => $form_token,
        ]);
    }

    public static function sample_before_save($e, $sample, $data)
    {
        // 如果PI开启了审批，以下逻辑就会跳过PI审批环境，故此处注释，还是走一遍审批环节
        // $equipment = $sample->equipment;
        // $me        = L('ME');
        // $now       = Date::time();

        // //自动批准送样申请。该处不会陷入死循环，已测试
        // if (
        //     $equipment->sample_autoapply//自动批准
        //      &&
        //     !$sample->id//新创建
        //      &&
        //     $sample->status == EQ_Sample_Model::STATUS_APPLIED//申请中
        //      &&
        //     !$sample->sender->is_allowed_to('添加送样记录', $equipment) //普通用户申请
        // ) {
        //     //自动批准
        //     error_log(__FILE__);

        //     $sample->status = EQ_Sample_Model::STATUS_APPROVED;
        // }
    }

    /*
    保存eq_sample后触发事件
    发送提醒消息
    申请送样:向仪器负责人发送提醒消息
    批准/拒绝/因故取消送样申请：向仪器负责人发送提醒消息
     */
    public static function on_sample_saved($e, $sample, $old_data, $new_data)
    {
        if ($new_data['id'] && !$old_data['id']) {

            //默认的添加、修改, 进行消息提醒
            if (array_key_exists($new_data['status'], Event::trigger('sample.status'))) {
                $now       = Date::time();
                $equipment = $sample->equipment;

                Notification::send('eq_sample.add_sample.sender', $sample->sender, [
                    '%time'    => Date::format($now, 'Y/m/d H:i:s'),
                    '%eq_name' => Markup::encode_Q($equipment),
                ]);

                //equipment contact
                foreach (Q("$equipment user.contact") as $contact) {
                    //eq_contact
                    Notification::send('eq_sample.add_sample.eq_contact', $contact, [
                        '%eq_name' => Markup::encode_Q($equipment),
                        '%time'    => Date::format($now, 'Y/m/d H:i:s'),
                        '%sender'  => Markup::encode_Q($sample->sender),
                    ]);
                }

                //pi
                if(!Event::trigger('eq_sample.add_sample_notification_pi.custom',$sample)){
                    Notification::send('eq_sample.add_sample.pi', $sample->lab->owner, [
                        '%eq_name'=> Markup::encode_Q($equipment),
                        '%time'=> Date::format($now, 'Y/m/d H:i:s'),
                        '%user'=> Markup::encode_Q($sample->sender)
                    ]);
                }
            }

            //旧值不存在说明为新增。同步上传的文件到$note的目录
            $old_path = NFS::get_path(O('eq_sample'), '', 'attachments', true);
            $new_path = NFS::get_path($sample, '', 'attachments', true);
            NFS::move_files($old_path, $new_path);
        }
    }

    /**
     *为仪器添加送样预约的状态
     */
    public static function equipment_status_tag($e, $equipment)
    {
        if ($equipment->accept_sample) {
            $url = $equipment->url('sample');
            $e->return_value .= '<a href="' . $url . '" class="prevent_default status_tag status_tag_warning">' . I18N::HT('eq_sample', '送样') . '</a> ';
        }
    }

    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'EQ_Sample::user_sample_tab');
        Event::bind('profile.view.content', 'EQ_Sample::user_sample_content', 0, 'eq_sample');
        Event::bind('profile.view.tool_box', 'EQ_Sample::_tool_box_user_view_sample', 0, 'eq_sample');
    }

    public static function user_sample_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');

        if ($me->is_allowed_to('列表个人页面送样预约', $user)) { /* TODO 仅能看自己的送样，未加权限 */
            $tabs->add_tab('eq_sample', [
                'url'   => $tabs->user->url('eq_sample'),
                'title' => I18N::T('eq_sample', '仪器送样'),
            ]);
        }
    }

    public static function user_sample_content($e, $tabs)
    {
        $me   = L('ME');
        $user = $tabs->user;

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_sample_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                if ($form['status'][0] == -1) {
                    unset($form['status'][0]);
                }

                if (isset($form['send_time_date_filter'])) {
                    if (!$form['send_time_dtstart_check']) {
                        unset($old_form['send_time_dtstart_check']);
                    }
                    if (!$form['send_time_dtend_check']) {
                        unset($old_form['send_time_dtend_check']);
                    } else {
                        $form['send_time_dtend'] = Date::get_day_end($form['send_time_dtend']);
                    }
                    unset($form['send_time_date_filter']);
                }
                // 设置默认排序(能防止第一次点击排序无效的方法)
                if (!isset($form['sort'])) {
                    $form['sort']     = 'dtsubmit';
                    $form['sort_asc'] = false;
                }
            });
        }
        $pre_selectors = new ArrayIterator;
        $selector = " eq_sample[sender={$user}]";

        if ($user->id != $me->id && Q("$me<incharge equipment eq_sample[sender={$user}]")->total_count()) {
            // $selector = "{$me}<incharge equipment ". $selector;
            //虽然是机主，但是如果他有能看见所有送样的权限，也不需要限制送样
            if (!(Q("($user, $me) lab")->total_count() && $me->access('查看本实验室成员送样记录')) &&
                !(Q("($user, $me<pi) lab")->total_count() && $me->access('查看负责实验室成员送样记录')) &&
                !$me->access('查看所有实验室的成员送样记录') &&
                !($me->access('查看下属实验室的成员送样记录') && $me->group->is_itself_or_ancestor_of($user->group))

            ) {
                    $pre_selectors[] = "{$me}<incharge equipment ";
            }
        }


        if ($form['equipment_name']) {
            $equipment_name = Q::quote($form['equipment_name']);
            $pre_selectors['equipment'] = "equipment[name*=$equipment_name]";
            $search = true;
        }

        if ($form['count']) {
            $count = Q::quote($form['count']);
            $selector .= "[count=$count]";
        }

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        //按时间搜索
        //送样时间
        if ($form['dtsubmit_dtstart']) {
            $form['dtsubmit_dtstart'] = Date::get_day_start($form['dtsubmit_dtstart']);
            $dtstart                  = Q::quote($form['dtsubmit_dtstart']);
            $selector .= "[dtsubmit>=$dtstart]";
        }
        if ($form['dtsubmit_dtend']) {
            $form['dtsubmit_dtend'] = Date::get_day_end($form['dtsubmit_dtend']);
            $dtend                  = Q::quote($form['dtsubmit_dtend']);
            $selector .= "[dtsubmit>0][dtsubmit<=$dtend]";
        }

        //测样时间
        /*
        if($form['dtrial_dtstart_check']){
        $dtstart = Q::quote($form['dtrial_dtstart']);
        $selector .= "[dtstart>=$dtstart]";
        }
        if($form['dtrial_dtend_check']){
        $dtend = Q::quote($form['dtrial_dtend']);
        $selector .= "[dtstart>0][dtstart<=$dtend]";
        }
         */

        // 取样时间
        if ($form['dtpickup_dtstart']) {
            $dtstart = Q::quote($form['dtpickup_dtstart']);
            $selector .= "[dtpickup>=$dtstart|dtpickup=0]";
        }
        if ($form['dtpickup_dtend']) {
            $dtend = Q::quote($form['dtpickup_dtend']);
            $selector .= "[dtpickup>0][dtpickup<=$dtend]";
        }

        if ($form['operator']) {
            $name                      = Q::quote(trim($form['operator']));
            $pre_selectors['operator'] = 'user<operator[name*=' . $name . ']';
        }

        if ($form['status']) {
            foreach ($form['status'] as $k) {
                $s[] = $k;
            }

            $status = join(',', $s);

            $selector .= '[status=' . $status . ']';
        }

        //排序
        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'serial_number':
                $selector .= ":sort(id {$sort_flag})";
                break;
            case 'equipment_name':
                $selector .= ":sort(equipment_abbr {$sort_flag})";
                break;
            case 'sender':
                $selector .= ":sort(sender_abbr {$sort_flag})";
                break;
            case 'operator':
                $selector .= ":sort(operator_abbr {$sort_flag})";
                break;
            case 'ctime':
                $selector .= ":sort(ctime {$sort_flag})";
                break;
            case 'dtsubmit':
                $selector .= ":sort(dtsubmit {$sort_flag})";
                break;
            //case 'dtrial' :
            //$selector = ":sort(dtstart {$sort_flag})";
            //break;
            case 'dtpickup':
                $selector .= ":sort(dtpickup {$sort_flag})";
                break;
            case 'count':
                $selector .= ":sort(count {$sort_flag})";
                break;
            case 'status':
                $selector .= ":sort(status {$sort_flag})";
                break;
            default:
                $selector .= ':sort(id D)';
                break;
        }

        $new_selector = Event::trigger('eq_sample.search.filter.submit', $selector, $form, $pre_selectors);
        if ($new_selector) {
            $selector = $new_selector;
        }

        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array) $pre_selectors).') ' . $selector;
        }

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $samples               = Q($selector);
        $pagination            = Lab::pagination($samples, (int) $form['st'], 15);

        $fields        = self::get_sample_field_with_user_sample($form, ['user' => $user]);
        $tabs->columns = new ArrayObject($fields);
        $tabs->form_token = $form_token;

        $tabs->content = V('eq_sample:samples_for_profile', [
            'form_token' => $form_token,
            'user'       => $user,
            'samples'    => $samples,
            'columns'    => $tabs->columns,
            'pagination' => $pagination,
            'form'       => $form,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'time'       => strtotime("+1 day"),
        ]);
    }

    public static function _tool_box_user_view_sample($e, $tabs)
    {
        $me = L('ME');

        $equipment  = $tabs->equipment;
        $form_token = $tabs->form_token;
        unset($tabs->form_token);

        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '导出Excel'),
            'text' => I18N::T('equipments', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_sample/export') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
         $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '打印'),
            'text' => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_sample/export') . '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['serial_number'], 'columns' => (array) $tabs->columns]);

    }

    public static function get_sample_field_with_user_sample($form, $search_box_need_param = [])
    {
        $me = L('ME');

        if (is_array($search_box_need_param)) {
            extract($search_box_need_param);
        }

        if ($form['dtsubmit_dtstart'] || $form['dtsubmit_dtend']) {
            $form['dtsubmit_date'] = true;
        }

        if ($form['dtpickup_dtstart'] || $form['dtpickup_dtend']) {
            $form['dtpickup_date'] = true;
        }

        $fields = [
            'serial_number'  => [
                'title'    => I18N::T('eq_sample', '编号'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/serial_number', ['id' => $form['id']]),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'weight'   => 10,
                'nowrap'   => true,
            ],
            'equipment_name' => [
                'title'    => I18N::T('eq_sample', '仪器'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/equipment_name', ['equipment_name' => $form['equipment_name']]),
                    'value' => $form['equipment_name'] ? H($form['equipment_name']) : null,
                ],
                'weight'   => 20,
                'nowrap'   => true,
            ],
            'count'          => [
                'title'    => I18N::T('eq_sample', '样品数'),
                'align'    => 'right',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/count', ['count' => $form['count']]),
                    'value' => $form['count'] ? H($form['count']) : null,
                ],
                'weight'   => 30,
                'nowrap'   => true,
            ],
            'dtsubmit'       => [
                'title'    => I18N::T('eq_sample', '送样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix'   => 'dtsubmit_',
                        'dtstart_check' => $form['dtsubmit_dtstart_check'],
                        'dtstart'       => $form['dtsubmit_dtstart'],
                        'dtend_check'   => $form['dtsubmit_dtend_check'],
                        'dtend'         => $form['dtsubmit_dtend'],
                    ]),
                    'value' => $form['dtsubmit_date'] ? H($form['dtsubmit_date']) : null,
                    'field' => 'dtsubmit_dtstart_check,dtsubmit_dtstart,dtsubmit_dtend_check,dtsubmit_dtend',
                ],
                'weight'   => 50,
                'nowrap'   => true,
            ],
            /*
            'dtrial'=>array(
            'title'=>I18N::T('eq_sample', '测样时间'),
            'align'=>'left',
            'sortable'=>TRUE,
            'filter'=> array(
            'form' => V('eq_sample:samples_table/filters/date', array(
            'name_prefix'=>'dtrial_',
            'dtstart_check'=>$form['dtrial_dtstart_check'],
            'dtstart'=>$form['dtrial_dtstart'],
            'dtend_check'=>$form['dtrial_dtend_check'],
            'dtend'=>$form['dtrial_dtend']
            )),
            'value' => $form['dtrial_date'] ? H($form['dtrial_date']) : NULL,
            'field'=>'dtrial_dtstart_check,dtrial_dtstart,dtrial_dtend_check,dtrial_dtend'
            ),
            'nowrap'=>TRUE,
            ),
             */
            'dtpickup'       => [
                'title'    => I18N::T('eq_sample', '取样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix'   => 'dtpickup_',
                        'dtstart_check' => $form['dtpickup_dtstart_check'],
                        'dtstart'       => $form['dtpickup_dtstart'],
                        'dtend_check'   => $form['dtpickup_dtend_check'],
                        'dtend'         => $form['dtpickup_dtend'],
                    ]),
                    'value' => $form['dtpickup_date'] ? H($form['dtpickup_date']) : null,
                    'field' => 'dtpickup_dtstart_check,dtpickup_dtstart,dtpickup_dtend_check,dtpickup_dtend',
                ],
                'weight'   => 60,
                'nowrap'   => true,
            ],
            'status'         => [
                'title'    => I18N::T('eq_sample', '状态'),
                'align'    => 'center',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/status', [
                        'status'  => $form['status'],
                        '_type'   => 'profile', //标记为 profile
                        '_object' => $user,
                    ]),
                    /* 'value' => V('eq_sample:samples_table/filters/status.value', [
                        'status'  => $form['status'],
                        '_type'   => 'profile',
                        '_object' => $user,
                    ]), */
                    'value' => !!$form['status'],
                ],
                'weight'   => 70,
                'nowrap'   => true,
            ],
            'operator'       => [
                'title'    => I18N::T('eq_sample', '操作者'),
                'align'    => 'center',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/operator', ['operator' => $form['operator']]),
                    'value' => $form['operator'] ? H($form['operator']) : null,
                ],
                'weight'   => 80,
                'nowrap'   => true,
            ],
            'fee'            => [
                'title'  => I18N::T('eq_sample', '收费'),
                'align'  => 'right',
                'weight' => 90,
                'nowrap' => true,
            ],
            'description'    => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
            ],
            /* 'description2'    => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
            ],
            'description3'    => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
            ], */
            'rest'           => [
                'title'       => I18N::T('eq_sample', '操作'),
                'align'       => 'left',
                'extra_class' => '',
                'weight'      => 110,
                'nowrap'      => true,
            ],
        ];

        $columns = new ArrayObject($fields);
        Event::trigger('eq_sample.table_list.columns', $form, $columns);

        return $columns;
    }

    public static function get_sample_field($equipment, $form)
    {

        // 送样时间
        if ($form['dtsubmit_dtstart'] && $form['dtsubmit_dtend']) {
            $form['dtsubmit_date'] = true;
        }

        // 取样时间
        if ($form['dtpickup_dtstart'] && $form['dtpickup_dtend']) {
            $form['dtpickup_date'] = true;
        }

        $group_root = Tag_Model::root('group');
        $field      = [
            // '@'=>NULL,
            'group'         => [
                'title'     => I18N::T('eq_sample', '组织机构'),
                'align'     => 'left',
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_sample:samples_table/filters/group', ['group' => $form['group']]),
                    'value' => ($form['group'] && $form['group'] != $group_root->id) ? H(O('tag', $form['group'])->name) : null,
                ],
                'weight'    => 10,
            ],
            'status'        => [
                'title'     => I18N::T('eq_sample', '状态'),
                'align'     => 'center',
                'invisible' => true,
                'sortable'  => true,
                'filter'    => [
                    'form'  => V('eq_sample:samples_table/filters/status', [
                        'name_prefix' => 'dtsubmit_',
                        'status'      => $form['status'],
                        '_type'       => 'equipment', // 标记为 Equipment
                    ]),
                    // 'value' => V('eq_sample:samples_table/filters/status.value', ['status' => $status_arr]),
                    'value' => $form['status'] ? (implode(', ', array_map(function ($k) {
                        return EQ_Sample_Model::$status[$k];
                    }, $form['status']))) : '',
                ],
                'nowrap'    => true,
                'weight'    => 15,
            ],
            'lab_name'      => [
                'title'     => I18N::T('eq_sample', '课题组'),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_sample:samples_table/filters/lab_name', ['lab_name' => $form['lab_name']]),
                    'value' => $form['lab_name'] ? H($form['lab_name']) : null,
                ],
                'nowrap'    => true,
                'weight'    => 35,
            ],

            'serial_number' => [
                'title'       => I18N::T('eq_sample', '编号'),
                'align'       => 'left',
                'extra_class' => 'num',
                'sortable'    => true,
                'filter'      => [
                    'form'  => V('eq_sample:samples_table/filters/serial_number', ['id' => $form['id'], 'tip' => '请输入编号']),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'nowrap'      => true,
                'weight'      => 20,
            ],
            'sender'        => [
                'title'    => I18N::T('eq_sample', '申请人'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/sender', ['sender' => $form['sender'], 'tip' => '请输入申请人']),
                    'value' => $form['sender'] ? H($form['sender']) : null,
                ],
                'nowrap'   => true,
                'weight'   => 30,
            ],
            'count'         => [
                'title'    => I18N::T('eq_sample', '样品数'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/count', ['count' => $form['count']]),
                    'value' => $form['count'] ? H($form['count']) : null,
                ],
                'nowrap'   => true,
                'weight'   => 40,
            ],
            'dtctime'       => [
                'title'    => I18N::T('eq_sample', '送样申请时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'value' => $form['dtctime'] ? H($form['dtctime']) : null,
                ],
                'nowrap'   => true,
                'weight'   => 40,
            ],
            'dtsubmit'      => [
                'title'    => I18N::T('eq_sample', '送样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix' => 'dtsubmit_',
                        'dtstart'     => $form['dtsubmit_dtstart'],
                        'dtend'       => $form['dtsubmit_dtend'],
                    ]),
                    'value' => $form['dtsubmit_date'] ? H($form['dtsubmit_date']) : null,
                    'field' => 'dtsubmit_dtstart,dtsubmit_dtend',
                ],
                'nowrap'   => true,
                'weight'   => 50,
            ],
            'dtpickup'      => [
                'title'    => I18N::T('eq_sample', '取样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix' => 'dtpickup_',
                        'dtstart'     => $form['dtpickup_dtstart'],
                        'dtend'       => $form['dtpickup_dtend'],
                    ]),
                    'value' => $form['dtpickup_date'] ? H($form['dtpickup_date']) : null,
                    'field' => 'dtpickup_dtstart,dtpickup_dtend',
                ],
                'nowrap'   => true,
                'weight'   => 60,
            ],
            'operator'      => [
                'title'    => I18N::T('eq_sample', '操作者'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/operator', ['operator' => $form['operator']]),
                    'value' => $form['operator'] ? H($form['operator']) : null,
                ],
                'nowrap'   => true,
                'weight'   => 80,
            ],
            'fee'           => [
                'title'       => I18N::T('eq_sample', '收费'),
                'align'       => 'left',
                'extra_class' => 'fee',
                'nowrap'      => true,
                'weight'      => 90,
            ],
            'description'   => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 100,
            ],
            'rest0'         =>
            [
                'title'       => I18N::T('eq_sample', '状态'),
                'align'       => 'left',
                'extra_class' => 'status',
                'nowrap'      => true,
                'weight'      => 19,
            ],
            'rest'          => [
                'title'  => I18N::T('eq_sample', '操作'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 120,
            ],
        ];

        if (!L('ME')->is_allowed_to('查看所有送样记录', $equipment)) {
            // 普通用户不需要组织机构和送样者搜索
            unset($field['group']);
            unset($field['sender']['filter']);
        }
        return $field;
    }

    public static function get_message_title($e, $eid)
    {
        if (!$eid) {
            return false;
        }

        $equipment       = O('equipment', $eid);
        $e->return_value = I18N::T('eq_sample', '关于您对【%ename】的送样申请', ['%ename' => $equipment->name]);
        return false;
    }

    public static function before_user_save_message($e, $user)
    {
        if (Q("eq_sample[sender={$user}]")->total_count()) {
            $e->return_value = I18N::T('eq_sample', '该用户关联了相应的送样记录!');
            return false;
        }
    }

    public static function extra_form_validate($e, $object, $type, $form)
    {
        if ($object->name() == 'equipment' && $type == 'use') {
            $equipment        = $object;
            $connect_samples  = $form['sample_records'] && count((array) @json_decode($form['sample_records'], true));
            $user_id          = $form['user_id'];
            $user_is_incharge = Q("{$equipment} user[id={$user_id}].incharge")->total_count();
            if ($connect_samples && !$user_is_incharge) {
                $form->set_error('user_id', I18N::T('eq_record', '只有仪器负责人的使用记录才能关联送样记录!'));
            }
        }

        if (Lab::get('eq_sample.response_time')) {
            if ($form['status'] == EQ_Sample_Model::STATUS_TESTED) {
                $has_records = false;
                if ($type == 'eq_sample' && $form['id']) {
                    $sample = O('eq_sample', $form['id']);
                    $has_records = Q("$sample eq_record")->total_count();
                }
                if (!$has_records && $form['dtrial_check'] != 'on') {
                    $form->set_error('dtend', I18N::T('eq_sample', '请输入测样时间!'));
                }
            }
        }
        
        //验证送样数限额
        if($object->sample_counts_limit){
            $current_count = 0;
            $start = strtotime(date('Y-m-d',$form['dtsubmit']));
            $end = $start + 86400 - 1;
            $estatus = EQ_Sample_Model::STATUS_APPROVED.','.EQ_Sample_Model::STATUS_TESTED;
            $rows = Q("eq_sample[equipment={$object}][status={$estatus}][dtsubmit>={$start}][dtsubmit<={$end}]");
            foreach ($rows as $row){
                if($row->id == $form['id'])
                    continue;
                $current_count += $row->count;
            }
            //剩余可测样品数
            $left_count = ($object->sample_counts_limit - $current_count) < 0 ? 0 : $object->sample_counts_limit - $current_count;
            $current_count += $form['count'];
            if($current_count > $object->sample_counts_limit)
                $form->set_error('count', I18N::T('eq_sample', '当日剩余可接样数('.$left_count.'), 您可关注后续日期进行申请!'));
        }
    }

    public static function record_edit_submit($e, $record, $form)
    {
        if (!Event::trigger('eq_sample_bind_mutile_record_set', $record, $form)) {
            $equipment = $record->equipment;
            if ($record->id && $equipment->accept_sample && isset($form['sample_records'])) {
                $samples = Q("$record eq_sample[equipment=$equipment]");
                $sids = array_keys((array)@json_decode($form['sample_records'], true));
                foreach ($sids as $sid) {
                    if (isset($samples[$sid])) {
                        unset($samples[$sid]);
                    }else {
                        $sample = O('eq_sample', $sid);
                        if ($sample->id) {
                            $sample->connect($record);
                        }
                    }
                }
            }

            foreach ($samples as $s) {
                $s->disconnect($record);

                $sc = O('eq_charge', ['source' => $s]);
                if (!$sc->id && !$equipment->charge_script['sample']) {
                    continue;
                }

                if (!$sc->source->id) {
                    $sc->source = $s;
                }

                $sc->user      = $s->sender;
                $sc->lab       = $GLOBALS['preload']['people.multi_lab'] ? $s->project->lab : Q("$s->sender lab")->current();
                $sc->equipment = $s->equipment;
                $sc->calculate_amount()->save();
                $s->save();
            }
        }
    }

    public static function record_edit_view($e, $record, $form, $sections)
    {
        if ((Config::get('extra.fudan.equipment.usetype') != null) && !Config::get('extra.fudan.equipment.usetype')) {
            return;
        }
        $equipment = $record->equipment;
        if ($record->id && $equipment->accept_sample) {
            if (!$samples = Event::trigger('eq_sample_bind_mutile_record', $record, $equipment)) {
                $samples = Q("$record eq_sample[equipment=$equipment]");
            }
            $tags = [];
            foreach ($samples as $sample) {
                $tags[$sample->id] = I18N::T('eq_sample', '%user(%num) %time', ['%user' => $sample->sender->name, '%time' => Date::format($sample->dtsubmit, 'Y/m/d H:i'), '%num' => $sample->count]);
            }
            $form['sample_records'] = json_encode($tags);
            $sections['eq_sample'] = V('eq_sample:record/edit', ['record'=>$record, 'form'=>$form]);
        }
    }

    public static function record_notes_view($e, $record)
    {
        $equipment = $record->equipment;
        if ($record->id && $equipment->accept_sample) {
            $samples = Q("$record eq_sample[equipment=$equipment]");
            if ($samples->total_count() > 0) {
                $e->return_value[] = V('eq_sample:record/notes', ['record' => $record, 'samples' => $samples]);
            }
        }
    }

    public static function record_description($e, $record)
    {
        $equipment = $record->equipment;
        if ($record->id && $equipment->accept_sample) {
            if (!$samples = Event::trigger('eq_sample_bind_mutile_record', $record, $equipment)) {
                $samples = Q("$record eq_sample[equipment=$equipment]");
            }
            if ($samples->total_count() > 0) {
                $quote       = I18N::HT('eq_sample', '存在%num条相关送样记录:', ['%num' => $samples->total_count()]);
                $description = '';
                foreach ($samples as $sample) {
                    $description .= '<div class="quote">' . $sample->sender->name;
                    $description .= ' <strong>(' . H($sample->count) . ')</strong> <span class="description">@ ' . Date::format($sample->dtsubmit, 'Y/m/d H:i') . '</span></div>';
                }
                $e->return_value[] = $quote . $description;
            }
        }
    }

    public static function record_description_csv($e, $record)
    {
        $equipment = $record->equipment;
        if ($record->id && $equipment->accept_sample) {
            if (!$samples = Event::trigger('eq_sample_bind_mutile_record', $record, $equipment)) {
                $samples = Q("$record eq_sample[equipment=$equipment]");
            }
            if ($samples->total_count() > 0) {
                $quote       = I18N::HT('eq_sample', '存在%num条相关送样记录:', ['%num' => $samples->total_count()]);
                $description = '';
                foreach ($samples as $sample) {
                    $description .= "\n" . $sample->sender->name;
                    $description .= ' (' . H($sample->count) . ') @ ' . Date::format($sample->dtsubmit, 'Y/m/d H:i');
                }
                $e->return_value[] = "\n" . $quote . $description;
            }
        }
    }

    public static function calendar_lines_get($e, $calendar, $dtstart, $dtend, $form)
    {
        if ($calendar->type != 'eq_sample') {
            return;
        }

        $ret = [];

        $equipment = $calendar->parent;

        foreach (Q("eq_sample[equipment={$equipment}][dtsubmit=$dtstart~$dtend|dtpickup=$dtstart~$dtend]") as $sample) {
            $temp = new stdClass();

            $temp->id = 'eq_sample_' . $sample->id;

            $temp->view = (string) V('eq_sample:calendar/line_content', [
                'sample' => $sample,
            ]);

            if ($sample->dtsubmit < $dtend && $sample->dtsubmit > $dtstart) {
                $temp->time       = $sample->dtsubmit;
                $temp->color_type = $sample->id % 6;
            }

            $ret[] = $temp;

            //如果dtpickup也在dtstart~dtend范围内，则增加另外一个dtpickup
            if ($sample->dtpickup < $dtend && $sample->dtpickup > $dtstart) {
                $temp2       = clone $temp;
                $temp2->time = $sample->dtpickup;
                $ret[]       = $temp2;
            }
        }

        $e->return_value = $ret;
    }

    //calendar中component列表获取, 通过eq_sample构造假的cal_component
    public static function calendar_components_get($e, $calendar, $components, $dtstart, $dtend, $limit = 0, $form = [])
    {
        $equipment = $calendar->parent;

        if ($calendar->type != 'eq_sample' || !$calendar->id) {
            return;
        }

        $equipment = $calendar->parent;
        $samples   = Q("eq_sample[equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");

        $ret = [];

        foreach ($samples as $s) {
            $temp = O('cal_component');

            $temp->calendar = $calendar;

            $temp->id = 'eq_sample_' . $s->id;

            $temp->sample = $s;

            $temp->dtstart = $s->dtstart;
            $temp->dtend   = $s->dtend;

            $temp->color = $s->id % 6;

            $ret[] = $temp;
        }

        $e->return_value = $ret;
        return false;
    }

    //component在calendar中显示content
    public static function calendar_components_content($e, $component, $current_calendar = null)
    {
        if ($component->calendar->type != 'eq_sample') {
            return;
        }

        $e->return_value = V('eq_sample:calendar/component_content', ['component' => $component]);

        return false;
    }

    public static function is_locked($e, $sample, $params)
    {
        if ($sample->id && $sample->is_locked) {
            $e->return_value = true;
            return false;
        }

        $time = max($sample->dtsubmit, $sample->dtpickup, $sample->dtstart, $sample->dtend);

        $transaction_locked_deadline = Lab::get('transaction_locked_deadline');

        if ($time < $transaction_locked_deadline) {
            $e->return_value = true;
            return false;
        }
    }

    public static function default_extra_setting_view($e, $uniqid, $field, $object, $prefix)
    {
        $e->return_value = (string) V("eq_sample:extra/setting/{$uniqid}", [
            'field'  => $field,
            'prefix' => $prefix,
        ]);
        return false;
    }

    public static function extra_check_field_title($e, $title, $extra)
    {
        if ($extra->type == 'eq_sample') {

            //存储系统默认locale
            $default_locale = $_SESSION['system.locale'];

            $self_fields = Config::get('extra.equipment.eq_sample');

            foreach ($self_fields as $category => $fields) {
                unset($fields['#i18n_module']);
                foreach ($fields as $f) {
                    $_title = $f['title'];

                    foreach (Config::get('system.locales') as $locale => $name) {
                        //清除自身模块I18N
                        I18N::clear_cache('eq_sample');
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

    public static function setup_lab()
    {
        Event::bind('lab.view.tab', 'EQ_Sample::lab_tab');
        Event::bind('lab.view.content', 'EQ_Sample::lab_tab_content', 0, 'eq_sample');
        Event::bind('lab.view.tool_box', 'EQ_Sample::lab_tab_tool', 0, 'eq_sample');
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }
        //取消现默认赋予给pi的权限
//        if (Q("$user<pi lab")->total_count()) {
//            $perms['查看负责实验室成员送样记录'] = 'on';
//        }
    }

    public static function lab_tab($e, $tabs)
    {
        $lab = $tabs->lab;
        $me  = L('ME');

        if ($me->is_allowed_to('列表仪器送样', $lab)) {
            $tabs->add_tab('eq_sample', [
                'url'   => $lab->url('eq_sample'),
                'title' => I18N::T('eq_sample', '仪器送样'),
            ]);
        }
    }

    public static function lab_tab_content($e, $tabs)
    {
        $me  = L('ME');
        $lab = $tabs->lab;

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_sample_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                if ($form['status'][0] == -1) {
                    unset($form['status'][0]);
                }

                if (isset($form['send_time_date_filter'])) {
                    if (!$form['send_time_dtstart_check']) {
                        unset($old_form['send_time_dtstart_check']);
                    }
                    if (!$form['send_time_dtend_check']) {
                        unset($old_form['send_time_dtend_check']);
                    } else {
                        $form['send_time_dtend'] = Date::get_day_end($form['send_time_dtend']);
                    }
                    unset($form['send_time_date_filter']);
                }
                // 设置默认排序(能防止第一次点击排序无效的方法)
                if (!isset($form['sort'])) {
                    $form['sort']     = 'dtsubmit';
                    $form['sort_asc'] = false;
                }
            });
        }
        $pre_selectors = new ArrayIterator;
        $selector = " eq_sample[lab={$lab}]";

        if (!Q("{$lab} {$me}")->total_count()
            && Q("($me<incharge equipment, {$lab} user<sender) eq_sample")->total_count()
            && !$me->access('管理所有内容')) {
            $selector = "{$me}<incharge equipment " . $selector;
        }

        if ($form['equipment_name']) {
            $equipment_name  = Q::quote(trim($form['equipment_name']));
            $pre_selectors['equipment'] = "equipment[name*=$equipment_name]";
            $search          = true;
        }

        if ($form['equipment_ref']) {
            $equipment_ref  = Q::quote(trim($form['equipment_ref']));
            if ($form['equipment_name']) {
                $pre_selectors['equipment'] .= "[ref_no*=$equipment_ref]";
            } else {
                $pre_selectors['equipment'] = "equipment[ref_no*=$equipment_ref]";
            }
            $search = true;
        }

        if ($form['count']) {
            $count = Q::quote($form['count']);
            $selector .= "[count=$count]";
        }

        if ($form['id']) {
            $id = Q::quote(trim($form['id']));
            $selector .= "[id=$id]";
        }

        // 按时间搜索
        // 送样时间
        if ($form['dtsubmit_dtstart']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtsubmit_dtstart']));
            $selector .= "[dtsubmit>=$dtstart]";
        }
        if ($form['dtsubmit_dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtsubmit_dtend']));
            $selector .= "[dtsubmit>0][dtsubmit<=$dtend]";
        }

        // 取样时间
        if ($form['dtpickup_dtstart']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtpickup_dtstart']));
            $selector .= "[dtpickup>=$dtstart|dtpickup=0]";
        }
        if ($form['dtpickup_dtend']) {
            $dtend = Q::quote(Date::get_day_end($form['dtpickup_dtend']));
            $selector .= "[dtpickup>0][dtpickup<=$dtend]";
        }

        /*
        如果未按送样时间搜索
        则建议搜索条件为一个月内
        (xiaopei.li@2011.08.15)
         */
        //         $today = getdate(time());
        //         $recommemded_dtstart_search = mktime(23, 59, 59, $today['mon'], $today['mday'], $today['year']);
        //         $recommemded_dtend_search = $recommemded_dtstart_search - 2592000;
        //        if (!$form['dtsubmit_dtstart'] && !$form['dtsubmit_dtend']) {
        //            $form['dtsubmit_dtend'] = $recommemded_dtstart_search;
        //            $form['dtsubmit_dtstart'] = $recommemded_dtend_search;
        //        }
        //        if (!$form['dtpickup_dtstart'] && !$form['dtpickup_dtend']) {
        //            $form['dtpickup_dtend'] = $recommemded_dtstart_search;
        //            $form['dtpickup_dtstart'] = $recommemded_dtend_search;
        //        }

        if ($form['sender']) {
            $name                    = Q::quote(trim($form['sender']));
            $pre_selectors['sender'] = 'user<sender[name*=' . $name . ']';
        }

        if ($form['operator']) {
            $name                      = Q::quote(trim($form['operator']));
            $pre_selectors['operator'] = 'user<operator[name*=' . $name . ']';
        }

        if ($form['sender']) {
            $name = Q::quote($form['sender']);
            $pre_selectors['sender'] = 'user<sender[name*=' . $name . ']';
        }

        if ($form['status']) {
            foreach ($form['status'] as $k => $foo) {
                $s[] = $foo;
            }
            $status = join(',', $s);
            $selector .= '[status=' . $status . ']';
        }

        //排序
        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'serial_number':
                $selector .= ":sort(id {$sort_flag})";
                break;
            case 'equipment_name':
                $selector .= ":sort(equipment_abbr {$sort_flag})";
                break;
            case 'sender':
                $selector .= ":sort(sender_abbr {$sort_flag})";
                break;
            case 'operator':
                $selector .= ":sort(operator_abbr {$sort_flag})";
                break;
            case 'ctime':
                $selector .= ":sort(ctime {$sort_flag})";
                break;
            case 'dtsubmit':
                $selector .= ":sort(dtsubmit {$sort_flag})";
                break;
            //case 'dtrial' :
            //$selector = ":sort(dtstart {$sort_flag})";
            //break;
            case 'dtpickup':
                $selector .= ":sort(dtpickup {$sort_flag})";
                break;
            case 'count':
                $selector .= ":sort(count {$sort_flag})";
                break;
            case 'status':
                $selector .= ":sort(status {$sort_flag})";
                break;
            default:
                $selector .= ':sort(id D)';
                break;
        }

        $new_selector = Event::trigger('eq_sample.search.filter.submit', $selector, $form, $pre_selectors);

        if (count($pre_selectors) > 0) {
            $pre = '('.implode(', ', (array) $pre_selectors).') ';
        }
        
        if ($new_selector) {
            $selector = $new_selector;
        }

        $selector = $pre . $selector;

        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $samples               = Q($selector);
        $tabs->form_token      = $form_token;
        $pagination            = Lab::pagination($samples, (int) $form['st'], 20);

        $field         = self::get_field($form, $tabs);
        $tabs->columns = new ArrayObject($field);

        $tabs->content = V('eq_sample:samples_for_lab', [
            'form_token' => $form_token,
            'lab'        => $lab,
            'samples'    => $samples,
            'pagination' => $pagination,
            'form'       => $form,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'time'       => strtotime("+1 day"),
            'columns'    => $tabs->columns,
        ]);
    }

    public static function lab_tab_tool($e, $tabs)
    {
        $form_token = $tabs->form_token;

        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text'  => I18N::T('equipments', '导出'),
            'tip'   => I18N::T('equipments', '导出Excel'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_sample/export') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text'  => I18N::T('equipments', '打印'),
            'tip'   => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_sample/export') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];
        $tabs->search_box = V('application:search_box', ['is_offset' => true, 'top_input_arr' => ['serial_number', 'equipment_ref'], 'columns' => (array) $tabs->columns, 'panel_buttons' => $panel_buttons]);

    }

    public static function get_field($form, $tabs)
    {

        if ($form['dtsubmit_dtstart'] || $form['dtsubmit_dtend']) {
            $form['dtsubmit_date'] = true;
        }

        if ($form['dtpickup_dtstart'] || $form['dtpickup_dtend']) {
            $form['dtpickup_date'] = true;
        }

        $lab = $tabs->lab;

        $fields = [
            'serial_number'  => [
                'title'    => I18N::T('eq_sample', '编号'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/serial_number', ['value' => $form['id']]),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],
                'weight'   => 10,
                'nowrap'   => true,
            ],
            'equipment_name' => [
                'title'    => I18N::T('eq_sample', '仪器'),
                'align'    => 'left',
                'sortable' => true,
                'invisible'=> true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/equipment_name', ['equipment_name' => $form['equipment_name']]),
                    'value' => $form['equipment_name'] ? H($form['equipment_name']) : null,
                ],
                'weight'   => 20,
                'nowrap'   => true,
            ],
            'equipment_ref' => [
                'title'    => I18N::T('eq_sample', '仪器编号'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/equipment_ref', ['value' => $form['equipment_ref']]),
                    'value' => $form['equipment_ref'] ? H($form['equipment_ref']) : null,
                ],
                'weight'   => 25,
                'nowrap'   => true,
            ],
            'count'          => [
                'title'    => I18N::T('eq_sample', '样品数'),
                'align'    => 'right',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/count', ['count' => $form['count']]),
                    'value' => $form['count'] ? H($form['count']) : null,
                ],
                'weight'   => 30,
                'nowrap'   => true,
            ],
            'dtsubmit'       => [
                'title'    => I18N::T('eq_sample', '送样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix' => 'dtsubmit_',
                        'dtstart'     => $form['dtsubmit_dtstart'],
                        'dtend'       => $form['dtsubmit_dtend'],
                    ]),
                    'value' => $form['dtsubmit_date'] ? H($form['dtsubmit_date']) : null,
                    'field' => 'dtsubmit_dtstart,dtsubmit_dtend',
                ],
                'weight'   => 50,
                'nowrap'   => true,
            ],
            /*
            'dtrial'=>array(
            'title'=>I18N::T('eq_sample', '测样时间'),
            'align'=>'left',
            'sortable'=>TRUE,
            'filter'=> array(
            'form' => V('eq_sample:samples_table/filters/date', array(
            'name_prefix'=>'dtrial_',
            'dtstart_check'=>$form['dtrial_dtstart_check'],
            'dtstart'=>$form['dtrial_dtstart'],
            'dtend_check'=>$form['dtrial_dtend_check'],
            'dtend'=>$form['dtrial_dtend']
            )),
            'value' => $form['dtrial_date'] ? H($form['dtrial_date']) : NULL,
            'field'=>'dtrial_dtstart_check,dtrial_dtstart,dtrial_dtend_check,dtrial_dtend'
            ),
            'nowrap'=>TRUE,
            ),
             */
            'dtpickup'       => [
                'title'    => I18N::T('eq_sample', '取样时间'),
                'align'    => 'left',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/date', [
                        'name_prefix' => 'dtpickup_',
                        'dtstart'     => $form['dtpickup_dtstart'],
                        'dtend'       => $form['dtpickup_dtend'],
                    ]),
                    'value' => $form['dtpickup_date'] ? H($form['dtpickup_date']) : null,
                    'field' => 'dtpickup_dtstart,dtpickup_dtend',
                ],
                'weight'   => 60,
                'nowrap'   => true,
            ],
            'status'         => [
                'title'    => I18N::T('eq_sample', '状态'),
                'align'    => 'center',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/status', [
                        'status'  => $form['status'],
                        '_type'   => 'lab', //标记为 lab
                        '_object' => $lab,
                    ]),
                    // 'value' => V('eq_sample:samples_table/filters/status.value', ['status' => $form['status'], '_type' => 'lab', '_object' => $lab]),
                    'value' => !!$form['status'],
                ],
                'weight'   => 70,
                'nowrap'   => true,
            ],
            'sender'         => [
                'title'    => I18N::T('eq_sample', '申请人'),
                'align'    => 'center',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/sender', ['sender' => $form['sender']]),
                    'value' => $form['sender'] ? H($form['sender']) : null,
                ],
                'weight'   => 15,
                'nowrap'   => true,
            ],
            'operator'       => [
                'title'    => I18N::T('eq_sample', '操作者'),
                'align'    => 'center',
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_sample:samples_table/filters/operator', ['operator' => $form['operator']]),
                    'value' => $form['operator'] ? H($form['operator']) : null,
                ],
                'weight'   => 80,
                'nowrap'   => true,
            ],
            'fee'            => [
                'title'  => I18N::T('eq_sample', '收费'),
                'align'  => 'right',
                'weight' => 90,
                'nowrap' => true,
            ],
            'description'    => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
                'noLast' => TRUE,
            ],
        ];
        $columns = new ArrayObject($fields);
        Event::trigger('eq_sample.table_list.columns', $form, $columns);
        return $columns->getArrayCopy();
    }

    public static function eq_sample_view_print($e, $sample)
    {
        $equipment = $sample->equipment;
        $dtsubmit = $sample->dtsubmit ? Date::format($sample->dtsubmit, 'Y/m/d H:i:s') : I18N::T('eq_sample', '暂无测样时间');
        $dtstart = $sample->dtstart ? Date::format($sample->dtstart, 'Y/m/d H:i:s').  ' - '. Date::format($sample->dtend, 'Y/m/d H:i:s') : I18N::T('eq_sample', '暂无测样时间');
        $dtpickup = $sample->dtpickup ? Date::format($sample->dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '暂无取样时间');
        $note = $sample->note ?: '无';

        $print = [
            I18N::T('eq_sample', '申请人信息') => [
                I18N::T('eq_sample', '申请人')     => H($sample->sender->name),
                I18N::T('eq_sample', '申请人实验室')  => H($sample->lab->name),
                I18N::T('eq_sample', '申请人组织机构') => H($sample->sender->group->name),
                I18N::T('eq_sample', '申请人电话') => H($sample->sender->phone),
            ],
            I18N::T('eq_sample', '仪器信息')  => [
                I18N::T('eq_sample', '仪器名称')    => H($equipment->name),
                I18N::T('eq_sample', '仪器编号')    => H($equipment->ref_no),
                I18N::T('eq_sample', '仪器CF_ID') => $equipment->id,
                I18N::T('eq_sample', '仪器组织机构')  => H($equipment->group->name),
            ],
            I18N::T('eq_sample', '时间设定')  => [
                I18N::T('eq_sample', '送样时间') => $dtsubmit,
                I18N::T('eq_sample', '测样时间') => $dtstart,
                I18N::T('eq_sample', '取样时间') => $dtpickup,
            ],
            I18N::T('eq_sample', '送样状态') => [
                I18N::T('eq_sample', '样品状态') => V('eq_sample:incharge/print/status', ['sample'=> $sample]),
                I18N::T('eq_sample', '机主备注信息') => H($note),
                I18N::T('eq_sample', '测样数') => H($sample->count)
            ]
        ];

        if ($sample->status == EQ_Sample_Model::STATUS_TESTED) {
            $print[I18N::T('eq_sample', '送样状态')][I18N::T('eq_sample', '测样成功数')] = H($sample->success_samples);
        }

        $e->return_value = $print;
        return true;
    }

    public static function eq_sample_table_column($e, $table)
    {
        if (Lab::get('eq_sample.response_time')) {
            $table->add_columns([
                'ctime'         => [
                    'title'    => I18N::T('eq_sample', '申请时间'),
                    'sortable' => true,
                    'nowrap'   => true,
                    'align'    => 'left',
                    'weight'   => 49,
                ],
                'response_time' => [
                    'title'  => I18N::T('eq_sample', '响应时间'),
                    'nowrap' => true,
                    'align'  => 'left',
                    'weight' => 51,
                ],
            ]);

            $samples = $table->samples;

            if ($samples->total_count()) {
                foreach ($samples as $sample) {
                    $key                                = 'sample_' . $sample->id;
                    $table->rows[$key]['ctime']         = V('eq_sample:samples_table/data/ctime', ['sample' => $sample]);
                    $response_time                      = (string) V('eq_sample:samples_table/data/response_time', ['sample' => $sample]);
                    $response_time                      = empty($response_time) || $response_time == '0' ? $response_time                      = '0小时' : $response_time;
                    $table->rows[$key]['response_time'] = $response_time;
                }
            }
        }
    }

    public static function eq_sample_list_row($e, $row, $sample)
    {
        if (Lab::get('eq_sample.response_time')) {
            $row['ctime']         = V('eq_sample:samples_table/data/ctime', ['sample' => $sample]);
            $response_time        = (string) V('eq_sample:samples_table/data/response_time', ['sample' => $sample]);
            $response_time        = empty($response_time) || $response_time == '0' ? $response_time        = '0小时' : $response_time;
            $row['response_time'] = $response_time;
        }
        $row['note'] = V('eq_sample:samples_table/data/note',['sample'=>$sample]);
        return true;
    }

    static function eq_sample_list_columns($e, $form, $columns)
    {
        $columns['note'] = [
            'title' => I18N::T('eq_samples','备注'),
            'align' => 'left',
            'nowrap' => true,
            'weight' => 100,
            'noLast' => TRUE,
        ];
    }

    public static function get_duration($time1, $time2)
    {
        $duration = max(0, ($time2 - $time1));
        $days     = floor($duration / 86400);
        $hours    = round(($duration % 86400) / 3600, 0);
        if ($hours == 24) {
            $days++;
            $hours = 0;
        }
        return ($days ? $days . '天 ' : '') . $hours . '小时';
    }

    public static function extra_export_columns($e, $valid_columns)
    {
        if (Lab::get('eq_sample.response_time')) {
            $valid_columns['ctime']         = '申请时间';
            $valid_columns['response_time'] = '响应时间';
        }

        //在单台仪器的送样、预约、使用记录的导出和打印字段选择中，
        //增加自定义表单字段（默认不勾选），
        //可在导出/ 打印预约记录、送样记录、使用记录时一并导出/ 打印；
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $_SESSION[$form_token]['equipment_id'],'type'=>'eq_sample']);
            if($setting->id){
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                if ($extra && !empty($extra)) $valid_columns[-4] = '自定义表单';
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        if ($name != 'count' && $name != 'description')
                            $valid_columns['extra_setting_'.$name] = $field['title'];
                    }
                }
            }
        }

        $e->return_value = $valid_columns;

        return true;
    }

    public static function pending_count($e, $user)
    {
        if (!$user->id) {
            return;
        }
        if ( Q("$user<incharge equipment")->total_count() ) {
            $sample_status_applied = EQ_Sample_Model::STATUS_APPLIED;
            $sample_status_approved = EQ_Sample_Model::STATUS_APPROVED;
            $sample = Q("$user<incharge equipment eq_sample[status=$sample_status_applied|status=$sample_status_approved]")->total_count();
            $e->return_value = $sample;
        } else {
            $sample_status_applied = EQ_Sample_Model::STATUS_APPLIED;
            $sample = Q("$user<sender eq_sample[status=$sample_status_applied]")->total_count();
            $e->return_value = $sample;
        }
    }

    public static function status($e)
    {
        $e->return_value = EQ_Sample_Model::$status;
        return false;
    }

    public static function colors($e)
    {
        $e->return_value = EQ_Sample_Model::$status_background_color;
        return false;
    }

    public static function charge_status($e)
    {
        $e->return_value = EQ_Sample_Model::$charge_status;
        return false;
    }

    /**
     * RQ184007—用户在填写送样表单时，系统需显示此台仪器下，申请中和已批准的送样总数量和送样人数
     * 这个钩子之前调用地方比较乱，因此请忽略参数名字,根据cond是否有equipment_id判断是增加或编辑
     */
    public static function show_queue_numbers($e, $cond, $equipment, $sample = null)
    {
        $me  = L('ME');
        $lab = Q("$me lab")->current();

        $equipmentId = $cond->equipment->id > 0 ? $cond->equipment->id : $equipment->id;
        $total       = [
            'queue_samples'  => 0, //申请样品数
            'queue_people'   => 0, //申请人数
            'access_samples' => 0, //已批准样品数
            'access_people'  => 0,
            'samples'        => 0, //申请+已批准
            'peoples'        => 0, //申请+已批准
        ];
        $senderIdArray = [];
        $samples       = Q("eq_sample[status=1,2][equipment_id={$equipmentId}]");
        foreach ($samples as $sample) {
            EQ_Sample_Model::STATUS_APPLIED == $sample->status ? $total['queue_samples'] += $sample->count : '';
            EQ_Sample_Model::STATUS_APPROVED == $sample->status ? $total['access_samples'] += $sample->count : '';
            $senderIdArray[$sample->status][$sample->sender_id] = $sample->sender_id;
        }
        if (!empty($senderIdArray) && isset($senderIdArray[EQ_Sample_Model::STATUS_APPLIED])) {
            $total['queue_people'] = count($senderIdArray[EQ_Sample_Model::STATUS_APPLIED]);
        }
        if (!empty($senderIdArray) && isset($senderIdArray[EQ_Sample_Model::STATUS_APPROVED])) {
            $total['access_people'] = count($senderIdArray[EQ_Sample_Model::STATUS_APPROVED]);
        }

        // RQ185311 -- 用户在申请送样的时候，如果课题组余额不足100元则在送样表单上出现提示信息
        $billing_account = O('billing_account', ['lab' => $lab]);
        $balance         = $billing_account->balance;

        $view = V('eq_sample:edit/queue_numbers', ['total' => $total, 'balance' => $balance]);
        $e->return_value .= $view;
        return true;
    }

    /**
     * 送样最早最晚时间
     * @param $sample
     * @param $sendTime
     */
    public static function check_limit_time($e, $equipment, $type, $form)
    {
        if ($type != 'eq_sample') {
            return;
        }
        $me     = L('ME');
        $sender = $form['sender'] ? O('user', $form['sender']) : $me; // 只要被添加的人是有权限的就行
        if ($me->is_allowed_to('修改送样设置', $equipment)) {
            $e->return_value = true;
            return;
        }

        $time_limit                = self::get_time_limit($sender, $equipment);
        $add_sample_earliest_limit = $time_limit['add_sample_earliest_limit'];
        $add_sample_latest_limit   = $time_limit['add_sample_latest_limit'];

        list($add_earliest_time, $add_earliest_format) = Date::format_interval($add_sample_earliest_limit, 'hid');
        $add_earliest_str                              = $add_earliest_time . I18N::T('eq_sample', Date::unit($add_earliest_format));

        list($add_latest_time, $add_latest_format) = Date::format_interval($add_sample_latest_limit, 'hid');
        $add_latest_str                            = $add_latest_time . I18N::T('eq_sample', Date::unit($add_latest_format));

        $earliest_access = $latest_access = true;
        if ($add_sample_earliest_limit != 0 && $form['dtsubmit'] - time() > $add_sample_earliest_limit) {
            $earliest_access = false;
        }
        if ($add_sample_latest_limit != 0 && $form['dtsubmit'] - time() < $add_sample_latest_limit) {
            $latest_access = false;
        }
        if ($add_earliest_str != 0 && !$earliest_access) {
            $message[earliest] = I18N::T('eq_sample', '此仪器创建送样的最早提前时间是 %start;', [
                '%start' => $add_earliest_str,
            ]);
        }
        if ($add_latest_str != 0 && !$latest_access) {
            $message['latest'] = I18N::T('eq_sample', '此仪器最晚提前送样时间是 %end;', [
                '%end' => $add_latest_str,
            ]);
        }
        if (count($message)) {
            $message['extra'] = I18N::T('eq_sample', '请选择有效时间段!');
            $ems              = join("\n", $message);
            $form->set_error('dtsubmit', $ems);
            $e->return_value = false;
            return;
        }
        $e->return_value = true;
        return;
    }

    public static function get_time_limit($user, $equipment)
    {
        if (!$user->id || !$equipment->id) {
            return;
        }
        //用户标签分两个 系统用户标签/仪器自己的用户标签
        $tag_table_name = ['tag', 'tag_equipment_user_tags'];
        foreach($tag_table_name as $table_name){
            if ($table_name == "tag"){
                $root = $equipment->tag_root;
                if(!$root->id) continue;
            }
            if ($table_name == "tag_equipment_user_tags"){
                $root = Tag_Model::root('equipment_user_tags');
                if(!$root->id) continue;
            }
            $current_tag = Q("$user {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
            $labs = Q("$user lab");
            if ($labs->total_count()) {
                $lab_ids = implode(',', $labs->to_assoc('id', 'id'));
                $weight  = $current_tag->weight;
                if ($weight != null) {
                    $tag = Q("lab[id={$lab_ids}] {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                    if ($tag->id && $tag->weight < $weight) {
                        $current_tag = $tag;
                    }
                } else {
                    $current_tag = Q("lab[id={$lab_ids}] {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                }
            }

            foreach ($labs as $lab) {
                $group = $lab->group;
                if (!$group->id) {
                    continue;
                }
                $groot = Tag_Model::root('group');
                foreach (Q("{$table_name}[root=$root] tag_group[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            $group = $user->group;
            if ($group->id) {
                $groot = $group->root;
                if (!$groot->id) {
                    $groot = Tag_Model::root('group');
                }
                foreach (Q("{$table_name}[root=$root] tag_group[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            //按照weight进行排序只获取最优先匹配的那个Tag
            if ($current_tag->id) {
                $current_tag  = $current_tag->name;
            }
            //如果当前用户有对应用个别预约设置
            $tagged = (array) P($equipment)->get('@TAG_SAMPLE', '@', '@TAG_SAMPLE');
            if ($tagged && count((array) $tagged[$current_tag])) {
                //tag中存储的，都是以specific_为前缀的
                $sample_time_limits         = $tagged[$current_tag];
                $add_sample_earliest_limit  = $sample_time_limits['specific_add_earliest_limit']; //最早提前送样时间
                $add_sample_latest_limit    = $sample_time_limits['specific_add_latest_limit']; //最晚提前送样的时间
                $modify_sample_latest_limit = $sample_time_limits['specific_modify_latest_limit']; //最晚提前修改时间
            }
            if (is_numeric($add_sample_earliest_limit)
                || is_numeric($add_sample_latest_limit)
                || is_numeric($modify_sample_latest_limit)
            ) {
                break;
            }
        }

        // 将没有找到的预约设置用仪器的预约设置
        if (!is_numeric($add_sample_earliest_limit)) {
            $add_sample_earliest_limit = $equipment->add_sample_earliest_limit;
        }
        if (!is_numeric($add_sample_latest_limit)) {
            $add_sample_latest_limit = $equipment->add_sample_latest_limit;
        }
        if (!is_numeric($modify_sample_latest_limit)) {
            $modify_sample_latest_limit = $equipment->modify_sample_latest_limit;
        }

        // 如果还有没有的预约设置则找全局的个别预约设置
        if (is_numeric($add_sample_earliest_limit) &&
            is_numeric($add_sample_latest_limit) && is_numeric($modify_sample_latest_limit)) {
            goto output;
        }
        $tagged = array_filter((array)Lab::get('@TAG'), function ($v, $k) {
            return !!array_filter((array)$v, function ($v, $k) {
                return is_numeric($v);
            }, ARRAY_FILTER_USE_BOTH);
        }, ARRAY_FILTER_USE_BOTH);
        $group = $user->group;
        while ($group->id != $group->root->id) {
            if (!array_key_exists($group->name, $tagged)) {
                $group = $group->parent;
                continue;
            }

            $sample_time_limits = $tagged[$group->name];
            if (!is_numeric($add_sample_earliest_limit)) {
                $add_sample_earliest_limit = $sample_time_limits['equipment.add_sample_earliest_limit'];
            }
            if (!is_numeric($add_sample_latest_limit)) {
                $add_sample_latest_limit = $sample_time_limits['equipment.add_sample_latest_limit'];
            }
            if (!is_numeric($modify_sample_latest_limit)) {
                $modify_sample_latest_limit = $sample_time_limits['equipment.modify_sample_latest_limit'];
            }
            break;
        }

        // 再没有就用全局的预约设置
        if (is_numeric($add_sample_earliest_limit) &&
            is_numeric($add_sample_latest_limit) && is_numeric($modify_sample_latest_limit)) {
            goto output;
        }
        if (!is_numeric($add_sample_earliest_limit)) {
            $add_sample_earliest_limit = Lab::get('equipment.add_sample_earliest_limit');
        }
        if (!is_numeric($add_sample_latest_limit)) {
            $add_sample_latest_limit = Lab::get('equipment.add_sample_latest_limit');
        }
        if (!is_numeric($modify_sample_latest_limit)) {
            $modify_sample_latest_limit = Lab::get('equipment.modify_sample_latest_limit');
        }
        output:
        return [
            'add_sample_earliest_limit'  => max(0, $add_sample_earliest_limit),
            'add_sample_latest_limit'    => max(0, $add_sample_latest_limit),
            'modify_sample_latest_limit' => max(0, $modify_sample_latest_limit),
        ];
    }

    public static function check_modify_time($sample, $equipment)
    {
        $me = L('ME');
        if ($me->is_allowed_to('修改送样设置', $equipment)) {
            return true;
        }

        $time_limit                 = self::get_time_limit($sample->sender, $equipment);
        $modify_sample_latest_limit = $time_limit['modify_sample_latest_limit'];
        if (($modify_sample_latest_limit != 0 && $sample->dtsubmit - time() < $modify_sample_latest_limit)) {
            return false;
        }
        return true;
    }

    public static function equipment_dashboard_sections($e, $equipment, $sections)
    {
        if ($equipment->accept_sample && $equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            $sections[] = V('eq_sample:view/section.equipment_sample_setting')
                ->set('equipment', $equipment);
        }
    }

    public static function get_calendar_left_content($e, $calendar, $tabs = null)
    {
        if ($tabs->equipment->id) {
            $panel_buttons = [];
            $equipment     = $tabs->equipment;
            $form          = $tabs->form;
            $form_token    = $form['form_token'];
            $me            = L('ME');
            if ($me->is_allowed_to('添加送样记录', $equipment)) {
                $panel_buttons[] = [
                    'tip'   => I18N::HT(
                        'eq_sample',
                        '添加送样记录'
                    //'添加送样记录'
                    ),
                    'extra' => 'q-object="add_sample_record" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                        '" q-static="' . H(['id' => $equipment->id]) .
                        '" class="eq_sample_button button_add"',
                    'text'  => I18N::HT('eq_sample','添加'),
                ];
            } else {
                $panel_buttons[] = [
                    'title' => I18N::HT('eq_sample', '申请送样'),
                    'text'  => I18N::HT('eq_sample','申请送样'),
                    'extra' => 'q-object="add_sample" q-event="click" q-src="' . H(URI::url('!eq_sample/index')) .
                        '" q-static="' . H(['id' => $equipment->id]) .
                        '" class="eq_sample_button button_add"',
                ];
            }
            if ($me->is_allowed_to('导出送样记录', $equipment)) {
                $panel_buttons[] = [
                    'tip'   => I18N::T(
                        'eq_sample',
                        '导出Excel'
                    ),
                    'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                        '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                        '" class="eq_sample_button button_save "',
                    'text' => I18N::HT('eq_sample','导出'),
                ];
                $panel_buttons[] = [
                    'tip'   => I18N::T(
                        'eq_sample',
                        '打印'
                    ),
                    'extra' => 'q-object="output" q-event="click" q-src="' . H(URI::url('!eq_sample/export')) .
                        '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                        '" class="eq_sample_button button_print"',
                    'text' => I18N::HT('eq_sample','打印'),
                ];
            }
            $new_panel_buttons = Event::trigger('eq_sample_lab_use.panel_buttons', $panel_buttons, $form_token);
            $panel_buttons     = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;
            $e->return_value = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
            return false;
        }
    }

    public static function sample_status_step($e, $sample) {
        $e->return_value = Event::trigger('sample.status');
        return false;
    }


    // 为PI审核新增方法
    public static function status_step($e, $sample)
    {
        if (!Module::is_installed('approval_flow') || !Approval_Flow::sample_flow_lab()) {
            $e->return_value = EQ_Sample_Model::$status;
            return false;
        }
        switch ($sample->status) {
            case EQ_Sample_Model::STATUS_APPLIED:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_APPLIED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_APPLIED],
                ];
                break;
            case EQ_Sample_Model::STATUS_APPROVED:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_APPROVED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_APPROVED],
                    EQ_Sample_Model::STATUS_TESTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_TESTED],
                    EQ_Sample_Model::STATUS_CANCELED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_CANCELED],
                ];
                break;
            case EQ_Sample_Model::STATUS_TESTED:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_TESTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_TESTED],
                ];
                break;
            case EQ_Sample_Model::STATUS_SEND:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_SEND => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_SEND],
                    EQ_Sample_Model::STATUS_APPROVED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_APPROVED],
                    EQ_Sample_Model::STATUS_REJECTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_REJECTED],
                ];
                break;
            case EQ_Sample_Model::STATUS_REJECTED:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_REJECTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_REJECTED],
                ];
                break;
            case EQ_Sample_Model::STATUS_CANCELED:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_CANCELED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_CANCELED],
                ];
                break;
            default:
                $e->return_value = [
                    EQ_Sample_Model::STATUS_APPLIED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_APPLIED],
                    EQ_Sample_Model::STATUS_APPROVED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_APPROVED],
                    EQ_Sample_Model::STATUS_TESTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_TESTED],
                    EQ_Sample_Model::STATUS_REJECTED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_REJECTED],
                    EQ_Sample_Model::STATUS_CANCELED => EQ_Sample_Model::$status[EQ_Sample_Model::STATUS_CANCELED],
                ];
        }
        return false;
    }

    public static function eq_sample_model_before_save($e, $sample, $new_data) {

        if (Module::is_installed('approval_flow')
            && $sample->lab->id && !$sample->lab->sample_approval
            && $sample->status == EQ_Sample_Model::STATUS_APPLIED
        ) {
            if (in_array('eq_sample', Config::get('approval.modules')
                && Config::get('flow.eq_sample')['approve_pi'])
            ) {
                $sample->status = EQ_Sample_Model::STATUS_SEND;
            }
        }
        return TRUE;
    }



    static function extra_display_none($e, $form_token){
        $form = Input::form();
        $display_columns = [];
        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $_SESSION[$form_token]['equipment_id'],'type'=>'eq_sample']);
            if($setting->id){
                $valid_columns[-4] = '自定义表单';
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        if ($name != 'count' && $name != 'description')
                            $display_columns[] = 'extra_setting_'.$name;
                    }
                }
            }
        }
        $e->return_value = $display_columns;
        return TRUE;
    }

    // 测样记录导出增加自定义字段
    static function eq_sample_export_csv($e, $sample, $data, $valid_columns) {
        $setting = O('extra',['object'=>$sample->equipment,'type'=>'eq_sample']);
        if($setting->id){
            $extra = json_decode($setting->params_json, TRUE);
            $extra_value = @json_decode(O('extra_value', ['object' => $sample])->values_json, TRUE) ?? [];
            foreach ($extra as $key => $fields) {
                foreach ($fields as $name => $field) {
                    if ($name != 'count' && $name != 'description')
                        if (array_key_exists('extra_setting_'.$name, $valid_columns)){
                            switch ($field['type']) {
                                case Extra_Model::TYPE_CHECKBOX:
                                    $value = [];
                                    foreach ($extra_value[$name] as $key => $item ) {
                                        if($item == 'on'){
                                            $value[] = $key;
                                        }
                                    }
                                    $data[] = implode(",", $value)?:'--';
                                    break;
                                case Extra_Model::TYPE_RANGE:
                                    $data[] = implode("~", $extra_value[$name])?:'--';
                                    break;
                                case Extra_Model::TYPE_DATETIME:
                                    $data[] = date('Y-m-d H:i:s',$extra_value[$name])?:'--';
                                    break;
                                case Extra_Model::TYPE_SELECT:
                                    $data[] = $extra_value[$name] != -1?$extra_value[$name]:'--';
                                    break;
                                default:
                                    $data[] = $extra_value[$name]?:'--';
                            }
                        }
                }
            }
        }
        $e->return_value = $data;
    }


    static function duty_teacher_extra_form_submit($e, $object, $form)
    {
        if ($object->name() != 'eq_sample') return;
        if ($object->equipment->require_dteacher && !$object->duty_teacher->id && $form['status'] == EQ_Sample_Model::STATUS_TESTED) {
            $object->duty_teacher = L('ME');
        }
    }

    static function duty_teacher_sample_table_list_columns ($e, $form, $columns)
    {
        $equipment = O('equipment', $form['equipment_id']);
        if ($equipment->require_dteacher) {
            $columns['duty_teacher'] = [
                'title' => '值班老师',
                'align' => 'center',
                'nowrap' => true,
                'weight' => 85,
            ];
        }
    }

    static function duty_teacher_sample_table_list_row ($e, $row, $sample)
    {
        if ($sample->equipment->require_dteacher) {
            $row['duty_teacher'] = V('eq_sample:samples_table/data/duty_teacher', ['sample' => $sample]);
        }

        $e->return_value = $row;
    }

    static function duty_teacher_extra_export_columns($e, $valid_columns, $form)
    {
        $equipment_id = $_SESSION[$form['form_token']]['equipment_id'];
        $equipment = O('equipment', $equipment_id);
        if (!$equipment_id || !$equipment->require_dteacher) {
            unset($valid_columns['duty_teacher']);
        }
        $e->return_value = $valid_columns;

        return TRUE;
    }

}
