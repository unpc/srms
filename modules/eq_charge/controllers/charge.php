<?php

class Charge_Controller extends Layout_Controller {}


class Charge_AJAX_Controller extends AJAX_Controller {

	function index_edit_submit($id=0) {
		return;//仪器收费暂时只能浏览不可编辑
		/*
		$form = Form::filter(Input::form())
					->validate('charge_id', 'is_numeric')
					->validate('charge_mode', 'is_numeric')
					->validate('unit_price', 'is_numeric')
					->validate('dtstart', 'number(<dtend)');
		
		//var_dump($form);die;
		$charge = O('eq_charge', $form['charge_id']);
		
		$charge->dtstart = $form['dtstart'];
		$charge->dtend = $form['dtend'];
		if($charge->dtstart > $charge->dtend){
			JS::alert('您选择的时间不被允许!');	
			return;
		}
		$charge->mode = (int)$form['charge_mode'];
		$charge->unit_price = $form['unit_price'];
		$charge->calculate_amount();
		$charge->save();
		
		JS::refresh();
		*/
	}
	
	function index_choose_department_click() {
		$form = Form::filter(Input::form());
		$uniqid = $form['rel'];
		if (Event::trigger('db_sync.need_to_hidden', 'billing_department')){
            $eq_id = $form['eq_id'];
            $equipment = O('equipment', $eq_id);
            $departments = Q('billing_department[site=' . $equipment->site . ']');
        }else{
            $departments = Q('billing_department');
        }
		Output::$AJAX['#'.$uniqid] = [
			'data'=>(string)V('eq_charge:view/departments', [
						'departments' => $departments,
					]),
			'mode'=>'html'
		];
	}

	/* (xiaopei.li@2011.07.28) */
	function index_button_recharge_click($record_id = 0) {

		$form = Input::form();
		$me = L('ME');

		$record_form = [];
		parse_str($form['record_form'], $record_form);
		$record_form = Form::filter($record_form);

		$record = O('eq_record', $record_id);
		
		$equipment = O('equipment', $record_form['equipment_id']);
		if (!$record->id) {
			$record->equipment = $equipment;
		}
	
		if (!$me->is_allowed_to('修改', $record)) {
			JS::alert(I18N::T('equipments', '您无权修改此记录!'));
			JS::close_dialog();
			return;
		}

		$dtstart = $record_form['dtstart'] ? : ($record->dtstart ? : time());
        $dtend = $record_form['dtend'] ? : $record->dtend;
        $dtstart = $dtstart + 1;
        $dtend = $dtend - 1;
		
		if ($dtend > 0 && $dtstart > $dtend) {
			list($dtstart, $dtend) = [$dtend, $dtstart];
		}

		$equipment = $record->equipment;
		if (Q("eq_record[id!={$record->id}][equipment=$equipment][dtstart=$dtstart~$dtend|dtend=$dtstart~$dtend]")->total_count() > 0) {
			JS::alert(I18N::T('equipments', '您设置的时段和其他使用记录有冲突!'));
			return;
		}

		$has_new_user = FALSE;
		if ($me->access('管理仪器临时用户', $equipment)) {
			if ($record_form['user_option'] == 'new_user') {
				$has_new_user = TRUE;
				$record_form->validate('user_name', 'not_empty', I18N::T('eq_charge', '用户姓名不能为空！'))
					->validate('user_org', 'not_empty', I18N::T('eq_charge', '用户单位不能为空！'))
					->validate('user_email', 'is_email', I18N::T('eq_charge', '电子邮箱填写有误!'));
				if ($record_form->no_error) {
					$temp_lab_id = (int) Lab::get('equipment.temp_lab_id');
					$temp_lab = O('lab', $temp_lab_id);
					$user = O('user');
				}
			}
		}
		if (!$has_new_user && $record_form['user_id']) {
			$user = O('user', $record_form['user_id']);
			if (!$user->id) { //对于用户指定的不存在的用户，将用户其设为零，报错
				$record_form->set_error('user_id', I18N::T('eq_charge', '请选择有效的用户！'));
			}
			if (!Q("$user lab")->total_count()) {
				$record_form->set_error('user_id', I18N::T('eq_charge', '请确认用户有实验室！'));
			}
		}

		if (!$record_form->no_error) {
			return;
		}

		//如果用户有权限，则不进行计费，（机主根据机主是否收费配置，中心管理员不计费）
		if (($user->is_allowed_to('管理使用', $equipment) && !Lab::get('eq_charge.incharges_fee')) 
			//|| $user->access('管理所有内容')) {
            ) {
            return;
        }
		
		$record->dtstart = $dtstart;
		$record->dtend = $dtend;
		$record->samples = (int) max($record_form['samples'], Config::get('eq_record.record_default_samples'));

		$eq_preheat_cooling = Equipment_Preheat_Cooling::get_preheat_cooling($equipment, $record->ctime);
		if ($record_form['power_on_preheating'] == 'on') {
			$record->preheat = $eq_preheat_cooling->preheat_time;
		} else {
			$record->preheat = 0;
		}

		if ($record_form['shutdown_cooling'] == 'on') {
			$record->cooling = $eq_preheat_cooling->cooling_time;
		} else {
			$record->cooling = 0;
		}

		if ($user->id && $user->id != $record->user->id) {
			// 清除仪器负责人关闭设备时的状态
			if (!$record->agent->id) $record->agent = $record->user;
			$record->user = $user;
			//如果修改了使用者，应该预先将reserv与record断开关联，只是为了计算
			$record->reserv = null;
		}

		if (!$record->user->id) $record->user = $me;

		//如果修改了使用记录的时间，使预约与使用记录无关联，点击计算还未保存，则应先把$record与$reserv脱离关联
		//没有save，只是为了计算
		$reserv = $record->reserv;
		if($reserv->id && ($record->dtend < $reserv->dtstart || $record->dtstart > $reserv->dtend)) {
			$record->reserv = NULL;
		}

		$charge = O('eq_charge', ['source'=>$record]);	
		$tags = [];
		foreach((array)$record_form['charge_tags'] as $k=>$v) {
			if ($v['checked']=='on') {
				$k = rawurldecode($k);
				$tags[$k] = $v['value'];
			}
		}
		$charge->charge_tags = $tags;

        //通用渲染当前送样对象，用于提供计费需要的虚属性
        Event::trigger('eq_charge.record_render',$record,(array)$record_form,$equipment);

		$charge->source = $record;

		//自定义使用表单传入供lua计算
		if (Module::is_installed('extra')) {
			$charge->source->extra_fields = (array)$record_form['extra_fields'];
		}
		$lua = new EQ_Charge_Lua($charge);
		$result = $lua->run(['fee']);
		
		$fee = (float) round($result['fee'], 2);

		$new_fee = Event::trigger('eq_record_modify_fee', $record_form, $fee);

		if (isset($new_fee)) {
			$fee = $new_fee;
		}

		/* 与前一段函数不同之处 */
		Output::$AJAX['auto_amount'] = [$fee];
	}

	function index_reserv_button_recharge_click($reserv_id){

		if($reserv_id){
			$reserv = O('eq_reserv', $reserv_id);
		}
		else{
			return;
		}
		$me = L('ME');
		$component = $reserv->component;

		

		$form = Input::form();

		$charge = O('eq_charge', ['source'=>$reserv]);	

		if($form['reserv_form']) {

			if (!$me->is_allowed_to('修改', $component)) {
				return;
			}

			$reserv_form = [];
			parse_str($form['reserv_form'], $reserv_form);
			$reserv_form = Form::filter($reserv_form);

			if(!$reserv_form['organizer']) return;
			$organizer = O('user', $reserv_form['organizer']);

			//如果用户有权限，则不进行计费，（机主根据机主是否收费配置，中心管理员不计费）
			if (($organizer->is_allowed_to('管理使用', $component->calendar->parent) && !Lab::get('eq_charge.incharges_fee')) 
				//|| $organizer->access('管理所有内容')) {
                ) {
				return;
			}
			$reserv->organizer = $organizer;
			$reserv->dtstart = $reserv_form['dtstart'];
			$reserv->dtend = $reserv_form['dtend'];
		
			if($reserv_form['reserv_charge_tags']){
				$tags = [];
				foreach((array)$reserv_form['reserv_charge_tags'] as $k=>$v) {
					if ($v['checked']=='on') {
						$k = rawurldecode($k);
						$tags[$k] = $v['value'];
					}
				}
				$charge->charge_tags = $tags;
			}
		}
		elseif($form['record_form']){
			$record_form = [];
			parse_str($form['record_form'], $record_form);
			$record_form = Form::filter($record_form);

			$record = O('eq_record', $record_form['record_id']);
			if (!$record->id) return;
		
			if (!$me->is_allowed_to('修改', $record)) {
				JS::alert(I18N::T('equipments', '您无权修改此记录!'));
				JS::close_dialog();
				return;
			}

			$dtstart = $record_form['dtstart'] ?: time();
            $dtend = $record_form['dtend'];
            $dtstart = $dtstart + 1;
            $dtend = $dtend + 1;
			
			if ($dtend > 0 && $dtstart > $dtend) {
				list($dtstart, $dtend) = [$dtend, $dtstart];
			}
			
			$equipment = $record->equipment;
			if (Q("eq_record[id!={$record->id}][equipment=$equipment][dtstart=$dtstart~$dtend|dtend=$dtstart~$dtend]")->total_count() > 0) {
				JS::alert(I18N::T('equipments', '您设置的时段和其他使用记录有冲突!'));
				return;
			}

			$has_new_user = FALSE;
			if ($me->access('管理仪器临时用户', $equipment)) {
				if ($record_form['user_option'] == 'new_user') {
					$has_new_user = TRUE;
					$record_form->validate('user_name', 'not_empty', I18N::T('eq_charge', '用户姓名不能为空！'))
						->validate('user_org', 'not_empty', I18N::T('eq_charge', '用户单位不能为空！'))
						->validate('user_email', 'is_email', I18N::T('eq_charge', '电子邮箱填写有误!'));
					if ($record_form->no_error) {
						$temp_lab_id = (int) Lab::get('equipment.temp_lab_id');
						$temp_lab = O('lab', $temp_lab_id);
						$user = O('user');
					}
				}
			}
			if (!$has_new_user && $record_form['user_id']) {
				$user = O('user', $record_form['user_id']);
				if (!$user->id) { //对于用户指定的不存在的用户，将用户其设为零，报错
					$record_form->set_error('user_id', I18N::T('eq_charge', '请选择有效的用户！'));
				}
				if (!Q("$user lab")->total_count()) {
					$record_form->set_error('user_id', I18N::T('eq_charge', '请确认用户有实验室！'));
				}
			}

			if (!$record_form->no_error) {
				return;
			}

			//如果用户有权限，则不进行计费，（机主根据机主是否收费配置，中心管理员不计费）
			if (($user->is_allowed_to('管理使用', $equipment) && !Lab::get('eq_charge.incharges_fee')) 
				//|| $user->access('管理所有内容')) {
                ) {
	            return;
	        }
			
			$record->dtstart = $dtstart;
			$record->dtend = $dtend;
			$record->samples = (int) max($record_form['samples'], Config::get('eq_record.record_default_samples'));

			if ($user->id && $user->id != $record->user->id) {
				// 清除仪器负责人关闭设备时的状态
				if (!$record->agent->id) $record->agent = $record->user;
				$record->user = $user;
				//如果修改了使用者，应该预先将reserv与record断开关联，只是为了计算
				$record->reserv = null;
			}

			if (!$record->user->id) $record->user = $me;

			Cache::L("edit_calculate_{$record}", $record);

			if($record_form['reserv_charge_tags']){
				$tags = [];
				foreach((array)$record_form['reserv_charge_tags'] as $k=>$v) {
					if ($v['checked']=='on') {
						$k = rawurldecode($k);
						$tags[$k] = $v['value'];
					}
				}
				$charge->charge_tags = $tags;
			}

		}

        //通用渲染当前送样对象，用于提供计费需要的虚属性
        Event::trigger('eq_charge.reserv_render',$reserv,(array)$record_form,$equipment);

        $charge->source = $reserv;

		$lua = new EQ_Charge_LUA($charge);
        
        $result = $lua->run(['fee']);
		Output::$AJAX['auto_amount'] = (float) round($result['fee'], 2);
	}


	function index_calc_sample_fee_click() {
		$me = L('ME');
		$form = Input::form();
        $sample = O('eq_sample', $form['id']);
        $equipment = O('equipment', $form['e_id']);
        $sample->equipment = $sample->equipment->id ? $sample->equipment : $equipment;
        $sample_form = [];
        parse_str($form['sample_form'], $sample_form);
        $sample_form = Form::filter($sample_form);

        if (!$equipment->id) {
			return FALSE;
		}

		$has_new_user = FALSE;
		if ($sample_form['user_option'] == 'new_user') {
			$has_new_user = TRUE;
			$sample_form->validate('user_name', 'not_empty', I18N::T('eq_charge', '用户姓名不能为空！'))
				->validate('user_org', 'not_empty', I18N::T('eq_charge', '用户单位不能为空！'))
				->validate('user_email', 'is_email', I18N::T('eq_charge', '电子邮箱填写有误!'));
			if ($sample_form->no_error) {
				$temp_lab_id = (int) Lab::get('equipment.temp_lab_id');
				$temp_lab = O('lab', $temp_lab_id);
				$user = O('user');
				$user->creator = $me;
			}
			else {
				JS::dialog(V('eq_sample:edit/edit',[
								 'sample'=>$sample,
								 'form'=>$sample_form,
								 ]));
			}
		}
		else {
			$user = O('user', $sample_form['sender']);
		}
		
		$fee = 0;
		
		if (!$me->is_allowed_to('修改', $sample) && empty(Event::trigger('eq_sample.charge_forecast', $sample, $equipment))) {
			Output::$AJAX = $fee;
			return;
		}
		
		if (!$sample->id) {
			$sample = O('eq_sample');
		}


		//如果用户有权限，则不进行计费，（机主根据机主是否收费配置，中心管理员不计费）
		if ($user->id && $user->is_allowed_to('管理使用', $equipment) && !Lab::get('eq_charge.incharges_fee')) {
            $fee = 0;
        }
        else{
            $sample->sender = $user;
            $sample->count = $form['count'];
            $sample->equipment = $equipment;
            $sample->dtpickup = $sample_form['dtpickup'];
            $sample->dtstart = $sample_form['dtstart'];
            $sample->dtend = $sample_form['dtend'];
            $sample->dtsubmit = $sample_form['dtsubmit'];

            //通用渲染当前送样对象，用于提供计费需要的虚属性
            Event::trigger('eq_charge.sample_render',$sample,(array)$sample_form,$equipment);

            $charge = O('eq_charge');
            $charge->source = $sample;

			$tags = [];
			foreach((array)$sample_form['sample_charge_tags'] as $k=>$v) {
				if ($v['checked']=='on') {
					$k = rawurldecode($k);
					$tags[$k] = $v['value'];
				}
			}

			$charge->charge_tags = $tags;

			//自定义送样表单传入供lua计算
			if (Module::is_installed('extra')) {
				$charge->source->extra_fields = (array)$sample_form['extra_fields'];
			}

        	$lua = new EQ_Charge_LUA($charge);

       		$result = Event::trigger('eq_charge.lua_cal_ext_amount',$equipment,$lua) ?: $lua->run(['fee']);
       		$fee = $result['fee'];
        }

		Output::$AJAX['fee'] = (float) round($fee, 2);
		Output::$AJAX['extra_result'] = $result ?? [];

	
	}
	
	function index_status_select_change() {
		$form = Input::form();

        $sample = O('eq_sample', $form['id']);
        $equipment = O('equipment', $form['e_id']);
		$can_charge = $form['can_charge'];
		$sender_id = $form['sender_id'];

		if (in_array($form['status'], Event::trigger('sample.charge_status'))) {
			$view = V('eq_charge:edit/sample/charge_input', ['sample' => $sample, 'can_charge' => $can_charge, 'equipment'=>$equipment,'sender_id'=>$sender_id]);
		}
		else {
			$view = '';
		}
        
        Output::$AJAX['charge_input'] = (string) $view;
	}
}

