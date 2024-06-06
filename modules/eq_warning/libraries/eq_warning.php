<?php

class EQ_Warning {

	const UNIT_MONTH = 'month';
	const UNIT_QUARTER = 'quarter';
	const UNIT_YEAR = 'year';

	public static $unit_lebals = [
		self::UNIT_MONTH => '月',
		self::UNIT_QUARTER => '季度',
		self::UNIT_YEAR => '年',
	];

	public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'EQ_Warning::eq_warning_set_tab');
    }
	
	public static function eq_warning_set_tab($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改预警设置', $equipment)) {
            $tabs
                ->add_tab('warning', [
                    'url' => $equipment->url('warning', null, null, 'edit'),
                    'title' => I18N::T('material', '预警设置'),
                    'weight' => 55
                ]);
            Event::bind('equipment.edit.content', 'Eq_Warning::eq_warning_set_content', 0, 'warning');
        }
    }

	public static function eq_warning_set_content($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if (!$me->is_allowed_to('修改预警设置', $equipment)) URI::redirect('error/401');

        $rules = Q("eq_warning_rule[equipment=$equipment]:sort(unit)");
        $rule_settings = [];

        foreach($rules as $rule){
            $setting = [];
            $setting['equipment'] = $rule->equipment->id;
            $setting['user'] = $rule->user->id;
            $setting['unit'] = $rule->unit;
            $setting['machine_hour'] = $rule->machine_hour;
            $setting['use_limit_max'] = $rule->use_limit_max;
            $setting['use_limit_min'] = $rule->use_limit_min;
            $rule_settings[] = $setting;
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {

            $form = Form::filter(Input::form());//这个验证
			foreach($form['machine_hour'] as $fin => $v){
				if($form['machine_hour'][$fin] < 0)  $form->set_error("machine_hour[{$fin}]", I18N::T('eq_warning', '仪器额定机时不能为负数!'));
				if($form['use_limit_max'][$fin] < 0)  $form->set_error("use_limit_max[{$fin}]", I18N::T('eq_warning', '使用时长最大值不能为负数!'));
				if($form['use_limit_min'][$fin] < 0)  $form->set_error("use_limit_min[{$fin}]", I18N::T('eq_warning', '使用时长最小值不能为负数!'));
                if($form['use_limit_min'][$fin] >= $form['use_limit_max'][$fin] && $form['use_limit_max'][$fin] > 0)  $form->set_error("use_limit_min[{$fin}]", I18N::T('eq_warning', '使用时长最大值须大于使用时长最小值!'));
			}
            
            if($form->no_error) {

                if (L('ME')->is_allowed_to('锁定预警设置', $equipment)) {
                    $equipment->warning_lock = $form['warning_lock'];
                    $equipment->save();
                }

                Q("eq_warning_rule[equipment=$equipment]")->delete_all();
                foreach($form['machine_hour'] as $fin => $v){
					$rule = O('eq_warning_rule',['equipment'=>$equipment,'unit'=>$form['unit'][$fin]]);
                    $rule->user = $me;
                    $rule->equipment = $equipment;
                    $rule->unit = $form['unit'][$fin];
                    $rule->ctime = time();
                    $rule->unit_value = 1;
                    $rule->machine_hour = $form['machine_hour'][$fin];
                    $rule->use_limit_max = $form['use_limit_max'][$fin];
                    $rule->use_limit_min = $form['use_limit_min'][$fin];
                    $rule->save();
				}
                Log::add(strtr('[eq_reserv] %user_name[%user_id]修改了仪器[%equipment_id] %equipment_name 的预警设置',[
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%equipment_id' => $equipment->id,
                    '%equipment_name' => $equipment->name,
                ]),'journal');
        
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_warning', '修改成功！'));
                URI::redirect();
                return false;

            }
           
        }

        $tabs->content = V('eq_warning:equipment/edit', [
            'form' => $form,
            'equipment' => $equipment,
            'rules' => $rules,
            'rule_settings' => $rule_settings,
        ]);

    }

	public static function equipment_ACL($e, $me, $perm_name, $object, $options)
    {
        $me = L('ME');
        // 查看的权限因为移到报废的判断之上，否则会出问题的。
        switch ($perm_name) {
            case '修改预警设置':
            case '查看预警统计':
                if (!$object->is_removable && Equipment_ACL::use_is_allowed($me, $object, $perm_name, $options)) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '锁定预警设置':
                if ($me->access('添加/修改所有机构的仪器') || $me->access('添加/修改下属机构的仪器')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            
        }
    }

    public static function setup_stat_view()
    {
        Event::bind('equipment.index.tab', 'EQ_Warning::stat_tab');
        Event::bind('equipment.index.tab.content', 'EQ_Warning::stat_tab_content', 0, 'warning_stat');
    }

    public static function stat_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->is_allowed_to('查看预警统计', $equipment)) {
            $tabs
                ->add_tab('warning_stat', [
                    'url'    => URI::url('!equipments/equipment/index.' . $equipment->id . '.warning_stat'),
                    'title'  => I18N::T('eq_warning', '预警统计'),
                    'weight' => 30,
                ]);
        }
    }

    public static function stat_tab_content($e, $tabs)
    {
        $equipment = $tabs->equipment;

        $params = Config::get('system.controller_params');

        // 仪器送样按时间搜索：如果下次未按时间搜索应取消上次的选择操作
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_warning_', 300);
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
        $default_unit = $form['unit'] ?? self::UNIT_MONTH;
        $rule = O('eq_warning_rule',['equipment'=>$equipment,'unit'=>$default_unit]);
        $tabs->content  = V('eq_warning:equipment/stat',['unit'=>$form['unit'],'equipment'=>$equipment,'rule' => $rule]);

    }

    public static function check_offline($e,$me,$form){
        $equipments = Q("{$me}<incharge equipment[!status][control_mode=computer,power][!connect]");
        foreach($equipments as $equipment){
            $key = 'eq_warning.offline';
            Notification::send($key, $me, [
                '%user' => Markup::encode_Q($me),
                '%equipment' => Markup::encode_Q($equipment),
                '%equipment_id' => $equipment->id,
                '%time' => date('Y-m-d H:i:s')
            ]);
        }

    }

}
	