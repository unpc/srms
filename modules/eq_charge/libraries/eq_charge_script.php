<?php
class EQ_Charge_Script{

	static function template_reserv_setting_view($e, $equipment, $form) {
		$charge_type = $equipment->charge_template['reserv'];
		$template = Config::get('eq_charge.template')[$charge_type];
		$charge_title = $template['title'];
		$charge_tags = $template['content']['reserv']['charge_tags'];
		$i18n_module = $template['i18n_module'];
		$charge_default_setting = $template['content']['reserv']['params']['%options'];

		if($form['submit']){
			if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备预约收费信息更新失败'));
				URI::redirect();
			}

			$reserv_setting['*'] = [
				'unit_price' => max(round($form['reserv_unit_price'], 2), 0),
				'minimum_fee' => max(round($form['reserv_minimum_fee'], 2), 0)
			];

			$root = $equipment->get_root();	
			$tags = $form['special_tags'];
			$prices = $form['special_unit_price'];
			$minimum_fees = $form['special_minimum_fee'];
			
			if ($tags) {
			    foreach ($tags as $i => $tag) {
					if ($tag) { 
						$special_tags = @json_decode($tag, TRUE);
						
						if ($special_tags) foreach($special_tags as $tag) {
							//限制该仪器设定的收费标签必须是仪器root下真实存在的标签
							$t = O('tag', ['root'=>$root, 'name'=>$tag]);
                            $tt = O('tag_equipment_user_tags', ['root'=> Tag_Model::root('equipment_user_tags'), 'name'=> $tag]);
                            //属于该仪器的标签或者全局仪器的用户标签
							if ($t->id || $tt->id) {
								$reserv_setting[$tag] = [
									'unit_price' => round($prices[$i], 2),
									'minimum_fee' => round($minimum_fees[$i],2)
									];
							}
						}
					}
			    }
			}

			$params = EQ_Lua::array_p2l($reserv_setting);
			if(EQ_Charge::update_charge_script($equipment, 'reserv', ['%options'=>$params])){
				EQ_Charge::put_charge_setting($equipment, 'reserv', $reserv_setting);

				if (Module::is_installed('yiqikong')) {
					CLI_YiQiKong::update_equipment_setting($equipment->id);
				}
				
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备预约收费信息已更新'));
			}
		}	

		$e->return_value = V('eq_charge:edit/setup/reserv/'.$charge_type, ['equipment' => $equipment,
				'charge_title' => I18N::T($i18n_module, $charge_title), 
				'charge_tags' => $charge_tags, 
				'charge_default_setting' => $charge_default_setting]);
		return FALSE;
	}

	//使用计费相关的3各模板，只有个别文字不同，所以都hook到该方法处理。
	static function template_record_script_setting_view($e, $equipment, $form) {
		$charge_type = $equipment->charge_template['record'];
		$template = Config::get('eq_charge.template')[$charge_type];
		$charge_title = $template['title'];
		$i18n_module = $template['i18n_module'];
		$charge_tags = $template['content']['record']['charge_tags'];
		$charge_default_setting = $template['content']['record']['params']['%options'];

		if($form['submit']){
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备使用收费信息更新失败'));
                URI::redirect();
            }

            //update at 18-12-26 by changchun.qi【定制】RQ184410-北京大学-计费设置中增加“折扣计费”收费方式
            try{
                $triggerResult = Event::trigger('eq_charge.equipment.record_time_discount',$charge_type,$equipment, $form);
                if($triggerResult && is_array($triggerResult) && !empty($triggerResult)){
                    $params = EQ_Lua::array_p2l($triggerResult);
                    if(EQ_Charge::update_charge_script($equipment, 'record', ['%options'=>$params])){
						EQ_Charge::put_charge_setting($equipment, 'record', $triggerResult);
						
						if (Module::is_installed('yiqikong')) {
							CLI_YiQiKong::update_equipment_setting($equipment->id);
						}

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备使用收费信息已更新'));
                        $e->return_value = V('eq_charge:edit/setup/record/'.$charge_type, ['equipment' => $equipment,
                            'charge_title' => I18N::T($i18n_module, $charge_title),
                            'charge_tags' => $charge_tags,
                            'charge_default_setting' => $charge_default_setting]);
                        return false;
                    }
                }
            }catch (Exception $e){
                if($e->getCode() == '12306'){
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', $e->getMessage()));
                    unset($form['submit']);
                    URI::redirect(null,$form);
                }
            }
            //end update

            //将仪器的设置存储到文件中
            $record_setting['*'] = [
				'unit_price' => max(round($form['record_unit_price'], 2), 0),
				'minimum_fee' => max(round($form['record_minimum_fee'], 2), 0)
			];

            $root = $equipment->get_root();
			$tags = $form['special_tags'];
			$prices = $form['special_unit_price'];
			$minimum_fees = $form['special_minimum_fee'];
            if ($tags) {
                foreach ($tags as $i => $tag) {
					if ($tag) { 
						$special_tags = @json_decode($tag, TRUE);
						
						if ($special_tags) foreach($special_tags as $tag) {
							//限制该仪器设定的收费标签必须是仪器root下真实存在的标签
							$t = O('tag', ['root'=>$root, 'name'=>$tag]);
                            $tt = O('tag_equipment_user_tags', ['root'=> Tag_Model::root('equipment_user_tags'), 'name'=> $tag]);
							if ($t->id || $tt->id) {
								$record_setting[$tag] = [
									'unit_price' => round($prices[$i], 2),
									'minimum_fee' => round($minimum_fees[$i],2)
									];
							}
						}
					}
			    }
			}

			$params = EQ_Lua::array_p2l($record_setting);
			if(EQ_Charge::update_charge_script($equipment, 'record', ['%options'=>$params])){
				EQ_Charge::put_charge_setting($equipment, 'record', $record_setting);

				if (Module::is_installed('yiqikong')) {
					CLI_YiQiKong::update_equipment_setting($equipment->id);
				}

				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备使用收费信息已更新'));
			}
		}	

		$e->return_value = V('eq_charge:edit/setup/record/'.$charge_type, ['equipment' => $equipment,
								'charge_title' => I18N::T($i18n_module, $charge_title), 
								'charge_tags' => $charge_tags, 
								'charge_default_setting' => $charge_default_setting]);
		return FALSE;
	}

	static function template_sample_count_setting_view($e, $equipment, $form) {
		$charge_type = $equipment->charge_template['sample'];
		$template = Config::get('eq_charge.template')[$charge_type];
		$charge_title = $template['title'];
		$i18n_module = $template['i18n_module'];
		$charge_tags = $template['content']['sample']['charge_tags'];
		$charge_default_setting = $template['content']['sample']['params']['%options'];

		if($form['submit']){
			if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备送样收费信息更新失败'));
				URI::redirect();
			}
		//将仪器的设置存储到文件中
			$sample_setting['*'] = [
				'unit_price' => max(round($form['sample_unit_price'], 2), 0),
				'minimum_fee' => max(round($form['sample_minimum_fee'], 2), 0)
			];

			$root = $equipment->get_root();	
			$tags = $form['special_tags'];
			$prices = $form['special_unit_price'];
			$minimum_fees = $form['special_minimum_fee'];
			
			if ($tags) {
			    foreach ($tags as $i => $tag) {
					if ($tag) { 
						$special_tags = @json_decode($tag, TRUE);
						
						if ($special_tags) foreach($special_tags as $tag) {
							//限制该仪器设定的收费标签必须是仪器root下真实存在的标签
							$t = O('tag', ['root'=>$root, 'name'=>$tag]);
                            $tt = O('tag_equipment_user_tags', ['root'=> Tag_Model::root('equipment_user_tags'), 'name'=> $tag]);
							if ($t->id || $tt->id) {
								$sample_setting[$tag] = [
									'unit_price' => round($prices[$i], 2),
									'minimum_fee' => round($minimum_fees[$i],2)
									];
							}
						}
					}
			    }
			}
			$params = EQ_Lua::array_p2l($sample_setting);
			if(EQ_Charge::update_charge_script($equipment, 'sample', ['%options'=>$params])){
				EQ_Charge::put_charge_setting($equipment, 'sample', $sample_setting);

				if (Module::is_installed('yiqikong')) {
					CLI_YiQiKong::update_equipment_setting($equipment->id);
				}

				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备送样收费信息已更新'));
			}
		}

		$e->return_value = V('eq_charge:edit/setup/sample/sample_count', ['equipment' => $equipment, 
			'charge_title' => I18N::T($i18n_module, $charge_title) , 
			'charge_tags' => $charge_tags, 
			'charge_default_setting' => $charge_default_setting]);
		return FALSE;
	}

	static function template_sample_time_script_setting_view($e, $equipment, $form) {
		$charge_type = $equipment->charge_template['sample'];
		$template = Config::get('eq_charge.template')[$charge_type];
		$charge_title = $template['title'];
		$i18n_module = $template['i18n_module'];
		$charge_tags = $template['content']['sample']['charge_tags'];
		$charge_default_setting = $template['content']['sample']['params']['%options'];

		if($form['submit']){
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '设备送样收费信息更新失败'));
                URI::redirect();
            }

            //将仪器的设置存储到文件中
            $sample_setting['*'] = [
				'unit_price' => max(round($form['sample_unit_price'], 2), 0),
				'minimum_fee' => max(round($form['sample_minimum_fee'], 2), 0)
			];

            $root = $equipment->get_root();
			$tags = $form['special_tags'];
			$prices = $form['special_unit_price'];
			$minimum_fees = $form['special_minimum_fee'];

            if ($tags) {
                foreach ($tags as $i => $tag) {
					if ($tag) { 
						$special_tags = @json_decode($tag, TRUE);
						
						if ($special_tags) foreach($special_tags as $tag) {
							//限制该仪器设定的收费标签必须是仪器root下真实存在的标签
							$t = O('tag', ['root'=>$root, 'name'=>$tag]);
                            $tt = O('tag_equipment_user_tags', ['root'=> Tag_Model::root('equipment_user_tags'), 'name'=> $tag]);
							if ($t->id || $tt->id) {
								$sample_setting[$tag] = [
									'unit_price' => round($prices[$i], 2),
									'minimum_fee' => round($minimum_fees[$i],2)
									];
							}
						}
					}
			    }
			}

			$params = EQ_Lua::array_p2l($sample_setting);
			if(EQ_Charge::update_charge_script($equipment, 'sample', ['%options'=>$params])){
				EQ_Charge::put_charge_setting($equipment, 'sample', $sample_setting);

				if (Module::is_installed('yiqikong')) {
					CLI_YiQiKong::update_equipment_setting($equipment->id);
				}

				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备使用收费信息已更新'));
			}
		}	

		$e->return_value = V('eq_charge:edit/setup/sample/'.$charge_type, ['equipment' => $equipment,
								'charge_title' => I18N::T($i18n_module, $charge_title), 
								'charge_tags' => $charge_tags, 
								'charge_default_setting' => $charge_default_setting]);
		return FALSE;
	}

}
