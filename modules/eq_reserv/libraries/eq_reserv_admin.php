<?php

class EQ_Reserv_Admin {

	static function setup(){
		
		if(L('ME')->access('管理所有内容') || L('ME')->access('添加/修改所有机构的仪器')){
			Event::bind('admin.equipment.tab', 'EQ_Reserv_Admin::_secondary_tab');
		}
	}
	
	static function _secondary_tab($e, $tabs){
		Event::bind('admin.equipment.content', 'EQ_Reserv_Admin::_secondary_content', 0, 'reserv');

		$tabs
		->add_tab('reserv', [
			'url'=>URI::url('admin/equipment.reserv'),
			'title'=> I18N::T('eq_reserv', '预约设置'),
		]);

	}
	
	static function _secondary_content($e, $tabs){

		if (Input::form('submit')) {
			
			$form = Form::filter(Input::form());//这个验证
			
			if($form['add_reserv_earliest_time'] < 0) {
				$form->set_error('add_reserv_earliest_time', I18N::T('eq_reserv', '添加预约最早提前时间必须大于等于零!'));
			}

			if($form['add_reserv_latest_time'] < 0) {
				$form->set_error('add_reserv_latest_time', I18N::T('eq_reserv', '添加预约最晚提前时间必须大于等于零!'));
			}

			if($form['modify_reserv_latest_time'] < 0) {
				$form->set_error('modify_reserv_latest_time', I18N::T('eq_reserv', '修改预约最晚提前时间必须大于等于零!'));
			}

            if($form['delete_reserv_latest_time'] < 0) {
                $form->set_error('delete_reserv_latest_time', I18N::T('eq_reserv', '删除预约最晚提前时间必须大于等于零!'));
            }
			
			if($form->no_error) {

				Lab::set('equipment.add_reserv_earliest_limit', NULL, '*');
				Lab::set('equipment.add_reserv_latest_limit', NULL, '*');
				Lab::set('equipment.modify_reserv_latest_limit', NULL, '*');
				Lab::set('equipment.delete_reserv_latest_limit', NULL, '*');

				$specific_tags = $form['specific_tags'];
				$seeting_tags = [];

				if ($specific_tags) {
				    foreach ($specific_tags as $i => $tags) {
				    	$tags = @json_decode($tags, TRUE);
				    	if ($tags) foreach ($tags as $tag) {
				    		$seeting_tags[] = $tag;
				    		if ($form['specific_add_earliest_limit'][$i] == 'customize') {
				    			$add_reserv_earliest_limit = Date::convert_interval($form['specific_add_reserv_earliest_time'][$i],$form['specific_add_reserv_earliest_format'][$i]);
				    			Lab::set('equipment.add_reserv_earliest_limit', (int) $add_reserv_earliest_limit, $tag);
				    		}
				    		else {
				    			Lab::set('equipment.add_reserv_earliest_limit', NULL, $tag);
				    		}

				    		if ($form['specific_add_latest_limit'][$i] == 'customize') {
	    						$add_reserv_latest_limit = Date::convert_interval($form['specific_add_reserv_latest_time'][$i],$form['specific_add_reserv_latest_format'][$i]);
				    			Lab::set('equipment.add_reserv_latest_limit', (int) $add_reserv_latest_limit, $tag);
				    		}
				    		else {
				    			Lab::set('equipment.add_reserv_latest_limit', NULL, $tag);	
				    		}

				    		if ($form['specific_modify_latest_limit'][$i] == 'customize') {
	    						$modify_reserv_latest_limit = Date::convert_interval($form['specific_modify_reserv_latest_time'][$i],$form['specific_modify_reserv_latest_format'][$i]);
				    			Lab::set('equipment.modify_reserv_latest_limit', (int) $modify_reserv_latest_limit, $tag);
				    		}
				    		else {
				    			Lab::set('equipment.modify_reserv_latest_limit', NULL, $tag);	
				    		}

                            if ($form['specific_delete_latest_limit'][$i] == 'customize') {
                                $delete_reserv_latest_limit = Date::convert_interval($form['specific_delete_reserv_latest_time'][$i],$form['specific_delete_reserv_latest_format'][$i]);
                                Lab::set('equipment.delete_reserv_latest_limit', (int) $delete_reserv_latest_limit, $tag);
                            }
                            else {
                                Lab::set('equipment.delete_reserv_latest_limit', NULL, $tag);
                            }

						}
				    }	
				}

				//清除删除的tag
				$tagged = (array) Lab::get('@TAG');
				foreach ($tagged as $tag => $data) {
					if(!in_array($tag, $seeting_tags)){
						Lab::set('equipment.add_reserv_earliest_limit', NULL, $tag);
						Lab::set('equipment.add_reserv_latest_limit', NULL, $tag);
						Lab::set('equipment.modify_reserv_latest_limit', NULL, $tag);	
						Lab::set('equipment.delete_reserv_latest_limit', NULL, $tag);
					}
				}

				$add_reserv_earliest_limit = Date::convert_interval($form['add_reserv_earliest_time'], $form['add_reserv_earliest_format']);
				$add_reserv_latest_limit = Date::convert_interval($form['add_reserv_latest_time'], $form['add_reserv_latest_format']);
				$modify_reserv_latest_limit = Date::convert_interval($form['modify_reserv_latest_time'], $form['modify_reserv_latest_format']);
				$delete_reserv_latest_limit = Date::convert_interval($form['delete_reserv_latest_time'], $form['delete_reserv_latest_format']);

				Lab::set('equipment.add_reserv_earliest_limit', (int) $add_reserv_earliest_limit);
				Lab::set('equipment.add_reserv_latest_limit', (int) $add_reserv_latest_limit);
				Lab::set('equipment.modify_reserv_latest_limit', (int) $modify_reserv_latest_limit);
				Lab::set('equipment.delete_reserv_latest_limit', (int) $delete_reserv_latest_limit);

				Lab::set('equipment.need_reserv_description', $form['need_reserv_description']);

				/* 记录日志 */
				Log::add(strtr('[eq_reserv] %user_name[%user_id]修改了系统设置中的预约设置',[
						'%user_name' => L('ME')->name,
						'%user_id' => L('ME')->id,
						]),'journal');
			
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_reserv', '信息修改成功！'));
			
			}
		}
		
		$tabs->content=V('eq_reserv:admin/reserv', ['form'=>$form])
							//->set('max_overtime_duration', Lab::get('equipment.max_overtime_duration', Config::get('equipment.max_overtime_duration')) )
							
							->set('need_reserv_description', Lab::get('equipment.need_reserv_description', Config::get('equipment.need_reserv_description'), '@'));
	}
	
}
