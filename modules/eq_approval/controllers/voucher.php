<?php

class Voucher_Controller extends Controller {
	function index(){
		URI::redirect('error/401');
	}
}

class Voucher_AJAX_Controller extends AJAX_Controller {

	function index_get_equipment_contacts() {
		$e = O('equipment', Input::form('eid'));
		if (!$e->id) Output::$AJAX['contacts'] = '';
		Output::$AJAX['contacts'] = join(', ', $e->contacts()->to_assoc('id', 'name'));
	}

	function index_get_voucher_info() {
		$voucher = O('eq_voucher', Input::form('id'));
		if (!$voucher->id) Output::$AJAX['info'] = '';
		Output::$AJAX['info'] = [
			'samples' => $voucher->samples,
			'description' => H($voucher->description),
			'used_time' => $voucher->used_time,
			'auto_amount' => $voucher->auto_amount,
			'type' => EQ_Voucher_Model::$TYPES_STYLE[$voucher->type],
			'project' => $voucher->project->id
		];
	}

	function index_add_reserv_voucher_click() {
		$uid = Input::form('user_id');
		$me = L('ME');
		if ($uid != $me->id) return;

		JS::dialog(V('eq_approval:voucher/add', [
			'user' => $me
		]), [
			'title' => I18N::T('eq_approval', '添加预约凭证')
		]);
	}

	function index_add_reserv_voucher_submit() {
		$me = L('ME');
		$form = Form::filter(Input::form());

		$equipment = $form['equipment'];

		if (!$equipment || $equipment == '{}') {
			$form->set_error('equipment', I18N::T('eq_approval', '请填写预约仪器!'));
		}
		
		if (Config::get('eq_reserv.must_connect_lab_project')) {
			$form->validate('project', $form['project'] == 0, I18N::T('eq_approval', '请填写关联项目!'));
		}

		$auto_amount = $form['auto_amount'];

		if ( !$auto_amount ) {
			$form->set_error('auto_amount', I18N::T('eq_approval', '请填写预计价格!'));
		}
		if ( $auto_amount && !is_numeric($auto_amount) ) {
			$form->set_error('auto_amount', I18N::T('eq_approval', '预计价格填写有误!'));
		}


		switch ($form['voucher_type']) {
			case EQ_Voucher_Model::RESERV:
				$used_time = $form['used_time'];
				if ( !$used_time) {
					$form->set_error('used_time', I18N::T('eq_approval', '请填写预计使用时长!'));
				}
				if ( $used_time && !is_numeric($used_time) ) {
					$form->set_error('used_time', I18N::T('eq_approval', '预计使用时长填写有误!'));
				}
				break;
			case EQ_Voucher_Model::SAMPLE:
				$samples = $form['samples'];
				if ( !$samples ) {
					$form->set_error('samples', I18N::T('eq_approval', '请填写送样数!'));
				}
				if ( $samples && !is_numeric($samples)  ) {
					$form->set_error('samples', I18N::T('eq_approval', '送样数填写有误!'));
				}
				break;
			default:
				break;
		}

		if ($form->no_error) {
			$voucher = O('eq_voucher');
			$voucher->user = $me;
			$voucher->equipment = O('equipment', $equipment);
			$voucher->lab = Q("{$me} lab")->current();
			$voucher->project = O('lab_project', $form['project']);
			$voucher->auto_amount = $auto_amount;
			switch ($form['voucher_type']) {
				case EQ_Voucher_Model::RESERV:
					$voucher->used_time = $used_time;
					break;
				case EQ_Voucher_Model::SAMPLE:
					$voucher->samples = $samples;
					break;
				default:
					break;
			}
			$voucher->type = (int)$form['voucher_type'];
			$voucher->description = $form['description'];
			$voucher->save();

			JS::refresh();
		}
		else {
			JS::dialog(V('eq_approval:voucher/add', [
				'user' => $me,
				'form' => $form
			]), [
				'title' => I18N::T('eq_approval', '添加预约凭证')
			]);
		}
	}

	function index_voucher_delete_click() {
		$voucher = O('eq_voucher', Input::form('id'));
		$me = L('ME');
		$ret = JS::confirm(I18N::T('eq_approval', '您是否确认删除此条凭证?'));
		if (!$ret || !$me->is_allowed_to('删除', $voucher)) return;

		$voucher->delete();
		JS::refresh();
	}

	function index_voucher_hide_click() {
		$voucher = O('eq_voucher', Input::form('id'));
		$me = L('ME');

		$ret = JS::confirm(I18N::T('eq_approval', '您是否确认从列表中隐藏此条凭证?'));

		if (!$ret || !$me->is_allowed_to('删除', $voucher)) return;

		$voucher->hide();
		JS::refresh();
	}

	function index_voucher_edit_click() {
		$voucher = O('eq_voucher', Input::form('id'));
		if (!L('ME')->is_allowed_to('修改', $voucher)) return;
		JS::dialog(V('eq_approval:voucher/edit', [
			'voucher' => $voucher,
			'form' => Form::filter(Input::form())
		]), [
			'title' => I18N::T('eq_approval', '编辑预约凭证')
		]);
	}

	function index_voucher_edit_submit() {
		$me = L('ME');
		$form = Form::filter(Input::form());

		$voucher = O('eq_voucher', $form['id']);

		if (!$voucher->id || !$me->is_allowed_to('修改', $voucher)) return;

		$equipment = $form['equipment'];

		if (!$equipment || $equipment == '{}') {
			$form->set_error('equipment', I18N::T('eq_approval', '请填写预约仪器!'));
		}
		
		if (Config::get('eq_reserv.must_connect_lab_project')) {
			$form->validate('project', $form['project'] == 0, I18N::T('eq_approval', '请填写关联项目!'));
		}

		$auto_amount = $form['auto_amount'];

		if ( !$auto_amount ) {
			$form->set_error('auto_amount', I18N::T('eq_approval', '请填写预计价格!'));
		}
		if ( $auto_amount && !is_numeric($auto_amount) ) {
			$form->set_error('auto_amount', I18N::T('eq_approval', '预计价格填写有误!'));
		}


		switch ($form['voucher_type']) {
			case EQ_Voucher_Model::RESERV:
				$used_time = $form['used_time'];

				if ( !$used_time) {
					$form->set_error('used_time', I18N::T('eq_approval', '请填写预计使用时长!'));
				}
				if ( $used_time && !is_numeric($used_time) ) {
					$form->set_error('used_time', I18N::T('eq_approval', '预计使用时长填写有误!'));
				}
				break;
			case EQ_Voucher_Model::SAMPLE:
				$samples = $form['samples'];
				if ( !$samples ) {
					$form->set_error('samples', I18N::T('eq_approval', '请填写送样数!'));
				}
				if ( $samples && !is_numeric($samples)  ) {
					$form->set_error('samples', I18N::T('eq_approval', '送样数填写有误!'));
				}
				break;
			default:
				break;
		}

		if ($form->no_error) {
			$voucher->user = $me;
			$voucher->equipment = O('equipment', $equipment);
			$voucher->lab = $me->lab;
			$voucher->project = O('lab_project', $form['project']);
			$voucher->auto_amount = $auto_amount;
			switch ($form['voucher_type']) {
				case EQ_Voucher_Model::RESERV:
					$voucher->used_time = $used_time;
					break;
				case EQ_Voucher_Model::SAMPLE:
					$voucher->samples = $samples;
					break;
				default:
					break;
			}
			$voucher->type = (int)$form['voucher_type'];
			$voucher->description = $form['description'];
			$voucher->save();

			JS::refresh();
		}
		else {
			JS::dialog(V('eq_approval:voucher/edit', [
				'voucher' => $voucher,
				'form' => $form
			]), [
				'title' => I18N::T('eq_approval', '编辑预约凭证')
			]);
		}
	}

	function index_voucher_pass() {
		$voucher = O('eq_voucher', Input::form('id'));
		if (!$voucher->id || !L('ME')->is_allowed_to('审批', $voucher)) return;
		$voucher->approved();
		JS::refresh();
	}

	function index_voucher_reject() {
		$voucher = O('eq_voucher', Input::form('id'));
		if (!$voucher->id || !L('ME')->is_allowed_to('审批', $voucher)) return;
		$voucher->rejected();
		
		$equipment = $voucher->equipment;
		$owner = $voucher->user;

		$content = $voucher->type == EQ_Voucher_Model::RESERV ? I18N::T('eq_approval', "预计使用时长:\t%used_time分\n", ['%used_time' => (int)$voucher->used_time]) : I18N::T('eq_approval', "送样数:\t%samples个\n", ['%samples' => (int)$voucher->samples]);


		Notification::send('approval.reject_reserv_voucher', $owner, [
			'%user'=> Markup::encode_Q($owner),
	        '%equipment'=> Markup::encode_Q($equipment),
	        '%contacts'=> join(',', $equipment->contacts()->to_assoc('id', 'name')),
	        '%project'=> H($voucher->project->name),
	        '%type_content'=> $content,
	        '%description' => H($voucher->description),
	        '%ctime' => Date::format($voucher->ctime),
	        '%link' => $owner->url('approval')
		]);
		JS::refresh();
	}

	function index_voucher_batch_action() {
		$ids = Input::form('ids');
		JS::dialog(V('eq_approval:labs/choose_action', [
				'ids' => $ids
			]), [
				'width' => '200px'
			]);
	}

	function index_voucher_batch_submit() {
		$form = Input::form();

		$ids = (array)explode(', ', $form['ids']); 

		if ($form['submit'] == 'pass') {
			foreach ($ids as $id) {
				$voucher = O('eq_voucher', $id);
				if (!$voucher->id || $voucher->status != EQ_Voucher_Model::PENDDING) continue;
				$voucher->approved();
			}
		}
		elseif ($form['submit'] == 'reject') {
			foreach ($ids as $id) {
				$voucher = O('eq_voucher', $id);
				if (!$voucher->id || $voucher->status != EQ_Voucher_Model::PENDDING) continue;
				$voucher->rejected();

				$equipment = $voucher->equipment;
				$owner = $voucher->user;
				$content = $voucher->type == EQ_Voucher_Model::RESERV ? I18N::T('eq_approval', "预计使用时长:\t%used_time分\n", ['%used_time' => (int)$voucher->used_time]) : I18N::T('eq_approval', "送样数:\t%samples个\n", ['%samples' => (int)$voucher->samples]);

				Notification::send('approval.reject_reserv_voucher', $owner, [
					'%user'=> Markup::encode_Q($owner),
			        '%equipment'=> Markup::encode_Q($equipment),
			        '%contacts'=> join(',', $equipment->contacts()->to_assoc('id', 'name')),
			        '%project'=> H($voucher->project->name),
			        '%type_content'=> $content,
			        '%description'=> H($voucher->description),
			        '%ctime'=> Date::format($voucher->ctime),
			        '%link'=> $owner->url('approval')
				]);
			}
		}

		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_approval', '批处理成功!'));
		JS::refresh();
	}

	function index_preview_click() {
		$form = Input::form();
		$voucher = O('eq_voucher',$form['id']);

		if (!$voucher->id) return;

		Output::$AJAX['preview'] = (string)V('eq_approval:voucher/preview', ['voucher'=>$voucher]);
	}
}