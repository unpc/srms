<?php

class EQ_Door_Meeting {

    static function setup_meeting() {
        Event::bind('meeting.edit.tab', 'EQ_Door_Meeting::_edit_meeting_tab', 0, 'eq_door');
        Event::bind('meeting.edit.content', 'EQ_Door_Meeting::_edit_meeting_content', 0, 'eq_door');
    }

    static function _edit_meeting_tab($e, $tabs) {
        $meeting = $tabs->meeting;
        if (L('ME')->is_allowed_to('关联门禁', $meeting)) {
            $tabs->add_tab('eq_door', [
                'title' => I18N::T('eq_door', '门禁设置'),
                'url' => $meeting->url('eq_door', NULL, NULL, 'edit'),
                'weight' => 25
            ]);
        }
    }

	static function _edit_meeting_content($e, $tabs) {
		$meeting = $tabs->meeting;
        $form = Form::filter(Input::form());

		if ($form['submit']) {
            if ((int)$form['ahead_time'] < 0
            || !is_numeric($form['ahead_time'])
            || (int)$form['ahead_time'] != $form['ahead_time']) {
                $form->set_error('ahead_time', I18N::T('eq_door', '请填写非负整数数字!'));
            }

			if ($form->no_error) {
				$meeting->ahead_time = $form['ahead_time'];
				$doors = $form['special_doors'];
				EQ_Door::eq_door_connect($doors, $meeting, 'door');
                if ($meeting->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_door', '门禁设置更新成功!'));
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_door', '门禁设置更新失败!'));
                }
			}
        }

		$content = V('eq_door:door/edit.meeting', ['meeting' => $meeting, 'form' => $form]);
		$tabs->content = $content;
	}

    static function operate_eq_door_is_allowed($e, $user, $perm, $meeting, $params) {
		switch ($perm) {
			case '关联门禁':
                if ($user->access('添加/修改所有会议室')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                if ($user->access('修改负责会议室信息') && ME_Reserv::user_is_meeting_incharge($user, $meeting)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
				break;
		}
    }

}
