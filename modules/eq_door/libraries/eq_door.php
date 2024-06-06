<?php

class EQ_Door {
	static function setup() {
		Event::bind('equipment.edit.tab', 'EQ_Door::_edit_equipment_tab', 0, 'eq_door');
		Event::bind('equipment.edit.content', 'EQ_Door::_edit_equipment_content', 0, 'eq_door');
	}

	static function setup_meeting() {
		Event::bind('meeting.edit.tab', 'EQ_Door::_edit_meeting_tab', 0, 'eq_door');
		Event::bind('meeting.edit.content', 'EQ_Door::_edit_meeting_content', 0, 'eq_door');
	}
	
	static function setup_door() {
		Event::bind('door.index.tab', 'EQ_Door::_view_door_tab', 0, 'equipments');
		Event::bind('door.index.tab.content', 'EQ_Door::_view_door_content', 0, 'equipments');
	}

	static function setup_profile() {
        if (!People::perm_in_uno()){
            Event::bind('profile.edit.tab', 'EQ_Door::_edit_profile_tab');
            Event::bind('profile.edit.content', 'EQ_Door::_edit_profile_content', 0, 'door');
        }

		Event::bind('profile.view.tab', 'EQ_Door::_index_profile_tab');
		Event::bind('profile.view.content', 'EQ_Door::_index_profile_content', 0, 'door');
	}
	
	static function _edit_equipment_tab($e, $tabs) {
		$equipment = $tabs->equipment;
		if (L('ME')->is_allowed_to('关联门禁', $equipment)) {
			$tabs->add_tab('eq_door', [
				'title' => I18N::T('eq_door', '门禁设置'),
				'url' => $equipment->url('eq_door', NULL, NULL, 'edit'),
				'weight' => 90
			]);
		}
	}
	
	static function _edit_equipment_content($e, $tabs) {
		$equipment = $tabs->equipment;
		$form = Form::filter(Input::form());
		if ($form['submit']) {

            if ((int) $form['slot_card_ahead_time'] < 0 || ! is_numeric($form['slot_card_ahead_time']) || ((int) $form['slot_card_ahead_time'] != $form['slot_card_ahead_time'])) {
                $form->set_error('slot_card_ahead_time', I18N::T('eq_door', '请填写非负整数数字!'));
            }
			if ((int) $form['slot_card_delay_time'] < 0 || ! is_numeric($form['slot_card_delay_time']) || ((int) $form['slot_card_delay_time'] != $form['slot_card_delay_time'])) {
                $form->set_error('slot_card_delay_time', I18N::T('eq_door', '请填写非负整数数字!'));
            }
			
			if ($form->no_error) {
				$equipment->slot_card_ahead_time = $form['slot_card_ahead_time'];
				$equipment->slot_card_delay_time = $form['slot_card_delay_time'];
				$doors = $form['special_doors'];
				self::eq_door_connect($doors, $equipment, 'door');
				$equipment->save();
				
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_door', '门禁设置更新成功!'));
			}
		}
		$content = V('eq_door:door/edit.equipment', ['equipment'=>$equipment, 'form'=>$form]);
		$tabs->content = $content;
	}
	
	static function _view_door_tab($e, $tabs) {
		$door = $tabs->door;
		$equipments = Q("{$door}<asso equipment");
		if ($equipments->total_count()) {
			$tabs->add_tab('equipments', [
				'url' => $door->url('equipments'),
				'title' => I18N::T('eq_door', '关联仪器')
			]);
		}
	}
	
	static function _view_door_content($e, $tabs) {
		$door = $tabs->door;
		$equipments = Q("{$door}<asso equipment");
		$content = V('eq_door:door/equipment/index', ['door'=>$door, 'equipments'=>$equipments]);
		$tabs->content = $content;
	}

	static function _edit_profile_tab($e, $tabs) {
		$me = L('ME');
		if ($me->is_allowed_to('添加', 'door')) {
			$tabs->add_tab('door', [
				'url' => $tabs->user->url('door', NULL, NULL, 'edit'),
				'title' => I18N::T('eq_door', '负责门禁'),
				'weight' => 70,
			]);
		}
	}

	static function _edit_profile_content($e, $tabs) {
		$user = $tabs->user;
		$selector = "{$user}<incharge door";
		$doors = Q($selector);
		
		$form = Form::filter(Input::form());
		if ($form['submit']) {			
			if ($form->no_error) {
				$special_doors = $form['special_doors'];
				self::eq_door_connect($special_doors, $user, 'door', 'incharge');
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_door', '门禁设置更新成功!'));
			}
		}
		
		$content = V('eq_door:door/edit.profile', ['doors' => $doors, 'user' => $user, 'form' => $form]);
		$tabs->content = $content;
	}

	static function _index_profile_tab($e, $tabs) {
		$user = $tabs->user;
		$doors = Q("{$user}<incharge door");
		if ($doors->total_count()) {
			$tabs->add_tab('door', [
				'url' => $user->url('door'),
				'title' => I18N::T('eq_door', '负责门禁'),
				'weight' => 0,
			]);
		}
	}

	static function _index_profile_content($e, $tabs) {
		$user = $tabs->user;
		$selector = "{$user}<incharge door";
		$doors = Q($selector);
		
		$content = V('eq_door:door/profile/index', ['doors' => $doors]);
		$tabs->content = $content;
	}

	static function equipment_dashboard_sections($e, $equipment, $sections) {
		$doors = Q("{$equipment}<asso door");
		if (count($doors)) {
			$sections[] = V('eq_door:door/equipment.section', ['equipment'=>$equipment, 'doors'=>$doors]);
		}
	}
	
	static function operate_door_is_allowed($e, $user, $direction, $door) {
		$equipments = Q("{$door}<asso equipment");

		//direction无用
        foreach ($equipments as $equipment) {
			if($user->is_allowed_to('管理使用', $equipment)) {
				$e->return_value = TRUE;
                return FALSE;
			}
			// 关联案例20190749, 案例20192805:
			// 门禁关联多个仪器, 有一个仪器满足上机条件则可开门(此hook return true)
			// 若仪器仅接收送样, 不可开门 (不用进行cannot_access判断)
			if ($equipment->accept_sample && !$equipment->accept_reserv) {
				continue;
			}
            $now = time();
			$dtstart = $now - $equipment->slot_card_delay_time * 60;
            $dtend = $now + $equipment->slot_card_ahead_time * 60;

            if (!$equipment->cannot_access_with_door($user, $dtstart, $dtend)) {
                Date::set_time();
                $e->return_value = TRUE;
                return FALSE;
            }
        }
	}

	static function operate_eq_door_is_allowed($e, $user, $perm, $equipment, $params) {	
		switch ($perm) {
			case '关联门禁':
				if ($user->access('管理所有仪器的门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('管理负责仪器的门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}

	static function eq_door_connect($subjects, $object, $s_name, $type = 'asso') {
		switch ($s_name) {
			case 'equipment':
			case 'door':
				break;
			default:
				return;
		}
		
		$old_subjects = Q("{$object}<{$type} {$s_name}")->to_assoc('id', 'id');
		if (count($subjects)) foreach ($subjects as $s_id) {
			if (!$s_id) continue;
			$subject = O("$s_name", $s_id);
			$subject_id = $subject->id;
			if (!$subject_id) continue;
			if (in_array($subject_id, $old_subjects)) {
				unset($old_subjects[$subject_id]);
				continue;
			}
			if ('equipment' == $subject->name()) {
				$subject->connect($object, $type);
			}
			elseif ('door' == $subject->name()) {
				$object->connect($subject, $type);
			}
			$me = L('ME');
			Log::add(strtr('[eq_door] %user_name[%user_id]关联%subject_name[%subject_id]与%object_name[%object_id]门禁', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%subject_name' => $subject->name,
						'%subject_id' => $subject->id,
						'%object_name' => $object->name,
						'%object_id' => $object->id,				
			]), 'journal');
		}
		
		if (count($old_subjects)) foreach ($old_subjects as $s_id) {
			$subject = O("$s_name", $s_id);
			if ('equipment' == $subject->name()) {
				$subject->disconnect($object, $type);
			}
			elseif ('door' == $subject->name()) {
				$object->disconnect($subject, $type);
			}
			$me = L('ME');
			Log::add(strtr('[eq_door] %user_name[%user_id]断开%subject_name[%subject_id]与%object_name[%object_id]的门禁', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%subject_name' => $subject->name,
						'%subject_id' => $subject->id,
						'%object_name' => $object->name,
						'%object_id' => $object->id,
			]), 'journal');
		}
	}
}
