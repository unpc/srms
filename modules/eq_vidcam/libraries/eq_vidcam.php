<?php

class EQ_Vidcam {

	static function setup() {

		//关联仪器
		Event::bind('vidcam.view.tab', 'EQ_Vidcam::_equipments_vidmon_tab', 0, 'equipments');
		Event::bind('vidcam.view.content', 'EQ_Vidcam::_equipments_vidmon_content', 0, 'equipments');

	}

	//关联仪器
	static function _equipments_vidmon_content($e, $tabs) {
		$vidcam = $tabs->vidcam;
		$equipments = Q("{$vidcam}<camera equipment");
		$content = V('eq_vidcam:equipments', ['equipments' => $equipments]);
		$tabs->content = $content;
	}

	static function _equipments_vidmon_tab($e, $tabs) {
		$vidcam = $tabs->vidcam;
		if (L('ME')->is_allowed_to('查看关联仪器', $vidcam)) {
			$tabs->add_tab('equipments', [
							   'title' => I18N::T('equipments', '关联仪器'),
							   'url' => $vidcam->url('equipments', NULL, NULL, 'view'),
							   'weight' => 110
						   ]);
		}
	}

	//关联摄像头
	static function setup_vidcam() {
		Event::bind('equipment.edit.tab', 'EQ_Vidcam::_edit_equipment_tab', 0, 'eq_vidcam');
		Event::bind('equipment.edit.content', 'EQ_Vidcam::_edit_equipment_content', 0, 'eq_vidcam');
	}

	static function _edit_equipment_tab($e, $tabs) {
		$equipment = $tabs->equipment;
		if (L('ME')->is_allowed_to('管理仪器视频监控', $equipment)) {
			$tabs->add_tab('eq_vidcam', [
							   'title' => I18N::T('eq_vidcam', '视频监控'),
							   'url' => $equipment->url('eq_vidcam', NULL, NULL, 'edit'),
							   'weight' => 100
						   ]);
		}
	}


	static function _edit_equipment_content($e, $tabs) {
		$equipment = $tabs->equipment;
		$form = Form::filter(Input::form());
		$exist = 0;
		$unexist = 0;

		if ($form['submit']) {
			if ($form->no_error) {
				$vidcams = $form['special_vidcams'];
				foreach ($vidcams as $key => $vidcam_id) {
					$vidcam = O("vidcam", $vidcam_id);
					if (!$vidcam->id) {
						unset($vidcams[$key]);
						$unexist ++;
					}
					else {
						$exist ++;
					}

				}
				self::eq_vidcam_connect($vidcams, $equipment, 'vidcam');
				$equipment->save();
				if ($exist == 0 && $unexist > 0) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_vidcam', '不存在的摄像头未添加!'));
				}
				else {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_vidcam', '摄像头设置更新成功!'));
				}
			}
			else{
				Lab::message(Lab::MESSAGE_WARNING, I18N::T('eq_vidcam', '您未做任何修改!'));
			}
		}
		$content = V('eq_vidcam:vidcam/edit.equipment', ['equipment'=>$equipment, 'form'=>$form]);
		$tabs->content = $content;

	}

	static function eq_vidcam_connect($subjects, $object, $s_name) {

		switch ($s_name) {
			case 'equipment':
			case 'vidcam':
				break;
			default:
				return;
		}

		$old_subjects = Q("{$object}<camera {$s_name}")->to_assoc('id', 'id');
		if (count($subjects)) {
			foreach ($subjects as $s_id) {
				if (!$s_id) continue;
				$subject = O("$s_name", $s_id);
				$subject_id = $subject->id;
				if (!$subject_id) continue;
				if (in_array($subject_id, $old_subjects)) {
					unset($old_subjects[$subject_id]);
					continue;
				}
				if ('equipment' == $subject->name()) {
					$subject->connect($object, 'camera');
				}
				elseif ('vidcam' == $subject->name()) {
					$object->connect($subject, 'camera');
				}
				$me = L('ME');

                Log::add(strtr('[eq_vidcam] %user_name[%user_id]关联%equipment_name[%equipment_id]与%vidcam_name[%vidcam_id]摄像头', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%equipment_name'=> $subject->name,
                    '%equipment_id'=> $subject->id,
                    '%vidcam_name'=> $object->name,
                    '%vidcam_id'=> $object->id
                ]), 'journal');
			}
		}

		if (count($old_subjects)) {
			foreach ($old_subjects as $s_id) {
				$subject = O("$s_name", $s_id);
				if ('equipment' == $subject->name()) {
					$subject->disconnect($object, 'camera');
				}
				elseif ('vidcam' == $subject->name()) {
					$object->disconnect($subject, 'camera');
				}
				$me = L('ME');
                Log::add(strtr('[eq_vidcam] %user_name[%user_id]断开%equipment_name[%equipment_id]与%vidcam_name[%vidcam_id]摄像头', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%equipment_name'=> $subject->name,
                    '%equipment_id'=> $subject->id,
                    '%vidcam_name'=> $object->name,
                    '%vidcam_id'=> $object->id
                ]), 'journal');
			}
		}
	}

	//关联的监控
	static function setup_equipment() {
		Event::bind('equipment.index.tab', 'EQ_Vidcam::index_equipment_tab');
		Event::bind('equipment.index.tab.content', 'EQ_Vidcam::index_equipment_content', 0, 'eq_vidcam');
	}


	static function index_equipment_tab($e, $tabs) {
		$me = L('ME');
		$equipment = $tabs->equipment;
		if ($me->is_allowed_to('查看仪器视频监控', $equipment)) {
			$tabs->add_tab('eq_vidcam', [
							   'url' => $equipment->url('eq_vidcam'),
							   'title' => I18N::T('eq_vidcam', '关联监控'),
							   'weight' => 100
							   ]
						  );
		}
	}


	static function index_equipment_content($e, $tabs) {
		$equipment = $tabs->equipment;


		$form = Lab::form();

		$me = L('ME');

		$selector = "{$equipment} vidcam.camera";
		if($form['name']){
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
		}


		$vidcams = Q($selector);
		$content = V('eq_vidcam:vidcam/list',[
							'equipment' => $equipment,
							'vidcams' => $vidcams,
							'form' => $form,
							]);

		$panel_buttons = [];

		if ($me->is_allowed_to('管理仪器视频监控', $equipment)) {
			$panel_buttons[] = [
				'tip'	=> I18N::T('eq_vidcam', '监控设置'),
				'text'	=> I18N::T('eq_vidcam', '监控设置'),
				'extra'	=> 'class="button button_edit"',
				'url' => $equipment->url('eq_vidcam', null, null, 'edit')
			];
		}

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons]);

		$tabs->content = $content;
	}


	static function operate_eq_vidcam_is_allowed($e, $user, $perm, $equipment, $params) {
		switch ($perm) {
			case '管理仪器视频监控':
				if (!$user->is_allowed_to('修改', $equipment)) {
					$e->return_value = FALSE;
					return FALSE;
				}
				if ($user->access('管理所有仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->group->id && $user->group->is_itself_or_ancestor_of($equipment->group) && $user->access('管理下属机构仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('管理负责仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '查看仪器视频监控':
				if ($user->access('管理所有仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->group->id && $user->group->is_itself_or_ancestor_of($equipment->group) && $user->access('管理下属机构仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('管理负责仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('查看负责仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}

	static function vidcam_ACL($e, $user, $perm, $vidcam, $params) {
		switch ($perm) {
			case '查看关联仪器':
				if ($vidcam->id && Vidmon::user_is_vidcam_incharge($user, $vidcam)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '查看历史记录':
			case '查看':
			case '列表':
				if ($user->access('管理所有仪器的视频监控')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				$equipments = Q("{$vidcam} equipment.camera");
				foreach ($equipments as $equipment) {
					if ($user->group->id && $user->group->is_itself_or_ancestor_of($equipment->group) && $user->access('管理下属机构仪器的视频监控')) {
						$e->return_value = TRUE;
						return FALSE;
					}
					if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('管理负责仪器的视频监控')) {
						$e->return_value = TRUE;
						return FALSE;
					}
					if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('查看负责仪器的视频监控')) {
						$e->return_value = TRUE;
						return FALSE;
					}
				}
				break;
		}
	}
}
