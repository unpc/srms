<?php

class Wait_Join {

	static function setup($e, $controller, $method, $params)
	{
		Event::bind('equipment.index.tab', 'Wait_Join::equipment_index');
	}

	static function equipment_index($e, $tabs)
	{
		$equipment = $tabs->equipment;
		$tabs->add_tab('wait_join', [
				'url' => $equipment->url('wait_join'),
				'title' =>I18N::T('equipments', '预约等待'),
				'weight' => 11,
			]);
		Event::bind('equipment.index.tab.content', 'Wait_Join::equipment_index_wait_join', 0, 'wait_join');
	}

	static function equipment_index_wait_join($e, $tabs)
	{
		$equipment = $tabs->equipment;
		$me = L('ME');
		$form = Lab::form();
		$params = (array)explode('.', Input::arg(1));
		$param = array_pop($params);

		$tab = in_array($param, Eq_Wait_Join_Model::$ref_status) ? $param : Eq_Wait_Join_Model::$ref_status[0];

		if (!$me->is_allowed_to('管理预约等待', $equipment)) {
			$tab = 'no_use';
		}

		$selector = "eq_wait_join";

		$selector .= "[equipment={$equipment}]";

		$pre_selectors = [];

		if ($tab) {
			$status = (int)array_flip(Eq_Wait_Join_Model::$ref_status)[$tab];
			$selector .= "[status={$status}]";
		}

		if ($form['time']) {
			$time = H($form['time']);
			$time_format = H($form['time_format']);
			$selector .= "[time={$time}][time_format={$time_format}]";
		}

		if ($form['name']) {
			$name = H($form['name']);
			$pre_selectors[] = "user[name*={$name}]";
		}

		if (count($pre_selectors)) {
			$selector = '( ' . join(', ', $pre_selectors) . ' ) ' . $selector;
		}

		$selector .= ":sort(ctime D)";

		$waiters = Q($selector);

		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);

		$pagination = Lab::pagination($waiters, $start, $per_page);

		$content = V('eq_wait_join:list', [
				'form' => $form,
				'equipment' => $equipment,
				'pagination' => $pagination,
				'waiters' => $waiters
			]);


		$content->secondary_tabs
			= Widget::factory('tabs')
				->set('equipment', $equipment)
				->set('class', 'secondary_tabs');

		foreach (Eq_Wait_Join_Model::$status as $key => $value) {
			$ref = Eq_Wait_Join_Model::$ref_status[$key];
			if (!$me->is_allowed_to('管理预约等待', $equipment) && $key == Eq_Wait_Join_Model::COMPLETE) {
				continue;
			}
			$content->secondary_tabs
				->add_tab($ref, [
					'url' => $equipment->url("wait_join.{$ref}"),
					'title' =>I18N::T('eq_wait_join', $value),
					'weight' => 0,
				]);
		}


		$content->secondary_tabs->select($tab);

		$tabs->content = $content;
	}

	public static function on_equipment_reserv_form_submit($e, $equipment, $form)
	{
		if ($equipment->accept_reserv) {
			$equipment->wait_join_for_reserv = $form['wait_join_for_reserv'] == 'on' ? TRUE : NULL;
		}
		else {
			$equipment->wait_join_for_reserv = NULL;
		}
		return TRUE;
	}

	public static function on_component_add_failed($e, $equipment, $links)
	{
		if ($equipment->name() != 'equipment') return;
		if (!$equipment->id) return;
		if (!$equipment->accept_reserv) return;
		if (!$equipment->wait_join_for_reserv) return;
		$me = L('ME');
		$calendar = O('calendar', ['parent' => $equipment]);
		if (!$me->is_allowed_to('添加事件', $calendar)) return;
		$links['wait_join'] = [
			'text'  => I18N::T('eq_wait_join', '申请预约等待'),
			'url' => '#',
			'extra' => 'class="select" q-object="reserv_fail_wait_join_user" q-event="click" '.
					'q-src="'.URI::url('!eq_wait_join').'" q-static="'.H(['eqId' => $equipment->id]).'"'
		];
	}

	public static function equipment_ACL($e, $me, $perm_name, $object, $options)
	{
		switch ($perm_name) {
			case '管理预约等待':
				if ($me->is_allowed_to('修改', $object)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
		}
	}

}
