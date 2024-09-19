<?php

class Index_Controller extends Base_Controller
{

    public function index()
    {

        if (!L('ME')->is_allowed_to('列表', 'meeting')) {
            URI::url('error/404');
        }
        $form = Lab::form();

        $sort_by   = $form['sort'] ? $form['sort'] : 'ctime';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = "";
        $location_root = Tag_Model::root('location');
        if ($form['location_id'] && $form['location_id'] != $location_root->id) {
            $location = o("tag_location", $form['location_id']);
            $pre_selector['location'] = "$location";
        }
        if (count($pre_selector) > 0) {
            $selector = '(' . implode(',', $pre_selector) . ') ';
        }

        $selector  .= 'meeting';

        if ($form['name'] != null) {
            $name = Q::quote($form['name']);
            $selector .= "[name*=$name|name_abbr*=$name]";
        }
        //按使用状态搜索
        if ($form['status']) {
            $status            = $form['status'];
            $now               = Date::time();
            $meeting_calendars = Q("cal_component[dtstart~dtend={$now}] calendar[parent_name=meeting]")->to_assoc('id', 'parent_id');
            $lab_calendars     = Q("cal_component[dtstart~dtend={$now}][me_room]")->to_assoc('id', 'me_room_id');
            $calendars         = array_merge($meeting_calendars, $lab_calendars);
            $calendar_ids      = count($calendars) ? join(', ', $calendars) : 0;

            if ($status == Meeting_Model::STATUS_USING) {
                $next_selector = "[id={$calendar_ids}]";
            } else if ($status == Meeting_Model::STATUS_AVAILABLE) {
                $next_selector = ":not(meeting[id={$calendar_ids}])";
            }
        }

        if (count($next_selector)) {
            $selector = $selector . $next_selector;
        }

        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name $sort_flag)";
                break;
            case 'contacts':
                $selector .= ":sort(contacts $sort_flag)";
                break;
            case 'location':
                $selector .= ":sort(location $sort_flag)";
                break;
            default:
                $selector .= ":sort(ctime D)";
                break;
        }

        $meetings = Q($selector);

        $start      = (int) $form['st'];
        $per_page   = 20;
        $start      = $start - ($start % $per_page);
        $pagination = Lab::pagination($meetings, $start, $per_page);
        $this->add_css('preview');
        $this->add_js('preview');

        $this->layout->body->primary_tabs
            ->select($tabs)
            ->content = V('index', [
                'form'       => $form,
                'pagination' => $pagination,
                'st'         => $start,
                'meetings'   => $meetings,
                'next_start' => $next_start,
                'model_name' => $tabs,
                'sort_asc'   => $sort_asc,
                'sort_by'    => $sort_by,
            ]);
    }

    public function add()
    {

        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'meeting')) {
            URI::redirect('error/401');
        }

        $meeting = O('meeting');
        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('meeting', '请输入会议室名称!'))
                ->validate('location', 'not_empty', I18N::T('meeting', '请输入会议室地点!'));
            $incharges = (array) @json_decode($form['incharges'], true);
            $contacts  = (array) @json_decode($form['contacts'], true);
            if (count($incharges) == 0) {
                $form->set_error('incharges', I18N::T('meeting', '请指定至少一名会议室负责人!'));
            }
            if (count($contacts) == 0) {
                $form->set_error('contacts', I18N::T('meeting', '请指定至少一名会议室联系人!'));
            }

            if ($form->no_error) {
                $meeting->name = $form['name'];    
                $meeting->seats = (int)$form['seats'];          
                $meeting->location = $form['location'];
                $meeting->description = $form['description'];

                $meeting->save();
                if ($meeting->id) {
                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $meeting->connect($user, 'incharge');
                        $user->follow($meeting);
                    }

                    //会议室多个联系人
                    foreach ($contacts as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $meeting->connect($user, 'contact');
                        $meeting->connect($user, 'incharge');
                        $user->follow($meeting);
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '会议室添加成功!'));
                    URI::redirect($meeting->url(null, null, null, 'edit'));

                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '会议室添加失败! 请与系统管理员联系。'));
                }

            }
        }

        $this->layout->body->primary_tabs
            ->add_tab('add', [
                'url'   => URI::url(''),
                'title' => I18N::T('meeting', '添加空间'),
            ])
            ->select('add')
            ->content = V('meeting:add', [
                'meeting' => $meeting,
                'form'    => $form,
            ]);
    }

    public function edit($id, $tab = 'info')
    {
        $me      = L('ME');
        $meeting = O('meeting', $id);
        if (!$me->is_allowed_to('修改', $meeting)) {
            URI::redirect('error/401');
        }

        if (!$meeting->id) {
            URI::redirect('error/404');
        }

        $content                 = V('edit');
        $content->meeting        = $meeting;
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        Event::bind('meeting.edit.content', [$this, '_edit_info'], 0, 'info');
        Event::bind('meeting.edit.content', [$this, '_edit_photo'], 0, 'photo');
        Event::bind('meeting.edit.content', [$this, '_edit_group'], 0, 'group');
        Event::bind('meeting.edit.content', [$this, '_edit_reserv'], 0, 'reserv');
        Event::bind('meeting.edit.content', [$this, '_edit_device'], 0, 'device');
        Event::bind('meeting.edit.content', [$this, '_edit_capture'], 0, 'capture');
        Event::bind('meeting.edit.content', [$this, '_edit_client'], 0, 'client');
        $this->layout->body->primary_tabs
            ->add_tab('info', [
                'url'    => $meeting->url('info', null, null, 'edit'),
                'title'  => I18N::T('meeting', '基本信息'),
                'weight' => 0,
            ])
            ->add_tab('reserv', [
                'url'    => $meeting->url('reserv', null, null, 'edit'),
                'title'  => I18N::T('meeting', '预约设置'),
                'weight' => 10,
            ])
            ->add_tab('group', [
                'url'    => $meeting->url('group', null, null, 'edit'),
                'title'  => I18N::T('meeting', '分组设置'),
                'weight' => 15,
            ])
            ->add_tab('device', [
                'url'    => $meeting->url('device', null, null, 'edit'),
                'title'  => I18N::T('meeting', '设施管理'),
                'weight' => 30,
            ])
            ->add_tab('capture', [
                'url'    => $meeting->url('capture', null, null, 'edit'),
                'title'  => I18N::T('meeting', '摄像头管理'),
                'weight' => 35,
            ])
            ->add_tab('client', [
                'url'    => $meeting->url('client', null, null, 'edit'),
                'title'  => I18N::T('meeting', '终端管理'),
                'weight' => 40,
            ]);

        $this->layout->body->primary_tabs
            ->set('meeting', $meeting)
            ->tab_event('meeting.edit.tab')
            ->content_event('meeting.edit.content')
            ->select($tab);

        $this->layout->title = H($meeting->name);
        $breadcrumbs = [
            [
                'url' => '!meeting',
                'title' => I18N::T('meeting', '空间列表'),
            ],
            [
                'url' => $meeting->url(),
                'title' => $meeting->name,
            ],
            [
                'title' => '修改',
            ],
        ];

        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

    }

    public function _edit_info($e, $tabs)
    {
        $meeting = $tabs->meeting;
        $me      = L('ME');

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        if (Input::form('submit')) {

            $location_root = Tag_Model::root('location');

            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('meeting', '请输入空间名称!'))
                ->validate('ref_no', 'not_empty', I18N::T('meeting', '请输入空间编号!'))
                ->validate('location', 'not_empty', I18N::T('meeting', '请选择空间地理位置!'));

            if ($form['location'] == $location_root->id) {
                $form->set_error('location', I18N::T('meeting', '地理位置不能为空!'));
            } 

            if ($form->no_error) {
                $meeting->name = H($form['name']);
                $meeting->en_name = H($form['en_name']);
                $meeting->ref_no = H($form['ref_no']);
                $meeting->ctime = Date::time();
                $meeting->type = (int)$form['type'];
                $meeting->util_area = (int)$form['util_area'];
                $meeting->phone = H($form['phone']);
                $location = O("tag_location", (int)$form['location']);
                $meeting->location = $location;     
                $meeting->description = $form['description'];
                $meeting->save();
                if ($meeting->id) {
                    foreach (Q("$meeting user.incharge") as $incharge) {
                        $meeting->disconnect($incharge, 'incharge');
                    }

                    $user = O('user', (int)$form['incharge']);
                    if ($user->id) {
                        $meeting->connect($user, 'incharge');
                        $user->follow($meeting);
                    }

                    if ($location->id) {
                        $location_root->disconnect($meeting);
                        $location->connect($meeting);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '空间已更新'));

                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '空间信息更新失败! 请与系统管理员联系。'));
                }

            }
        }

        $tabs->content = V('meeting/edit.info', ['form' => $form, 'meeting' => $meeting]);
    }

    public function _edit_photo($e, $tabs)
    {
        $meeting = $tabs->meeting;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext = File::extension($file['name']);
                    $meeting->save_icon(Image::load($file['tmp_name'], $ext));
                    $me = L('ME');
                    Log::add(strtr('[meeting] %user_name[%user_id]修改%meeting_name[%meeting_id]会议室的图标', [
                        '%user_name'    => $me->name,
                        '%user_id'      => $me->id,
                        '%meeting_name' => $meeting->name,
                        '%meeting_id'   => $meeting->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '会议室图标已更新'));
                } catch (Error_Exception $e) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '会议室图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '请选择您要上传的会议室图标文件。'));
            }
        }

        $tabs->content = V('meeting/edit.photo');
    }

    public function _edit_group($e, $tabs) {
        $meeting = $tabs->meeting;

        $form    = Form::filter(Input::form());

        if ($form['submit']) {
            $groups = (array)@json_decode($form['groups'], TRUE);
			if ($form->no_error) {
				$old_groups = Q("$meeting tag_room")->to_assoc('id', 'name');
				$disconGroups = array_diff_key($old_groups, $groups);
				$newConGroups = array_diff_key($groups, $old_groups);
				foreach ($disconGroups as $id => $name) {
					$tag = O('tag_room', $id);
					$tag->id && $meeting->disconnect($tag);
				}
				foreach ($newConGroups as $id => $name) {
					$tag = O('tag_room', $id);
					$tag->id && $meeting->connect($tag);
				}

                $me = L('ME');
                Log::add(strtr('[meetings] %user_name[%user_id]修改了空间%member_name[%member_id]的分组信息', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%member_name'=> $meeting->name, '%mbmer_id'=> $meeting->id]), 'journal');
				Lab::message(LAB::MESSAGE_NORMAL, I18N::T('meeting', '空间分组信息保存成功！'));
				URI::redirect($meeting->url('group', NULL, NULL, 'edit'));
			}
        }

        $tabs->content = V('meeting/edit.group', [
            'meeting' => $meeting,
            'form' => $form
        ]);
    }

    public function _edit_reserv($e, $tabs)
    {
        $meeting = $tabs->meeting;
        $properties = Properties::factory($meeting);
        $me      = L('ME');
        $form    = Form::filter(Input::form());

        $success = 0;
        $fail    = 0;
        $fails   = 0;
        if ($form['submit']) {

            if ($form['accept_block_time']) {
                if ($form['interval_time'] <= 0) {
                    $form->set_error('interval_time', I18N::T('meeting', '块状预约长度对齐时间必须大于0!'));
                }

                if ($form['align_time'] <= 0) {
                    $form->set_error('align_time', I18N::T('meeting', '块状预约起始对齐时间必须大于0!'));
                }

                if (count($form['block_time'])) {
                    foreach ($form['block_time'] as $key => $block) {
                        if ((date('H', $block['start']) == date('H', $block['end'])) && (date('i', $block['start']) == date('i', $block['end']))) {
                            $form->set_error("block_time[$key][start]", I18N::T('meeting', '块状预约个别时段时间段不能相同!'));
                        }

                        if (Date::convert_interval($block['align_time'], $block['align_format']) <= 0) {
                            $form->set_error("block_time[$key][interval_time]", I18N::T('meeting', '块状预约个别时段时间长度对齐间隔必须大于0!'));
                        }

                        if (Date::convert_interval($block['align_time'], $block['align_format']) <= 0) {
                            $form->set_error("block_time[$key][align_time]", I18N::T('meeting', '块状预约个别时段时间起始对齐间隔必须大于0!'));
                        }
                    }
                }
            }

            if ($form['add_resrev_earliest_format'] && $form['add_reserv_earliest_format'] == 'i') {
                $tmp_value = $form['add_resrev_earliest_format'];
                if ((float) $tmp_value > (int) $tmp_value) {
                    $form->set_error('add_reserv_earliest_format', I18N::T('eq_reserv', '添加预约最早提前时间精确到“分”时，请填写整数值'));
                }
            }

            if ($form['add_reserv_latest_format'] && $form['add_reserv_latest_format'] == 'i') {
                $tmp_value = $form['add_reserv_latest_format'];
                if ((float) $tmp_value > (int) $tmp_value) {
                    $form->set_error('add_reserv_latest_format', I18N::T('eq_reserv', '添加预约最晚提前时间精确到“分”时，请填写整数值'));
                }
            }

            if ($form['modify_reserv_latest_format'] && $form['modify_reserv_latest_format'] == 'i') {
                $tmp_value = $form['modify_reserv_latest_format'];
                if ((float) $tmp_value > (int) $tmp_value) {
                    $form->set_error('modify_reserv_latest_format', I18N::T('eq_reserv', '修改 / 删除预约最晚提前时间精确到“分”时，请填写整数值'));
                }
            }

            // 工作时间
            Q("eq_reserv_time[meeting={$meeting}]")->delete_all();

            foreach ($form['startdate'] as $key => $value) {
                $fail = 0;
                $time = O('eq_reserv_time', $form['id'][$key]);
                $time->meeting = $meeting;
                $rules = [];

                $time->controlall = $form['controlall'][$key];
                if ($time->controlall) {
                    $time->controluser = '';
                    $time->controllab = '';
                    $time->controlgroup = '';
                }
                else {
                    $time->controluser = ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}')
                        ? $form['user'][$key] : '';
                    
                    $time->controllab = ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}')
                        ? $form['lab'][$key] : '';
    
                    $time->controlgroup = ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}')
                        ? $form['group'][$key] : '';

                    if ($time->controluser == '' && $time->controllab == '' && $time->controlgroup == '') {
                        $form->set_error('controlall['.$key.']', I18N::T('eq_sample', '选择个别用户后请选择具体使用用户!'));
                        $fail ++;
                    }
                }

                if ($form['starttime'][$key] >= 31593600) $form['starttime'][$key] = $form['starttime'][$key] - 86400;
                elseif ($form['starttime'][$key] < 31507200) $form['starttime'][$key] = $form['starttime'][$key] + 86400;
                if ($form['endtime'][$key] >= 31593600) $form['endtime'][$key] = $form['endtime'][$key] - 86400;
                elseif ($form['endtime'][$key] < 31507200) $form['endtime'][$key] = $form['endtime'][$key] + 86400;

                if ($form['startdate'][$key] > $form['enddate'][$key]) {
                    $form->set_error('working_date['.$key.']', I18N::T('eq_sample', '起始日期不能大于结束日期!'));
                    $fail ++;
                }

                if ($form['starttime'][$key] >= $form['endtime'][$key]) {
                    $form->set_error('working_time['.$key.']', I18N::T('eq_sample', '起始时间不能大于等于结束时间!'));
                    $fail ++;
                }

                $time->ltstart = mktime(0, 0, 0, date('m', $form['startdate'][$key]), date('d', $form['startdate'][$key]), date('Y', $form['startdate'][$key]));
                $time->ltend = mktime(23, 59, 59, date('m', $form['enddate'][$key]), date('d', $form['enddate'][$key]), date('Y', $form['enddate'][$key]));
                $time->dtstart = mktime(date('H', $form['starttime'][$key]), date('i', $form['starttime'][$key]), date('s', $form['starttime'][$key]), 1, 1, 1971);
                $time->dtend = mktime(date('H', $form['endtime'][$key]), date('i', $form['endtime'][$key]), date('s', $form['endtime'][$key]), 1, 1, 1971);
                $time->type = $form['repeat'][$key] ? $form['rtype'][$key] : 1;
                $time->num = $form['repeat'][$key] ? $form['rnum'][$key] : 1;
                
                switch($time->type) {
                    case -2:    //用户选择工作日，默认为周一到周五
                        $rules = [1,2,3,4,5];
                        break;
                    case -3:    //用户选择周末，默认为周六周日
                        $rules = [0,6];
                        break;
                    case 2:
                        $rules = array_keys($form['week_day'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体星期!'));
                            $fail ++;
                        }
                        break;
                    case 3:
                        $rules = array_keys($form['month_day'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体日期!'));
                            $fail ++;
                        }
                        break;
                    case 4:
                        $rules = array_keys($form['year_month'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体月份!'));
                            $fail ++;
                        }
                        break;
                }
                $time->days = $rules;

                if (!$fail && $time->save()) {
                    $success++ ;
                    Log::add(strtr('[eq_sample] %user_name[%user_id] 修改%equipment_name[%equipment_id]预约时间的规则', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                    ]), 'journal');
                }
                else $fails ++;
                
                $ntime['id'] = $time->id;
                $ntime['equipment'] = $time->equipment->id;
                $ntime['startdate'] = $time->ltstart;
                $ntime['enddate'] = $time->ltend;
                $ntime['starttime'] = $time->dtstart;
                $ntime['endtime'] = $time->dtend;
                $ntime['rtype'] = $time->type;
                $ntime['rnum'] = $time->num;
                $ntime['days'] = $time->days;
                $ntime['controlall'] = $time->controlall;
                $ntime['controluser'] = $time->controluser;
                $ntime['controllab'] = $time->controllab;
                $ntime['controlgroup'] = $time->controlgroup;
                $times[] = $ntime;
            }

            if ($form->no_error) {
                $su = true;

                if ($form['default_add_earliest'] == 'customize') {
                    $meeting->add_reserv_earliest_limit = Date::convert_interval($form['add_reserv_earliest_time'], $form['add_reserv_earliest_format']);
                } else {
                    $meeting->add_reserv_earliest_limit = null;
                }

                if ($form['default_add_latest'] == 'customize') {
                    $meeting->add_reserv_latest_limit = Date::convert_interval($form['add_reserv_latest_time'], $form['add_reserv_latest_format']);
                } else {
                    $meeting->add_reserv_latest_limit = null;
                }

                if ($form['default_modify_latest'] == 'customize') {
                    $meeting->modify_reserv_latest_limit = Date::convert_interval($form['modify_reserv_latest_time'], $form['modify_reserv_latest_format']);
                } else {
                    $meeting->modify_reserv_latest_limit = null;
                }

                $default_add_reserv_earliest_limit  = Lab::get('equipment.add_reserv_earliest_limit');
                $default_add_reserv_latest_limit    = Lab::get('equipment.add_reserv_latest_limit');
                $default_modify_reserv_latest_limit = Lab::get('equipment.modify_reserv_latest_limit');


                $meeting->accept_block_time = ($form['accept_block_time'] == 'on');

                if (!$meeting->accept_block_time) {
                    unset($meeting->reserv_interval_time);
                    unset($meeting->reserv_align_time);
                    unset($meeting->reserv_block_data);
                } else {
                    $meeting->reserv_interval_time = Date::convert_interval($form['interval_time'], $form['interval_time_format']);
                    $meeting->reserv_align_time    = Date::convert_interval($form['align_time'], $form['align_time_format']);
                    $block_times                   = $form['block_time'];
                    $data                          = [];
                    if (count($block_times)) {
                        foreach ($block_times as $key => $block) {
                            $data[$key]['dtstart']       = ['h' => date('H', $block['start']), 'i' => date('i', $block['start'])];
                            $data[$key]['dtend']         = ['h' => date('H', $block['end']), 'i' => date('i', $block['end'])];
                            $data[$key]['interval_time'] = Date::convert_interval($block['interval_time'], $block['interval_format']);
                            $data[$key]['align_time']    = Date::convert_interval($block['align_time'], $block['align_format']);
                        }
                    }
                    $meeting->reserv_block_data = $data;
                }

                $meeting->save();
                $properties->save();
                if ($su) {
                    Log::add(strtr('[meeting] %user_name[%user_id]修改%equipment_name[%equipment_id]会议室的预约设置', [
                        '%user_name'      => $me->name,
                        '%user_id'        => $me->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id'   => $equipment->id]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '会议室预约设置已更新!'));
                }
            }

            
        } else {
            $times = [];
            $sample_times = Q("eq_reserv_time[meeting={$meeting}]");

            foreach ($sample_times as $key => $value) {
                $time = [];
                $time['id'] = $value->id;
                $time['equipment'] = $value->equipment->id;
                $time['meeting'] = $value->meeting->id;
                $time['startdate'] = $value->ltstart;
                $time['enddate'] = $value->ltend;
                $time['starttime'] = $value->dtstart;
                $time['endtime'] = $value->dtend;
                $time['rtype'] = $value->type;
                $time['rnum'] = $value->num;
                $time['days'] = explode(',', $value->days);
                $time['controlall'] = $value->controlall;
                $time['controluser'] = $value->controluser;
                $time['controllab'] = $value->controllab;
                $time['controlgroup'] = $value->controlgroup;
                $times[] = $time;
            }
        }

        if (!is_null($properties->get('add_reserv_earliest_limit', '@'))) {
            list($add_reserv_earliest_time, $add_reserv_earliest_format) = Date::format_interval($properties->get('add_reserv_earliest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('add_reserv_latest_limit', '@'))) {
            list($add_reserv_latest_time, $add_reserv_latest_format) = Date::format_interval($properties->get('add_reserv_latest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('modify_reserv_latest_limit', '@'))) {
            list($modify_reserv_latest_time, $modify_reserv_latest_format) = Date::format_interval($properties->get('modify_reserv_latest_limit', '@'), 'hid');
        }

        $tabs->content = V('meeting/edit.reserv', [
            'add_reserv_earliest_time'    => $add_reserv_earliest_time,
            'add_reserv_latest_time'      => $add_reserv_latest_time,
            'add_reserv_earliest_format'  => $add_reserv_earliest_format,
            'add_reserv_latest_format'    => $add_reserv_latest_format,
            'modify_reserv_latest_time'   => $modify_reserv_latest_time,
            'modify_reserv_latest_format' => $modify_reserv_latest_format,
            'times'                       => $times,
            'form'                        => $form,
        ]);
    }

    public function _edit_tag($e, $tabs)
    {
        $meeting = $tabs->meeting;
        $form    = Input::form();

        //获取仪器的tag_root
        $root = $meeting->get_root();
        $tags = Q("tag_meeting_user_tags[root={$root}]:sort(weight A)")->to_assoc('id', 'name');

        $tabs->content = V('meeting_tags/tags', [
            'form'    => $form,
            'meeting' => $meeting,
            'tags'    => $tags,
        ]);
    }

    static function _edit_device($e, $tabs) {
		$meeting = $tabs->meeting;
        $form = Form::filter(Input::form());

		if ($form['submit']) {
            $equipments = (array)$form['equipments'];
            Meeting_Room::batch_connect($equipments, $meeting, 'equipment', 'room');
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '设施关联成功!'));
        }

		$content = V('meeting/edit.device', ['meeting' => $meeting, 'form' => $form]);
		$tabs->content = $content;
	}

    static function _edit_capture($e, $tabs) {
        $meeting = $tabs->meeting;
        $form = Form::filter(Input::form());

		if ($form['submit']) {
            $vidcams = (array)$form['vidcams'];
            Meeting_Room::batch_connect($vidcams, $meeting, 'vidcam', 'room');
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '摄像头关联成功!'));
        }

		$content = V('meeting/edit.capture', ['meeting' => $meeting, 'form' => $form]);
		$tabs->content = $content;
    }

    static function _edit_client($e, $tabs) {
        $meeting = $tabs->meeting;
        $form = Form::filter(Input::form());

		if ($form['submit']) {
            $clients = (array)$form['client'];
            Meeting_Room::batch_connect($clients, $meeting, 'client', 'room');
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '终端设备关联成功!'));
        }

		$content = V('meeting/edit.client', ['meeting' => $meeting, 'form' => $form]);
		$tabs->content = $content;
    }


    public function delete_photo($id = 0)
    {
        $me      = L('ME');
        $meeting = O('meeting', $id);
        if (!$me->is_allowed_to('修改', $meeting)) {
            URI::redirect('error/401');
        }

        $meeting->delete_icon();
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '删除会议室图标成功!'));

        URI::redirect($meeting->url('photo', null, null, 'edit'));
    }

    public function delete($id = 0)
    {
        $meeting = O('meeting', $id);

        if (!$meeting->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('删除', $meeting)) {
            URI::redirect('error/401');
        }

        foreach (Q("$meeting user.incharge") as $incharge) {
            $meeting->disconnect($incharge, 'incharge');
        }

        $meeting_attachments_dir_path = NFS::get_path($meeting, '', 'attachments', true);
        if ($meeting->delete()) {

            Log::add(strtr('[meeting] %user_name[%user_id]删除%meeting_name[%meeting_id]会议室', [
                '%user_name'    => $me->name,
                '%user_id'      => $me->id,
                '%meeting_name' => $meeting->name,
                '%meeting_id'   => $meeting->id,
            ]), 'journal');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '会议室删除成功!'));
            File::rmdir($meeting_attachments_dir_path);
            URI::redirect('!meeting');
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '会议室删除失败!'));
        }
    }

}

class Index_AJAX_Controller extends AJAX_Controller
{

    public function index_preview_click()
    {

        $form    = Input::form();
        $meeting = O('meeting', $form['id']);

        if (!$meeting->id) {
            return;
        }

        Output::$AJAX['preview'] = (string) V('meeting:meeting/preview', ['meeting' => $meeting]);
    }

    public function index_add_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'meeting')) {
            URI::redirect('error/401');
        }

        $meeting = O('meeting');

        JS::dialog(V('add', ['form' => $form, 'meeting' => $meeting]), [
            'title' => I18N::T('meeting', '添加空间'),
        ]);
    }

    public function index_add_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'meeting')) {
            URI::redirect('error/401');
        }

        $meeting = O('meeting');

        if (Input::form('submit')) {

            $location_root = Tag_Model::root('location');
            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('meeting', '请输入空间名称!'))
                ->validate('ref_no', 'not_empty', I18N::T('meeting', '请输入空间编号!'))
                ->validate('location', 'not_empty', I18N::T('meeting', '请选择空间地理位置!'));

            if ($form['location'] == $location_root->id) {
                $form->set_error('location', I18N::T('meeting', '地理位置不能为空!'));
            } 

            if ($form->no_error) {
                $meeting->name = H($form['name']);
                $meeting->en_name = H($form['en_name']);
                $meeting->ref_no = H($form['ref_no']);
                $meeting->ctime = Date::time();
                $meeting->type = (int)$form['type'];
                $meeting->util_area = (int)$form['util_area'];
                $meeting->phone = H($form['phone']);
                $location = O("tag_location", (int)$form['location']);
                $meeting->location = $location;     
                $meeting->description = $form['description'];
                $meeting->save();
                if ($meeting->id) {
                    $user = O('user', (int)$form['incharge']);
                    if ($user->id) {
                        $meeting->connect($user, 'incharge');
                        $user->follow($meeting);
                    }

                    if ($location->id) {
                        $location_root->disconnect($meeting);
                        $location->connect($meeting);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '空间添加成功!'));
                    JS::redirect($meeting->url(null, null, null, 'view'));

                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '空间添加失败! 请与系统管理员联系。'));
                }

            }
        }

        JS::dialog(V('add', ['form' => $form, 'meeting' => $meeting]), [
            'title' => I18N::T('meeting', '添加会议室'),
        ]);

    }
}
