<?php

class Equipments_Admin
{

    public static function setup()
    {
        if (L('ME')->access('添加/修改所有机构的仪器')) {
            Event::bind('admin.index.tab', 'Equipments_Admin::_primary_tab');

            /*
        if (Config::get('equipment.power_enabled', TRUE)) {
        Event::bind('admin.services.enumerates', 'Equipments_Admin::power_auth_service');
        }
         */
        }
    }

    /*
    static function power_auth_service($e, $services) {

    $pid = @file_get_contents(Daemon::get_pid_file('power_auth'));

    $services['power_auth'] = array(
    'summary' => I18N::T('equipments', '电源控制验证服务器'),
    'running' => $pid > 0 ? TRUE: FALSE,
    );

    }
     */

    //系统设置primary tab
    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Equipments_Admin::_primary_content', 0, 'equipment');

        $tabs->add_tab('equipment', [
            'url'    => URI::url('admin/equipment'),
            'title'  => I18N::T('equipments', '仪器管理'),
            'weight' => 30,
        ]);
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_notification_content', 0, 'message');
        Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_tag_content', 0, 'tag');
        Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_technical_tag_content', 0, 'technical');
        Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_education_tag_content', 0, 'education');
        Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_user_tag_content', 0, 'user_tag');

        $secondary_tabs = Widget::factory('tabs');

        if (!Event::trigger('db_sync.need_to_hidden', 'equipment')) {
            $tabs->content->secondary_tabs = $secondary_tabs
                ->set('class', 'secondary_tabs')
                ->add_tab('tag', [
                    'url'   => URI::url('admin/equipment.tag'),
                    'title' => I18N::T('equipments', '仪器分类'),
                ])
                // ->add_tab('technical', [
                //     'url'   => URI::url('admin/equipment.technical'),
                //     'title' => I18N::T('equipments', '国家科技部分类'),
                // ])
                // ->add_tab('education', [
                //     'url'   => URI::url('admin/equipment.education'),
                //     'title' => I18N::T('equipments', '教育部分类'),
                // ])
                ->add_tab('user_tag', [
                    'url'   => URI::url('admin/equipment.user_tag'),
                    'title' => I18N::T('equipments', '用户标签'),
                ])
                ->tab_event('admin.equipment.tab')
                ->content_event('admin.equipment.content');
        }

        Event::trigger('admin.equipment.secondary_tabs', $secondary_tabs);

		$me_token = Auth::normalize(L('ME')->token);
		if (in_array($me_token, (array)Config::get('lab.admin'))) {
			Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_supercode_content', 0, 'supercode');
			$tabs->content->secondary_tabs
				->add_tab('supercode', [
					'url'=>URI::url('admin/equipment.supercode'),
					'title'=>I18N::T('equipments', '超级密码'),
					'weight' => 100
				]);
		}
		if (Config::get('equipment_use.overtime_limit')) {
			Event::bind('admin.equipment.content', 'Equipments_Admin::_secondary_useset_content', 0, 'useset');
			$tabs->content->secondary_tabs
				->add_tab('useset', [
					'url'=>URI::url('admin/equipment.useset'),
					'title'=>I18N::T('equipments', '使用设置'),
					'weight' => 100
				]);
		}

        if(L('ME')->access('管理所有内容') && Config::get('equipment.holiday_support')){
            $secondary_tabs->add_tab('holiday', [
                'url' => URI::url('admin/equipment.holiday'),
                'title'=> I18N::T('equipments', '假期设置'),
                'weight' => 120
            ]);

            Event::bind('admin.equipment.content', 'Equipments_Admin::admin_holiday_content', 0, 'holiday');
        }

		$params = Config::get('system.controller_params');
		
		$tabs->content->secondary_tabs->select($params[1]);
	}

	static function _secondary_supercode_content($e, $tabs) {
		if (Input::form('submit')) {

            $key = (string) Config::get('equipment.super_key');

            //旧版
            $rand_code = substr(Input::form('rand_code'), 0, 8);

            $super_code = Cipher::encrypt($rand_code, $key, FALSE, 'des');
            for($i=0;$i<strlen($super_code);$i++) {
                $super_code[$i] = chr(0x30 + ord($super_code[$i])%10);
            }
            $super_code = substr($super_code, 0, 8);

            //新版
			for($i = 1; $i <= strlen($rand_code); $i++) {
				$sc[$i] = chr(0x30 + ord($rand_code[$i - 1]) % 10);
			}
			
			foreach ($sc as $value) {
				if ($value == 9) {
					$value = 1;
				} else if ($value == 0) {
					$value = 2;
				}
				$super_code_arr_new[] = $sc[$value];
			}

            $super_code_new = implode($super_code_arr_new);
		}

		$tabs->content = V('equipments:admin/supercode', [
			'rand_code' => $rand_code,
			'super_code' => $super_code,
			'super_code_new' => $super_code_new,
		]);

	}

	static function _secondary_tag_content($e, $tabs){
		$root = Tag_Model::root('equipment');
		$tags = Q("tag_equipment[parent={$root}]:sort(weight A)");
		Controller::$CURRENT->add_js('tag_sortable');

		$uniqid="tag_".uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
		$tabs->content = V('application:admin/tags/tag_root', ['tags'=>$tags, 'root'=>$root,'uniqid'=>$uniqid, 'title'=>'仪器分类', 'button_title'=>'分类']);

	}

    static function _secondary_technical_tag_content($e, $tabs){
		$root = Tag_Model::root('equipment_technical');
		$tags = Q("tag_equipment_technical[parent={$root}]:sort(weight A)");
		Controller::$CURRENT->add_js('tag_sortable');

		$uniqid="tag_".uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
		$tabs->content = V('application:admin/tags/tag_root', ['tags'=>$tags, 'root'=>$root,'uniqid'=>$uniqid, 'title'=>'国家科技部分类', 'button_title'=>'分类']);

	}

    static function _secondary_education_tag_content($e, $tabs){
		$root = Tag_Model::root('equipment_education');
		$tags = Q("tag_equipment_education[parent={$root}]:sort(weight A)");
		Controller::$CURRENT->add_js('tag_sortable');

		$uniqid="tag_".uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
		$tabs->content = V('application:admin/tags/tag_root', ['tags'=>$tags, 'root'=>$root,'uniqid'=>$uniqid, 'title'=>'教育部分类', 'button_title'=>'分类']);

	}

    static function _secondary_user_tag_content($e, $tabs) {
        Controller::$CURRENT->add_js('equipments:equipment_tag_sortable');
        $tabs->content = V('equipments:admin/user_tags/tags', ['is_slave' => Event::trigger('db_sync.need_to_hidden', '_r_user_tag')]);
    }

    static function _secondary_tag_location_content($e, $tabs){
        $root = Tag_Model::root('location');
        $tags = Q("tag_location[parent={$root}]:sort(weight A)");
        Controller::$CURRENT->add_js('tag_sortable');

        $uniqid="tag_".uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
        $tabs->content = V('application:admin/tags/tag_root', ['tags'=>$tags, 'root'=>$root,'uniqid'=>$uniqid, 'title'=>'地理位置', 'button_title'=>'地理位置']);

    }

    public static function _secondary_notification_content($e, $tabs)
    {

        $configs = Config::get('notification.equipments_conf');

        /*
        NO.TASK#282(guoping.zhang2010.12.01)
        添加送样申请提醒
         */
        $others_configs = Event::trigger('admin.equipments.notification_configs', [$tabs]);
        $configs        = array_merge((array) $others_configs, (array) $configs);
        $vars           = [];

        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', I18N::T('equipments', '消息标题不能为空!'))
                    ->validate('body', 'not_empty', I18N::T('equipments', '消息内容不能为空!'));
                $vars['form'] = $form;
                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];
                    foreach (Lab::get('notification.handlers') as $k => $v) {
                        if (isset($form['send_by_' . $k])) {
                            $value = $form['send_by_' . $k];
                        } else {
                            $value = 0;
                        }
                        $tmp['send_by'][$k] = $value;
                    }
                    self::add_logs($form['type']);
                    Lab::set($form['type'], $tmp);
                }
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '内容修改成功'));
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '恢复系统默认设置成功'));
            }
        }
        $views         = Notification::preference_views($configs, $vars, 'equipments');
        $tabs->content = $views;
    }

	static function _secondary_useset_content($e, $tabs) {
		$time = Lab::get('equipment.useset');
		$form = Form::filter(Input::form());
		if ($form['submit']) {
			if ($form['time'] >= 1) {
				Lab::set('equipment.useset', sprintf('%d', $form['time']));
			} else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设置时间为大于0的整数'));
			}
		}

		$tabs->content = V('equipments:admin/useset', [
			'time' => $form['time'] ? sprintf('%d', $form['time']) : $time ,
		]);
		
	}

	static function add_logs($type=null) {

		if (!$type)  return;

		switch ($type) {
			case 'notification.nofeedback':
				$category = '填写反馈信息';
				break;
			case 'notification.report_problem':
				$category = '故障报告';
				break;
			case 'notification.training_apply':
			case 'notification.training_approved':
			case 'notification.training_rejected':
			case 'notification.training_deleted':
				$category = '培训状态';
				break;
            case 'notification.edit_record':
                $category = '修改使用记录';
            default:
                $category = '设置';
                break;
        }

        $category = Event::trigger('other_notification.add.logs', $type);

        $me = L('ME');

        switch ($category) {
            case '填写反馈信息':
                Log::add(strtr('[equipments] %user_name[%user_id]修改了仪器填写反馈信息的提醒消息', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
                break;
            case '故障报告':
                Log::add(strtr('[equipments] %user_name[%user_id]修改了仪器故障报告的提醒消息', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
                break;
            case '培训状态':
                Log::add(strtr('[equipments] %user_name[%user_id]修改了仪器培训状态的提醒消息', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
                break;
            case '修改使用记录':
                Log::add(strtr('[equipments] %user_name[%user_id]修改了仪器修改使用记录的提醒消息', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
                break;
            case '设置':
                Log::add(strtr('[equipments] %user_name[%user_id]修改了仪器设置的提醒消息', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
                break;
        }
	}

	static function overtime_box_show() {
		$me = L('ME');
		$ids = $_SESSION['alert_record_overtime_ids'] ? $_SESSION['alert_record_overtime_ids'] : [];
	
		$records = Q("eq_record[user={$me}][dtend=0]");
		foreach ($records as $record) {
			if (!$record->id) break;
			if (isset($ids[$record->id])) continue;
			$use_time = round( (Date::time() - $record->dtstart) / 3600 , 2);
			if (!Config::get('equipment_use.overtime_limit') || $use_time < (Lab::get('equipment.useset') ? : 0 ) ) return TRUE;
			$params = [
				'title' => T("超时警告：{$me->name}用户"),
				'body' => T("您好！您使用的{$record->equipment->name}仪器已连续使用{$use_time}小时，请尽快结束使用。")
			];
			$_SESSION['alert_record_overtime_ids'][$record->id] = TRUE;
			JS::run(JS::smart()->jQuery->propbox((string)V('equipments:admin/prop_box/warning', $params), 150, 300, 'right_bottom'));
			break;
		}
	}

	static function layout_after_call($e, $controller) {
		if (!Auth::logged_in()) {
			return;
		}
		$controller->add_js('equipments:bind_check_use_overtime', false);
	}

	static function equipments_glogon_ret($e, $ret, $user, $equipment) {
		$dtstart = Date::time();
        if (!Config::get('equipment_use.overtime_limit')) return;
            $locale = $equipment->device['lang'];
            $limit = Lab::get('equipment.useset') ? : 0;
            $url = URI::url('!equipments/glogon/using', ['user' => $user->id, 'equipment' => $equipment->id]);
            $ret['alert'] = [
                'delay' => Date::time() + $limit * 3600,
                'url' => $url,
                'text' => "超时警告：{$user->name}用户\n\n您好！您使用的##仪器已连续使用{$limit}小时，请尽快结束使用。",
                'position' => 'center'
            ];
    }

    static function admin_holiday_content($e, $tabs) {

        $me = L('ME');
        if($me->access('管理所有内容')){
            $form = Form::filter(Input::form());
            $me = L('ME');

            if($form['submit']){

                $now = time();
                $form->validate('dtstart',"not empty",I18N::T('equipments', '请设置起始时间'))
                    ->validate('dtstart',"compare(>{$now})",I18N::T('equipments', '起始时间须晚于当前时间'))
                    ->validate('dtend',"not empty",I18N::T('equipments', '请设置结束时间'))
                    ->validate('dtend',"compare(>{$form['dtstart']})",I18N::T('equipments', '结束时间须晚于起始时间'));

                $specific_tags = $form['specific_tags'];
                $seeting_tags = [];
                if ($specific_tags) {
                    foreach ($specific_tags as $i => $tags) {
                        $tags = @json_decode($tags, TRUE);
                        if ($tags) foreach ($tags as $tid => $tag) {
                            $form->validate("dtstart_special{$i}","not empty",I18N::T('equipments', '请设置起始时间'))
                                ->validate("dtstart_special{$i}","compare(>{$now})",I18N::T('equipments', '起始时间须晚于当前时间'))
                                ->validate("dtend_special{$i}","not empty",I18N::T('equipments', '请设置结束时间'))
                                ->validate("dtend_special{$i}","compare(>{$form['dtstart_special'.$i]})",I18N::T('equipments', '结束时间须晚于起始时间'));
                            $seeting_tags[$tag] = ['tid'=>$tid,'dtstart'=>$form['dtstart_special'.$i],'dtend'=>$form['dtend_special'.$i]];
                        }
                    }
                }
                if($form->no_error){
                    try{

                        $path = sys_get_temp_dir();
                        if(file_exists($path.'/set_holiday_for_equipment')){
                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '存在正在执行的假期设置任务,请稍后重试'));
                        }else {
                            touch($path.'/set_holiday_for_equipment');
                            $settings = ['tagged'=>$seeting_tags, 'has_setting'=>$form['has_setting'],'dtstart'=>$form['dtstart'],'dtend'=>$form['dtend']];
                            Lab::set('equipment.holiday',$settings);

                            putenv('Q_ROOT_PATH=' . ROOT_PATH);
                            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php Equipment set_holiday_for_equipment ';
                            $cmd .= "'" . $me->id . "' '" . json_encode($settings, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
                            exec($cmd, $output);
                            $msg = $form['has_setting'] == 'on' ? I18N::T('equipments', '已开启假期, 对应时间段的所有预约将被删除') : I18N::T('equipments', '已关闭假期设置');

                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', $msg));
                            Log::add(strtr('[admin] %user_name[%user_id]修改了假期设置,是否开启假期:%has_setting,假期区间:%holiday_setting', [
                                '%user_name' => $me->name,
                                '%user_id' => $me->id,
                                '%has_setting' => $form['has_setting'] == 'on' ? '是' : '否',
                                '%holiday_setting' => date('Y-m-d H:i:s', $form['dtstart']) . '~' . date('Y-m-d H:i:s', $form['dtend']),
                            ]), 'journal');
                        }
                    }catch (Exception $e){
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '假期设置失败,请联系管理员'));
                    }
                }
            }

            $view = V('equipments:equipment/admin/set_holiday', [
                'form' => $form,
                'setting_tags' => $seeting_tags,
            ]);
            $tabs->content = $view;
        }
    }
}

