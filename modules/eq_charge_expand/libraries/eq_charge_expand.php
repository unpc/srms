<?php

class EQ_Charge_Expand {
	/**
	 *  仪器计费设置中添加三个tab页
	 */
	static function charge_edit_content_tabs($e, $tabs) {
		$equipment = $tabs->equipment;
		// 机时补贴费
		if ($equipment->charge_template) {
			$tabs->content->third_tabs
			     ->add_tab('subsidy', [
					'url'    => $equipment->url('charge.subsidy', NULL, NULL, 'edit'),
					'title'  => I18N::T('eq_charge', '机时补贴'),
					'weight' => 7,
				]);
			// 耗材费
			$tabs->content->third_tabs
			     ->add_tab('expend', [
					'url'    => $equipment->url('charge.expend', NULL, NULL, 'edit'),
					'title'  => I18N::T('eq_charge', '耗材计费'),
					'weight' => 6,
				]);

			Event::bind('equipment.charge.edit.content', 'EQ_Charge_expand::edit_charge_subsidy', 7, 'subsidy');
			Event::bind('equipment.charge.edit.content', 'EQ_Charge_expand::edit_charge_expend', 6, 'expend');
		}
	}

	static function edit_charge_subsidy($e, $tabs) {
		$equipment = $tabs->equipment;
		$form      = Input::form();

		if ($form['submit']) {
			$setting      = [];
			$setting['*'] = [
				'hour'   => max((float) $form['subsidy_hour'], 0),
				'sample' => max((float) $form['subsidy_sample'], 0),
			];

			foreach ((array) $form['special_tags'] as $id => $tags) {
				$tags = @json_decode($tags);
				foreach ($tags as $t) {
					$setting[$t] = [
						'hour'   => max((float) $form['special_subsidy_hour'][$id], 0),
						'sample' => max((float) $form['special_subsidy_sample'][$id], 0),
					];
				}
			}

			if (EQ_Charge_expand::put_charge_subsidy_setting($equipment, $setting)) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '机时补贴计费更新成功'));
			} else {
				Lab::message(Lab::MESSAGE_ERROR, i18n::T('eq_charge', '机时补贴计费更新失败'));
			}
		}
		$tabs->content = V('eq_charge_expand:edit/setup/subsidy', ['equipment' => $equipment]);
	}

	static function edit_charge_expend($e, $tabs) {
		$equipment = $tabs->equipment;
		$form      = Input::form();

		if ($form['submit']) {
			$setting      = [];
			$setting['*'] = [
				'hour'   => max((float) $form['expend_hour'], 0),
				'sample' => max((float) $form['expend_sample'], 0),
			];

			foreach ((array) $form['special_tags'] as $id => $tags) {
				$tags = @json_decode($tags);
				foreach ($tags as $t) {
					$setting[$t] = [
						'hour'   => max((float) $form['special_expend_hour'][$id], 0),
						'sample' => max((float) $form['special_expend_sample'][$id], 0),
					];
				}
			}

			if (EQ_Charge_expand::put_charge_expend_setting($equipment, $setting)) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '耗材计费更新成功'));
			} else {
				Lab::message(Lab::MESSAGE_ERROR, i18n::T('eq_charge', '耗材计费更新失败'));
			}
		}
		$tabs->content = V('eq_charge_expand:edit/setup/expend', ['equipment' => $equipment]);
	}

	//获取配置
	static function get_charge_subsidy_setting($equipment) {
		return (array) $equipment->subsidy_setting;
	}

	static function get_charge_expend_setting($equipment) {
		return (array) $equipment->expend_setting;
	}

	//存储配置
	static function put_charge_subsidy_setting($equipment, $setting) {
		$equipment->subsidy_setting = $setting;
		return $equipment->save();
	}

	static function put_charge_expend_setting($equipment, $setting) {
		$equipment->expend_setting = $setting;
		return $equipment->save();
	}

	// 存储收费额外字段
	static function charge_saved($e, $charge, $old_data, $new_data) {
		//新创建eq_charge
		if (!$old_data['id'] && $new_data['id']) {
			$charge_expand = O('eq_charge_expand');
		} else {
			$charge_expand = O('eq_charge_expand', ['charge' => $charge]);
		}
		
		$charge_expand->charge = $charge;
		$charge_expand->calculate_minimum();
		$charge_expand->calculate_subsidy();
		$charge_expand->calculate_expend();
		$charge_expand->save();
	}

	// 删除收费
	static function charge_deleted($e, $charge) {
		O('eq_charge_expand', ['charge' => $charge])->delete();
	}

	// 打印收费
	static function charge_print_minimum($e, $view, $output) {
		$e->return_value = (string) V('eq_charge_expand:print_charges_table/data/minimum_fee', ['c' => $view->c]);
		return FALSE;
	}

	static function charge_print_subsidy($e, $view, $output) {
		$e->return_value = (string) V('eq_charge_expand:print_charges_table/data/subsidy_fee', ['c' => $view->c]);
		return FALSE;
	}

	static function charge_print_expend($e, $view, $output) {
		$e->return_value = (string) V('eq_charge_expand:print_charges_table/data/expend_fee', ['c' => $view->c]);
		return FALSE;
	}

	// 导出收费
	static function charge_export($e, $charge, $valid_columns, $data) {
		$expands = ['minimum_fee', 'subsidy_fee', 'expend_fee'];
		foreach ($expands as $expand) {
			if (array_key_exists("{$expand}", $valid_columns)) {
				$data[] = round(O('eq_charge_expand', ['charge' => $charge])->$expand, 2);
			}
		}
		$e->return_value = $data;
	}
}
