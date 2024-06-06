<?php

class Door_Controller extends Base_Controller
{

    public function index($id = 0, $tab = 'dashboard')
    {
        $door = O('door', $id);
        if (!$door->id) {
            URI::redirect('error/404');
        }

        $content                 = V('door/view');
        $content->door           = $door;
        $this->layout->body->primary_tabs = Widget::factory('tabs')
            ->set('door', $door)
            ->tab_event('door.index.tab')
            ->content_event('door.index.tab.content');

        if (L('ME')->is_allowed_to('列表记录', $door)) {

            Event::bind('door.index.tab.content', [$this, '_index_records'], 0, 'records');
            $this->layout->body->primary_tabs->add_tab('records', [
                'url'   => $door->url('records'),
                'title' => I18N::T('entrance', '进出记录'),
            ])->select($tab);
        }

        $this->add_css('entrance:common');
        $breadcrumbs = [
            [
                'url' => '!entrance/index',
                'title' => I18N::T('entrance', '门禁列表'),
            ],
            [
                'title' => $door->name,
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('entrance:door/header_content', ['door' => $door]);
        $this->layout->title = I18N::T('equipments', '');
    }

    public function _index_records($e, $tabs)
    {
        $door                = $tabs->door;
        $type                = 'record';
        $records_data        = DC_Record::index_records_get($door, $type);
        $tabs->search_box    = $records_data['data']['search_box'];
        $tabs->content       = $records_data['view'];
    }

    public function edit($id = 0, $tab = 'info')
    {
        $door = O('door', $id);

        $me = L('ME');

        if (!$door->id) {
            URI::redirect('error/404');
        }

        if (!$me->is_allowed_to('修改', $door)) {
            URI::redirect('error/401');
        }

        Event::bind('door.edit.content', [$this, '_edit_info'], '0', 'info');
        Event::bind('door.edit.content', [$this, '_edit_photo'], '0', 'photo');
        Event::bind('door.edit.content', [$this, '_edit_rule'], '0', 'rule');


        $this->layout->body->primary_tabs = Widget::factory('tabs')
            ->add_tab('info', [
                'url'   => $door->url('info', null, null, 'edit'),
                'title' => I18N::T('entrance', '基本设置'),
            ])
            ->add_tab('rule', [
                'url'   => $door->url('rule', null, null, 'edit'),
                'title' => I18N::T('entrance', '门禁规则'),
            ])
            ->set('door', $door)
            ->tab_event('door.edit.tab')
            ->content_event('door.edit.content')
            ->select($tab);

        $this->add_css('entrance:calendar');

        $this->layout->title = H($door->name);
        $breadcrumbs = [
            [
                'url' => '!entrance/index',
                'title' => I18N::T('entrance', '门禁列表'),
            ],
            [
                'url' => $door->url(),
                'title' => $door->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
    }

    public function _edit_info($e, $tabs)
    {
        $door = $tabs->door;
        $me   = L('ME');

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('entrance', '名称不能为空'));
            $requires = Config::get('form.entrance')['requires'];
            if (in_array('location', $requires)) {
                $form
                    ->validate('location', 'not_empty', I18N::T('entrance', '地理位置不能为空'))
                    ->validate('location', $form['location'] != '{}', I18N::T('entrance', '地理位置不能为空'));
            }
            $is_single = true;
            if ($form['single_direction']) {
                $is_single = false;
                $form->validate('out_addr', 'not_empty', I18N::T('entrance', '出门地址不能为空'));
            }
            switch ($form['type']) {
                case Door_Model::type('genee'):
                    $form['remote_device'] = 0;
                    $form
                        ->validate('in_addr', 'not_empty', I18N::T('entrance', '进门地址不能为空'))
                        ->validate('lock_id', 'not_empty', I18N::T('entrance', '门锁ID不能为空'))
                        ->validate('detector_id', 'not_empty', I18N::T('entrance', '门磁ID不能为空'));
                    break;
                case Door_Model::type('mp'):
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '关联门禁不可为空'));
                    $device = O('door_device', ['uuid' => $form['remote_device']]);
                    if ($device->id && Q("{$device}<remote_device door[id!={$door->id}]")->total_count()) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    break;
                case Door_Model::type('mpv2'):
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '请从门禁列表中选择关联门禁'));
                    if (Q("door[id!=$door->id][remote_device_id=" . $form['remote_device'] . "]")->total_count()
                        || Q("door[id!=$door->id][remote_device2_id=" . $form['remote_device'] . "]")->total_count()
                    ) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    if ($form['remote_device2']) {
                        if (Q("door[id!=$door->id][remote_device_id=" . $form['remote_device2'] . "]")->total_count()
                            || Q("door[id!=$door->id][remote_device2_id=" . $form['remote_device2'] . "]")->total_count()
                            || $form['remote_device'] === $form['remote_device2']
                        ) {
                            $form->set_error('remote_device2', I18N::T('entrance', '关联门禁设备2不可重复'));
                        }
                    }
                    break;
                default:
                    $form->validate('remote_device', 'not_empty', I18N::T('entrance', '请从门禁列表中选择关联门禁'));
                    if (Q("door[id!=$door->id][remote_device_id=" . $form['remote_device'] . "]")->total_count()) {
                        $form->set_error('remote_device', I18N::T('entrance', '关联门禁设备不可重复'));
                    }
                    break;
            }

            if ($form->no_error) {
                $door->name      = $form['name'];
                $door->in_addr   = $form['in_addr'];
                if($form['type'] == Door_Model::type('genee')) $door->in_addr = $form['in_addr'];
				else $door->in_addr = '';
                foreach (Q("$door<incharge user") as $incharge) {
                    $door->disconnect($incharge, 'incharge');
                }

                foreach ((array) @json_decode($form['incharges'], true) as $id => $name) {
                    $user = O('user', $id);
                    if (!$user->id) {
                        continue;
                    }

                    $door->connect($user, 'incharge');
                }
                if (!$is_single) {
                    $door->out_addr = $form['out_addr'];
                } else {
                    $door->out_addr = '';
                }
                $tags = @json_decode($form['location'], true);
                if (count($tags)) {
                    Tag_Model::replace_tags($door, $tags, 'location');
                } else {
                    $location_root = Tag_Model::root('location');
                    $tags = Q("$door tag_location[root=$location_root]");
                    foreach ($tags as $t) {
                        $t->disconnect($door);
                    }
                }
                $door->is_single_direction = $is_single;
                $door->lock_id             = $form['lock_id'];
                $door->detector_id         = $form['detector_id'];
                $door->type                = $form['type'];
                switch ($form['type']) {
                    case Door_Model::type('mp'):
                        $device = O('door_device', ['uuid' => $form['remote_device']]);
                        if (!$device->id) {
                            if($door->remote_device->id) $door->remote_device->delete();
                            $device->uuid = $form['remote_device'];
                            $device->save();
                        }
                        $door->remote_device = $device;
                        break;
                    case Door_Model::type('mpv2'):
                        $door->remote_device = $form['remote_device']?o('door_device', $form['remote_device']):0;
                        $door->remote_device2 = $form['remote_device2']?o('door_device', $form['remote_device2']):0;
                        break;
                    default:
                        $door->remote_device = $form['remote_device']?o('door_device', $form['remote_device']):0;
                        break;
                }
                if ($door->save()) {
                    $type = explode(':', $door->device['uuid'])[0];
                    if ($type == 'cacs' || $type == 'icco') {
                        // 由于 icco-server 式的门禁 is_monitoring 可能管理得不及时,
                        // 而 Device_Agent 中是会对是否 connect 做判断的, 比较严谨.
                        // 所以在此去除 is_monitoring 的判断
                        $agent = new Device_Agent($door, false, 'out');
                        $agent->call('sync');
                    }
                    /* 记录日志 */
                    Log::add(strtr('[entrance] %user_name[%user_id] 修改 门禁%door_name[%door_id]的基本设置', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%door_name' => $door->name,
                        '%door_id'   => $door->id,
                    ]), 'journal');
                    if (!$me->is_allowed_to('修改', $door)) {
                        URI::redirect($door->url());
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁信息更新成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁信息更新失败!'));
                }
            }
        }

        $tabs->content = V('door/edit.info', ['door' => $door, 'form' => $form]);
    }

    public function _edit_photo($e, $tabs)
    {
        $door = $tabs->door;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext = File::extension($file['name']);
                    if ($door->save_icon(Image::load($file['tmp_name'], $ext))) {
                        /* 记录日志 */
                        Log::add(strtr('[entrance] %user_name[%user_id] 修改 门禁%door_name[%door_id]的图标', [
                            '%user_name' => L('ME')->name,
                            '%user_id'   => L('ME')->id,
                            '%door_name' => $door->name,
                            '%door_id'   => $door->id,
                        ]), 'journal');

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁图标已更新'));
                    }
                } catch (Error_Exception $e) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '请选择您要上传的门禁图标。'));
            }
        }

        $tabs->content = V('door/edit.photo');
    }

    public function _edit_rule($e, $tabs)
    {
        $door = $tabs->door;

        if (Input::form('submit')) {
            $form  = Form::filter(Input::form());
            $rules = [];
            //分析select_user_mode
            if (!is_array($form['select_user_mode_user'])) {
                $form['select_user_mode_user'] = [];
            }
            foreach ($form['select_user_mode_user'] as $key => $value) {
                $rules[$key]['select_user_mode_user'] = $value;
            }
            if (!is_array($form['select_user_mode_lab'])) {
                $form['select_user_mode_lab'] = [];
            }
            foreach ($form['select_user_mode_lab'] as $key => $value) {
                $rules[$key]['select_user_mode_lab'] = $value;
            }
            if (!is_array($form['select_user_mode_group'])) {
                $form['select_user_mode_group'] = [];
            }
            foreach ($form['select_user_mode_group'] as $key => $value) {
                $rules[$key]['select_user_mode_group'] = $value;
            }


            //分析group
            if (!is_array($form['group'])) {
                $form['group'] = [];
            }

            foreach ($form['group'] as $key => $value) {
                if ($rules[$key]['select_user_mode_group'] == 'on') {
                    $group_arr = @json_decode($value, true);
                    if ($value && count($group_arr)) {
                        $rules[$key]['groups'] = $group_arr;
                    }
                }
            }

            //分析lab
            if (!is_array($form['lab'])) {
                $form['lab'] = [];
            }

            foreach ($form['lab'] as $key => $value) {
                if ($rules[$key]['select_user_mode_lab'] == 'on') {
                    $lab_arr = @json_decode($value, true);
                    if ($value && count($lab_arr)) {
                        $rules[$key]['labs'] = $lab_arr;
                    }
                }
            }

            //分析user
            if (!is_array($form['user'])) {
                $form['user'] = [];
            }

            foreach ($form['user'] as $key => $value) {
                if ($rules[$key]['select_user_mode_user'] == 'on') {
                    $user_arr = @json_decode($value, true);
                    if ($value && count($user_arr)) {
                        $rules[$key]['users'] = $user_arr;
                    }
                }
            }

            foreach ($rules as $key => $rule) {
                foreach (['user', 'group', 'lab'] as $k) {
                    if ($rule["select_user_mode_$k"] == 'on') {
                        $filter = "{$k}[1]";
                        $rule_name = "{$k}s";
                        if (!array_key_exists($rule_name, $rule)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁规则的用户类型、名称填写不完整!'));
                            $form->set_error($filter, I18N::T('entrance', '门禁规则的用户类型、名称填写不完整!'));
                        }
                    }
                }
            }

            //分析direction
            //direction 为一个数组, 0 代表出门, 1代表进门
            if (!is_array($form['direction'])) {
                $form['direction'] = [];
            }

            foreach ($form['direction'] as $key => $value) {
                $rules[$key]['directions'] = array_keys($value);
            }

            //分析access
            if (!is_array($form['access'])) {
                $form['access'] = [];
            }

            foreach ($form['access'] as $key => $value) {
                $rules[$key]['access'] = $value;
            }

            ///////////////////////////以下对时间那块的分析////////////////////////////////////

            //分析rnum
            if (!is_array($form['rnum'])) {
                $form['rnum'] = [];
            }

            foreach ($form['rnum'] as $key => $value) {
                $rules[$key]['rnum'] = $value;
            }

            //对于起止时间的逻辑判断
            if (!is_array($form['dtend'])) {
                $form['dtend'] = [];
            }

            foreach ($form['dtend'] as $key => $value) {
                if ($form['dtstart'][$key] == $value) {
                    $form->set_error('dtend[1]', I18N::T('entrance', '结束时间不得等于开始时间'));
                } else if ($form['dtstart'][$key] > $value) {
                    $form['dtend'][$key]   = $form['dtstart'][$key];
                    $form['dtstart'][$key] = $value;
                }
            }

            //分析起止时间dtstart
            if (!is_array($form['dtstart'])) {
                $form['dtstart'] = [];
            }

            foreach ($form['dtstart'] as $key => $value) {
                $rules[$key]['dtstart'] = $value;
            }
            //分析起止时间dtend
            if (!is_array($form['dtend'])) {
                $form['dtend'] = [];
            }

            foreach ($form['dtend'] as $key => $value) {
                $rules[$key]['dtend'] = $value;
            }

            //分析rtype
            if (!is_array($form['rtype'])) {
                $form['rtype'] = [];
            }

            foreach ($form['rtype'] as $key => $value) {
                $rules[$key]['rtype'] = $value;
            }

            //分析有效时间,当rtype为none时,有效时间按照起止时间
            //分析dtfrom
            if (!is_array($form['dtfrom'])) {
                $form['dtfrom'] = [];
            }

            foreach ($form['dtfrom'] as $key => $value) {
                if ($rules[$key]['rtype']) {
                    //rtype为 daily、weekly、yearly
                    $rules[$key]['dtfrom'] = Date::get_day_start($value);
                } else {
                    $rules[$key]['dtfrom'] = Date::get_day_start($rules[$key]['dtstart']);
                }
            }
            //分析dtto
            if (!is_array($form['dtto'])) {
                $form['dtto'] = [];
            }

            foreach ($form['dtto'] as $key => $value) {
                if ($rules[$key]['rtype']) {
                    $rules[$key]['dtto'] = Date::get_day_end($value);
                } else {
                    $rules[$key]['dtto'] = Date::get_day_end($rules[$key]['dtend']);
                }
            }

            //分析yearly_type
            if (!is_array($form['yearly_type'])) {
                $form['yearly_type'] = [];
            }

            foreach ($form['yearly_type'] as $key => $value) {
                $rules[$key]['yearly_type'] = $value;
            }

            //分析monthly_type
            if (!is_array($form['monthly_type'])) {
                $form['monthly_type'] = [];
            }

            foreach ($form['monthly_type'] as $key => $value) {
                $rules[$key]['monthly_type'] = $value;
            }

            //分析rrule,并生成rrule

            //分析month   \   month_day   \   month_week   \   week_day
            if (!is_array($form['month'])) {
                $form['month'] = [];
            }

            foreach ($form['month'] as $key => $value) {

                switch ($rules[$key]['rtype']) {
                    case TM_RRule::RRULE_YEARLY:
                        switch ($rules[$key]['yearly_type']) {
                            case TM_RRule::TYPE_DAY:
                                $rules[$key]['rrule'][] = array_keys($value);
                                $rules[$key]['rrule'][] = [-1];
                                break;
                            case TM_RRule::TYPE_WEEK:
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            //month_day
            if (!is_array($form['month_day'])) {
                $form['month_day'] = [];
            }

            foreach ($form['month_day'] as $key => $value) {

                switch ($rules[$key]['rtype']) {
                    case TM_RRule::RRULE_YEARLY:
                        switch ($rules[$key]['yearly_type']) {
                            case TM_RRule::TYPE_DAY:
                                if (!is_array($rules[$key]['rrule'])) {
                                    $rules[$key]['rrule'][] = [];
                                    $rules[$key]['rrule'][] = -1;
                                }
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;

                    case TM_RRule::RRULE_MONTHLY:
                        switch ($rules[$key]['monthly_type']) {
                            case TM_RRule::TYPE_DAY:
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            //month_week
            if (!is_array($form['month_week'])) {
                $form['month_week'] = [];
            }

            foreach ($form['month_week'] as $key => $value) {
                switch ($rules[$key]['rtype']) {
                    case TM_RRule::RRULE_YEARLY:
                        switch ($rules[$key]['yearly_type']) {
                            case TM_RRule::TYPE_WEEK:
                                if (!is_array($rules[$key]['rrule'])) {
                                    $rules[$key]['rrule'][] = [];
                                }
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;

                    case TM_RRule::RRULE_MONTHLY:
                        switch ($rules[$key]['monthly_type']) {
                            case TM_RRule::TYPE_WEEK:
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            //week_day
            if (!is_array($form['week_day'])) {
                $form['week_day'] = [];
            }

            foreach ($form['week_day'] as $key => $value) {
                switch ($rules[$key]['rtype']) {
                    case TM_RRule::RRULE_YEARLY:
                        switch ($rules[$key]['yearly_type']) {
                            case TM_RRule::TYPE_WEEK:
                                if (!is_array($rules[$key]['rrule'])) {
                                    $rules[$key]['rrule'][] = [];
                                    $rules[$key]['rrule'][] = [];
                                } elseif (count($rules[$key]['rrule']) == 1) {
                                    $rules[$key]['rrule'][] = [];
                                }
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;

                    case TM_RRule::RRULE_MONTHLY:
                        switch ($rules[$key]['monthly_type']) {
                            case TM_RRule::TYPE_WEEK:
                                if (!is_array($rules[$key]['rrule'])) {
                                    $rules[$key]['rrule'][] = [];
                                }
                                $rules[$key]['rrule'][] = array_keys($value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case TM_RRule::RRULE_WEEKLY:
                        $rules[$key]['rrule'][] = array_keys($value);
                        break;
                    default:
                        break;
                }
            }
            //去掉@INDEX, 所以unset掉index为 @INDEX 的项目
            unset($rules['@INDEX']);
            $door->rules = json_encode($rules, true);

            if ($form->no_error) {
                if ($door->save()) {

                    // 仪器绑定门禁
                    $old_subjects = Q("{$door}<asso equipment")->to_assoc('id', 'id');
                    foreach (json_decode($form['equipment']['default']) as $eqId => $name) {
                        $equipment = o('equipment', $eqId);
                        $equipment->connect($door, 'asso');
                        if (in_array($eqId, $old_subjects)) {
                            unset($old_subjects[$eqId]);
                            continue;
                        }
                    }

                    if (count($old_subjects)) foreach ($old_subjects as $s_id) {
                        $equipment = O("equipment", $s_id);
                        $equipment->disconnect($door, 'asso');
                    }

                    /* 记录日志 */
                    Log::add(strtr('[entrance] %user_name[%user_id] 修改 门禁%door_name[%door_id]的规则', [
                        '%user_name' => L('ME')->name,
                        '%user_id'   => L('ME')->id,
                        '%door_name' => $door->name,
                        '%door_id'   => $door->id,
                    ]), 'journal');

                    if ($door->type == Door_Model::type('hikvision') && !$door->isSettingHkisc) {
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁规则更新成功,建议请在ISC完成门禁规则设置后再使用该门禁!'));
                    } else {
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁规则更新成功!'));
                    }
                }
            }
        }

        $rules = json_decode($door->rules);
        $this->add_css('entrance:common');
        //由于edit.rules中用了widget的方式加载视图，这里只好用session传值到widget中
        $_SESSION['form'] = $form;
        $tabs->content    = V('door/edit.rules', ['rules' => $rules, 'form' => $form]);
    }

    public function delete($id = 0)
    {
        /*
        NO.TASK#274(guoping.zhang@2010.11.27)
        应用权限设置新规则
         */
        $door = O('door', $id);
        $me   = L('ME');

        if (!$me->is_allowed_to('删除', $door)) {
            URI::redirect('error/401');
        }

        if ($door->id) {
            if (Q("$door dc_record")->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁"%door"在系统中存在进出记录,请删除该门禁相关的进出记录后重试.', ['%door' => H($door->name)]));
            } else {
                if ($door->delete()) {
                    /* 记录日志 */
                    Log::add(strtr('[entrance] %user_name[%user_id] 删除 门禁:%door_name[%door_id]', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%door_name' => $door->name,
                        '%door_id'   => $door->id,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('entrance', '门禁删除成功!'));
                }
            }
            URI::redirect('!entrance');
        }
        /*
        BUG#98
        2010.11.04 by cheng.liu
        判断是否存在id，如若不存在，则跳转到错误页面
        避免出现假删除现象
         */ else {
            URI::redirect('error/404');
        }
    }

    public function delete_photo($id = 0)
    {
        /*
        NO.TASK#274(guoping.zhang@2010.11.27)
        应用权限设置新规则
         */
        $door = O('door', $id);

        if (!$door->id) {
            URI::redirect('error/404');
        }

        if (!L('ME')->is_allowed_to('修改', $door)) {
            URI::redirect('error/401');
        }
        /*
        BUG#98
        2010.11.04 by cheng.liu
        判断是否存在id，如若不存在，则跳转到错误页面
        避免出现假删除现象
         */

        $door->delete_icon();

        /* 记录日志 */
        Log::add(strtr('[entrance] %user_name[%user_id] 删除 门禁%door_name[%door_id]的图标', [
            '%user_name' => L('ME')->name,
            '%user_id'   => L('ME')->id,
            '%door_name' => $door->name,
            '%door_id'   => $door->id,
        ]), 'journal');

        URI::redirect($door->url('photo', null, null, 'edit'));
    }
}

class Door_AJAX_Controller extends AJAX_Controller
{

    public function index_door_in_click()
    {
        $me   = L('ME');
        $form = Input::form();
        $door = O('door', $form['id']);
        if (!$door->id || !JS::confirm(I18N::T('entrance', '您确定要开门吗?'))) {
            return;
        }
        // 根据门的类型，调用不同的远程开门接口
        if ($door->open_by_remote()) {
            return;
        }

        $type = explode(':', $door->device['uuid'])[0];

        try {
            if ($type == 'cacs' || $type == 'icco') {
                $agent = new Device_Agent($door, false, 'in');
                if (!$agent->call('open')) {
                    throw new Exception;
                }

                if (Event::trigger('door.in', $door)) {
                    JS::alert(I18N::T('entrance', '进门成功!'));
                    JS::refresh();
                }
            } else {
                $client = new \GuzzleHttp\Client([
                    'base_uri'    => $door->server,
                    'http_errors' => false,
                    'timeout'     => Config::get('device.gdoor.timeout', 5),
                ]);

                $success = (bool) $client->post('open', [
                    'form_params' => [
                        'uuid' => $door->device['uuid'],
                        'user' => [
                            'username' => $me->token,
                            'name'     => $me->name,
                        ],
                    ],
                ])->getBody()->getContents();

                if ($success && Event::trigger('door.in', $door)) {
                    JS::alert(I18N::T('entrance', '进门成功!'));
                    JS::refresh();
                }
            }
        } catch (Exception $e) {
            JS::alert(I18N::T('entrance', '进门失败!'));
        }
    }

    public function index_door_out_click()
    {
        $me   = L('ME');
        $form = Input::form();
        $door = O('door', $form['id']);
        if ($door->is_single_direction) {
            return;
        }

        if (!$door->id || !JS::confirm(I18N::T('entrance', '您确定要出门吗?'))) {
            return;
        }

        $type = explode(':', $door->device['uuid'])[0];

        try {
            if ($type == 'cacs' || $type == 'icco') {
                $agent = new Device_Agent($door, false, 'out');
                if (!$agent->call('open')) {
                    throw new Exception;
                }

                if (Event::trigger('door.out', $door)) {
                    JS::alert(I18N::T('entrance', '进门成功!'));
                    JS::refresh();
                }
            } else {
                $client = new \GuzzleHttp\Client([
                    'base_uri'    => $door->server,
                    'http_errors' => false,
                    'timeout'     => Config::get('device.gdoor.timeout', 5),
                ]);

                $success = (bool) $client->post('open', [
                    'form_params' => [
                        'uuid' => $door->device['uuid'],
                        'user' => [
                            'username' => $me->token,
                            'name'     => $me->name,
                        ],
                    ],
                ])->getBody()->getContents();

                if ($success && Event::trigger('door.out', $door)) {
                    JS::alert(I18N::T('entrance', '出门成功!'));
                    JS::refresh();
                }
            }
        } catch (Exception $e) {
            JS::alert(I18N::T('entrance', '出门失败!'));
        }
    }

    public function index_tohkisc_view()
    {
        $form = Input::form();
        JS::dialog(V('door/rule/tohkisc', ['id' => $form['id']]), ['title' => '', 'no_close' => FALSE]);
    }

    public function index_tohkisc_submit()
    {
        $form = Input::form();
        $door = O('door', $form['id']);
        $door->isSettingHkisc = $form['status'];
        $door->save();
    }
}
