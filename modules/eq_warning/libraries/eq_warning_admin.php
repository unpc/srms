<?php

class EQ_Warning_Admin {

	static function setup_reminder($e,$secondary_tabs){
		$secondary_tabs->add_tab('eq_warning', [
			'title' => T('预警消息提醒'),
			'url'   => URI::url('admin/reminder.eq_warning'),
		]);
		Event::bind('admin.reminder.content', 'EQ_Warning_Admin::_notif_content', 0, 'eq_warning');
	}

	static function _notif_content($e, $tabs) {
      
		// 未达到额定使用机时预警、超过最大使用机时预警、低于最小使用预警
		$configs = [
            'notification.eq_warning.less_use',
            'notification.eq_warning.more_use',
            'notification.eq_warning.too_less_use',
            // 'notification.eq_warning.offline',
        ];

        $vars = [];

        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', I18N::T('eq_warning', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('eq_warning', '消息内容不能为空！'));
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

                    Lab::set($form['type'], $tmp);
                }
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_warning', '内容修改成功'));

                    $me = L('ME');
                    Log::add(strtr('[eq_warning] %user_name[%user_id]修改了仪器[%title]的提醒消息', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%title'     => $form['title'],
                    ]), 'journal');
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_warning', '恢复系统默认设置成功'));
            }
        }

        $views         = Notification::preference_views($configs, $vars, 'eq_warning');
        $tabs->content = $views;
    }

	static function setup(){
		
		if(L('ME')->access('管理所有内容')){
			Event::bind('admin.equipment.tab', 'EQ_Warning_Admin::_secondary_tab');
		}
	}
	
	static function _secondary_tab($e, $tabs){
		Event::bind('admin.equipment.content', 'EQ_Warning_Admin::_secondary_content', 0, 'warning');

		$tabs
		->add_tab('warning', [
			'url'=>URI::url('admin/equipment.warning'),
			'title'=> I18N::T('eq_warning', '预警设置'),
		]);

	}

	static function _secondary_content($e, $tabs){

		$me = L('ME');

		$is_admin = $me->access('管理所有内容');
		$is_group_admin = $me->access('添加/修改下属机构的仪器');
		
		if (Input::form('submit')) {

			$form = Form::filter(Input::form());//这个验证

			foreach($form['machine_hour'] as $fin => $v){
				if($form['machine_hour'][$fin] < 0)  $form->set_error("machine_hour[{$fin}]", I18N::T('eq_warning', '仪器额定机时不能为负数!'));
				if($form['use_limit_max'][$fin] < 0)  $form->set_error("use_limit_max[{$fin}]", I18N::T('eq_warning', '使用时长最大值不能为负数!'));
				if($form['use_limit_min'][$fin] < 0)  $form->set_error("use_limit_min[{$fin}]", I18N::T('eq_warning', '使用时长最小值不能为负数!'));
				if($form['use_limit_min'][$fin] >= $form['use_limit_max'][$fin] && $form['use_limit_max'][$fin] > 0)  $form->set_error("use_limit_min[{$fin}]", I18N::T('eq_warning', '使用时长最大值须大于使用时长最小值!'));
				$not_set = true;
				if($form['control_tag'][$fin] || $form['control_equipment'][$fin]) $not_set = false;
				if($not_set) $form->set_error("use_limit_min{$fin}", I18N::T('eq_warning', '预警设置下的仪器分类和个别仪器不能同时为空!'));
			}

			if($form->no_error) {

				//获取我负责的仪器
				$except_status = EQ_Status_Model::NO_LONGER_IN_SERVICE;

				Q("eq_warning[user={$me}]")->delete_all();
				foreach($form['machine_hour'] as $fin => $v){
					$rule = O('eq_warning');
					$rule->user = $me;
					$rule->unit = $form['unit'][$fin];
					$rule->machine_hour = $form['machine_hour'][$fin] ?? 0;
					$rule->use_limit_max = $form['use_limit_max'][$fin] ?? 0;
					$rule->use_limit_min = $form['use_limit_min'][$fin] ?? 0;
					$rule->control_tag = $form['control_tag'][$fin] ?? '';
					$rule->control_equipment = $form['control_equipment'][$fin] ?? '';
					$rule->unit_value = 1;
					$rule->ctime = time();
					$rule->save();
					//进行关联
					if($form['control_tag'][$fin]){
						$tag_id = join(',',array_keys(json_decode($form['control_tag'][$fin],true)));
						$selector = "(tag_equipment[id={$tag_id}]) equipment[status!={$except_status}]";
						if($is_group_admin && !$is_admin) $selector = "(tag_equipment[id={$tag_id}],{$me->group}) equipment[status!={$except_status}]";
						$set_equipments = Q($selector);
						foreach($set_equipments as $set_equipment){
							if($set_equipment->is_removable) continue;
							$rule = O('eq_warning_rule',['equipment'=>$set_equipment,'unit'=>$form['unit'][$fin]]);
							$rule->user = $me;
							$rule->equipment = $set_equipment;
							$rule->unit = $form['unit'][$fin];
							$rule->ctime = time();
							$rule->unit_value = 1;
							$rule->machine_hour = $form['machine_hour'][$fin];
							$rule->use_limit_max = $form['use_limit_max'][$fin];
							$rule->use_limit_min = $form['use_limit_min'][$fin];
							$rule->save();
						}
					}
					//优先个别仪器
					if($form['control_equipment'][$fin]){
						$equipment_id = join(',',array_keys(json_decode($form['control_equipment'][$fin],true)));
						$selector = "equipment[id={$equipment_id}]";
						$set_equipments = Q($selector);
						foreach($set_equipments as $set_equipment){
							if($set_equipment->is_removable) continue;
							$rule = O('eq_warning_rule',['equipment'=>$set_equipment,'unit'=>$form['unit'][$fin]]);
							$rule->user = $me;
							$rule->equipment = $set_equipment;
							$rule->unit = $form['unit'][$fin];
							$rule->ctime = time();
							$rule->unit_value = 1;
							$rule->machine_hour = $form['machine_hour'][$fin];
							$rule->use_limit_max = $form['use_limit_max'][$fin];
							$rule->use_limit_min = $form['use_limit_min'][$fin];
							$rule->save();
						}
					}
					
				}

				Log::add(strtr('[eq_reserv] %user_name[%user_id]修改了系统设置中的预警设置',[
					'%user_name' => L('ME')->name,
					'%user_id' => L('ME')->id,
				]),'journal');
		
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_reserv', '修改成功！'));

			}

		}

		$settings = [];
		$warnings = Q("eq_warning[user={$me}]:sort(unit):sort(id)");

		foreach($warnings as $warning){
			$settings[] = [
				'unit' => $warning->unit,
				'machine_hour' => $warning->machine_hour,
				'use_limit_min' => $warning->use_limit_min,
				'use_limit_max' => $warning->use_limit_max,
				'control_tag' => $warning->control_tag,
				'control_equipment' => $warning->control_equipment,
			];
		}

		$tabs->content=V('eq_warning:admin/setting', ['form'=>$form,'settings'=>$settings]);
		
	}

}
	