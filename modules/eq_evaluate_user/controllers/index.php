<?php

class Index_AJAX_Controller extends AJAX_Controller {

    function index_evaluate_user_click() {
        $form = Form::filter(Input::form());
        $record = O('eq_record', $form['id']);
        JS::dialog(V('eq_evaluate_user:edit.eq_evaluate_user', ['record' => $record]));
    }

    function index_evaluate_user_submit() {
        $form = Form::filter(Input::form());

		if (!$form['record_id']) return;

		$record = O('eq_record', $form['record_id']);
		if (!$record->id) return;

		$me = L('ME');

        if (!$me->is_allowed_to('使用者确认', $record)) return;

        $baseline = Config::get('eq_evaluate_user')['rate.baseline'];

		$form['status_feedback'] = trim($form['status_feedback']);

		$form->validate('status', 'not_empty', I18N::T('eq_evaluate_user', '请确认仪器状态!'));
		if ($form['status'] == EQ_Evaluate_User_Model::FEEDBACK_PROBLEM) {
			$form->validate('status_feedback', 'not_empty', I18N::T('eq_evaluate_user', '请认真填写确认信息!'));
		}

        if (Config::get('eq_evaluate_user')['attitude.require']) {
            if (!$form['attitude']) {
                $form->set_error('attitude', I18N::T('eq_evaluate_user', '请评价用户使用态度!'));
            }
        }

        if ($form['attitude'] && $form['attitude'] <= $baseline) {
            $form->validate('attitude_feedback', 'not_empty', I18N::T('eq_evaluate_user', '请认真填写评价内容!'));
        }

        if (mb_strlen($form['attitude_feedback']) > 240) {
            $form->set_error('attitude_feedback', '评价信息填写有误, 长度不得大于240!');
        }

        if (Config::get('eq_evaluate_user')['proficiency.require']) {
            if (!$form['proficiency']) {
                $form->set_error('proficiency', I18N::T('eq_evaluate_user', '请评价熟练度!'));
            }
        }

        if ($form['proficiency'] && $form['proficiency'] <= $baseline) {
            $form->validate('proficiency_feedback', 'not_empty', I18N::T('eq_evaluate_user', '请认真填写评价内容!'));
        }

        if (mb_strlen($form['proficiency_feedback']) > 240) {
            $form->set_error('proficiency_feedback', '评价信息填写有误, 长度不得大于240!');
        }

        if (Config::get('eq_evaluate_user')['cleanliness.require']) {
            if (!$form['cleanliness']) {
                $form->set_error('cleanliness', I18N::T('eq_evaluate_user', '请评价试验台清洁度!'));
            }
        }

        if ($form['cleanliness'] && $form['cleanliness'] <= $baseline) {
            $form->validate('cleanliness_feedback', 'not_empty', I18N::T('eq_evaluate_user', '请认真填写评价内容!'));
        }

        if (mb_strlen($form['cleanliness_feedback']) > 240) {
            $form->set_error('cleanliness_feedback', '评价信息填写有误, 长度不得大于240!');
        }

		if ($form->no_error) {
            if ($form['status'] == EQ_Evaluate_User_Model::FEEDBACK_PROBLEM && $record->equipment->status != EQ_Status_Model::OUT_OF_SERVICE) {
                if (!JS::confirm( I18N::T('eq_evaluate_user', '你确定要将仪器状态设置为暂时故障吗?请谨慎操作!') )) {
                    return;
                }
                $eq_status = O('eq_status');
                $eq_status->dtstart = time();
                $eq_status->status = EQ_Status_Model::OUT_OF_SERVICE;
                $eq_status->equipment = $record->equipment;
                $eq_status->description = T(H($form['status_feedback']));
                $eq_status->save();
                $record->equipment->status = EQ_Status_Model::OUT_OF_SERVICE;
                $record->equipment->save();
            }
            if ($record->evaluate_user->id) {
                $eq_evaluate_user = O('eq_evaluate_user', $record->evaluate_user->id);
            } else {
                $eq_evaluate_user = O('eq_evaluate_user');
            }
            $eq_evaluate_user->equipment = $record->equipment;
            $eq_evaluate_user->user = $record->user;
            $eq_evaluate_user->status = $form['status'];
            $eq_evaluate_user->status_feedback = $form['status_feedback'];
            $eq_evaluate_user->attitude = $form['attitude'];
            $eq_evaluate_user->attitude_feedback = $form['attitude_feedback'];
            $eq_evaluate_user->proficiency = $form['proficiency'];
            $eq_evaluate_user->proficiency_feedback = $form['proficiency_feedback'];
            $eq_evaluate_user->cleanliness = $form['cleanliness'];
            $eq_evaluate_user->cleanliness_feedback = $form['cleanliness_feedback'];
            if ($eq_evaluate_user->save()) {
                $record->evaluate_user = $eq_evaluate_user;
                $record->save();
            }

            JS::refresh();
		}
		else {
            JS::dialog(V('eq_evaluate_user:edit.eq_evaluate_user', ['record' => $record, 'form' => $form]));
		}
    }

    function index_view_evaluate_user_click() {
        $form = Form::filter(Input::form());
        $record = O('eq_record', $form['id']);
        JS::dialog(V('eq_evaluate_user:view.eq_evaluate_user', ['record' => $record]), ['title' => '查看评价']);
    }
}
