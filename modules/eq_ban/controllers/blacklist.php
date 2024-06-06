<?php


class Blacklist_AJAX_Controller extends AJAX_Controller {

	function index_add_banned_user_click() {
		$form = Form::filter(Input::form());
		$id = $form['e_id'];
		$equipment = O('equipment', $id);
		if (L('ME')->is_allowed_to('添加仪器黑名单', $equipment)) {
			JS::dialog(V('eq_ban:equipment/add_banned', ['eid'=>$equipment->id]));
		}
	}

    private static function contact_info() {
    	$objects = func_get_args();
		$ci = [];
		foreach ($objects as $object) {
			if (!$ci['phone'] && $object->phone) $ci['phone'] = 'T: '.$object->phone;
			if (!$ci['email'] && $object->email) $ci['email'] = 'M: '.$object->email;
			if (count($ci) == 2) break;
		}
		return implode(', ', $ci);
    }

	function index_add_banned_user_submit() {

		if (!JS::confirm( I18N::T('eq_ban', '你确定要添加封禁吗?请谨慎操作!') )) {
			return;
		}

		$form = Form::filter(Input::form());
		$me = L('ME');
		$equipment = O('equipment', $form['eid']);
		if (!$me->is_allowed_to('添加仪器黑名单', $equipment)) {
			JS::alert(I18N::T('eq_ban', '封禁失败!'));
		}

		if ($form['submit']) {
			$type = $form['type'];

			/* validation */
			if ($type == 'user') {
				$form->validate('user_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁用户!'));

			}
			else if ($type == 'lab') {
				$form->validate('lab_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁实验室!'));

			}
			else {
				return;
			}
			$form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
				->validate('atime',  'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));
			
			if ($form->no_error) {
				if ($equipment->id) {
					$equipment_name = "{$equipment->name}[{$equipment->id}]";
				}
				else {
					$equipment_name = "所有";
				}
				if ($type == 'user') {
					$user = O('user', $form['user_id']);
					$users = [$user];
					$object_name = "用户{$user->name}";
					$object_id = $user->id;
				}
				else {
					$lab = O('lab', $form['lab_id']);
					if ($lab->id) {
						$users = Q("user[lab={$lab}]");
					}
					$object_name = "课题组{$lab->name}";
					$object_id = $lab->id;
				}

				foreach ($users as $user) {
					if ($user->id) {
						$eq_banned = O('eq_banned');
						$eq_banned->user = $user;
						$eq_banned->reason = $form['reason'];
						$eq_banned->equipment = $equipment;
						$eq_banned->atime = $form['atime'];

						$eq_banned->save();
						
						//如果用户是被所有仪器封禁的话， 则按照仪器加黑默认的消息模板方式来发送消息
						if (!$equipment->id) {
							Notification::send('eq_ban.eq_banned', $user, [
								'%user'=>Markup::encode_Q($user),
								'%reason'=>$form['reason'],
							]);
						}
						//如果用户是被单个仪器封禁的话， 则按照该仪器的加黑消息模板方式来发送消息
						else {
							Notification::send('eq_ban.eq_banned.item|'.$equipment->id, $user, [
								'%user'=>Markup::encode_Q($user),
								'%reason'=>$form['reason'],
								'%equipment'=>Markup::encode_Q($equipment),
								'%incharge' => Markup::encode_Q($me),
								'%contact_info'=> self::contact_info($equipment, $me),
							]);
						}		
					}
				}
				Log::add(strtr('[eq_ban] %operator_name[%operator_id]添加了%equipment_name的封禁用户%user_name[%user_id]: %reason', [
					'%operator_name' => $me->name,
					'%operator_id' => $me->id,
					'%equipment_name' => $equipment_name,
					'%object_name' => $object_name,
					'%object_id' => $object_id,
					'%reason' => $eq_banned->reason,
					]), 'journal');
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '添加封禁成功!'));
				JS::refresh();
			}
			//如果form表单中提交验证失败，则将信息返回提示给用户
			else {
				JS::dialog(V('eq_ban:equipment/add_banned', ['eid'=>$equipment->id, 'form'=>$form]));
			}
		}
	}

	function index_delete_banned_click() {
		if (!JS::confirm( I18N::T('eq_ban', '你确定要解除封禁吗?请谨慎操作!') )) {
			return;
		}
		$form = Form::filter(Input::form());
		$banned_item = O('eq_banned', $form['banned_id']);
		if (!$banned_item->id) return;
		if (!L('ME')->is_allowed_to('删除', $banned_item)) return;
		
		$user = $banned_item->user;
		if ( $user->id && isset($user->reserv_late_times) && $user->reserv_late_times>0 ) {
			$user->reserv_late_times = 0;
			$user->save();
		}

		$banned_item->delete();
		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '解除封禁成功！'));
		JS::refresh();
	}

	function index_edit_banned_click() {
		$form = Input::form();
		$banned = O('eq_banned', $form['banned_id']);
		if (!$banned->id) return;
		if (!L('ME')->is_allowed_to('修改', $banned))  return;
		JS::dialog(V('eq_ban:equipment/edit_banned', ['banned'=>$banned]));
	}

	function index_edit_banned_submit() {
		$form = Form::filter(Input::form());
		$banned = O('eq_banned', $form['banned_id']);
		if (!$banned->id) return;
		if (!L('ME')->is_allowed_to('修改', $banned))  return;
		if ($form['submit']) {
			// $form->validate('user', 'not_empty', I18N::T('eq_ban', '请选择封禁用户！'));
			$form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因！'));
			if ($form->no_error) {
				/* $user = O('user', $form['user']); */
				/* if ($user->id) { */
				/* 	$banned->user = $user; */
				$banned->reason = $form['reason'];
				$banned->atime = $form['atime'];
				if ($banned->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '修改封禁成功！'));
					JS::refresh();
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_ban', '修改封禁失败！'));
					JS::alert(I18N::T('eq_ban', '更新失败!'));
				}
					/*}*/
			}
			else {
				JS::dialog(V('eq_ban:equipment/edit_banned', ['banned'=>$banned, 'form'=>$form]));
			}
		}
	}
}
