<?php

class Index_Controller extends Layout_Controller
{

}

class Index_AJAX_Controller extends AJAX_Controller
{
	public function index_add_wait_join_user_click()
	{
		$form = Input::form();
		$equipment = O('equipment', (int)$form['id']);
		if (!L('ME')->is_allowed_to('管理预约等待', $equipment)) return;
		JS::dialog(V('eq_wait_join:equipment/add_wait_join_user', [
			'equipment' => $equipment
		]), [
			'title' => H(I18N::T('eq_wait_join', '添加预约等待用户'))
		]);
	}

	public function index_add_wait_join_user_submit()
	{
		$form = Form::filter(Input::form());
		$equipment = O('equipment', (int)$form['id']);
		if (!L('ME')->is_allowed_to('管理预约等待', $equipment)) return;
		$form
			->validate('sample', 'compare(>0)', I18N::T('eq_wait_join', '请输入样品数!'))
			->validate('time', 'compare(>0)', I18N::T('eq_wait_join', '请输入预计时长!'));
		if ((int)$form['sample'] != $form['sample']) {
			$form->set_error('sample', I18N::T('eq_wait_join', '样品数请填写正整数!'));
		}
		if ((int)$form['time'] != $form['time']) {
			$form->set_error('time', I18N::T('eq_wait_join', '预计时长请填写正整数!'));
		}

		$user = O('user', (int)$form['user_id']);
		if (!$user->id) {
			$form->set_error('user_id', I18N::T('eq_wait_join', '请选择用户!'));
		}
		if ($form->no_error) {
			$waiter = O('eq_wait_join');
			$waiter->user = $user;
			$waiter->equipment = $equipment;
			$waiter->ctime = Date::time();
			$waiter->sample = H($form['sample']);
			$waiter->description = H($form['description']);
			$waiter->time = H($form['time']);
			$waiter->time_format = H($form['time_format']);
			$waiter->save();
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_wait_join', '添加预约等待信息成功!'));
			JS::refresh();
		}
		else {
			JS::dialog(V('eq_wait_join:equipment/add_wait_join_user', [
				'equipment' => $equipment,
				'form' => $form
			]), [
				'title' => H(I18N::T('eq_wait_join', '添加预约等待用户'))
			]);
		}
	}

	public function index_mark_waiter_click()
	{
		$form = Input::form();
		$waiter = O('eq_wait_join', (int)$form['id']);
		if (!L('ME')->is_allowed_to('管理预约等待', $waiter->equipment)) return false;
		if (!$waiter->id || $waiter->status != Eq_Wait_Join_Model::NO_USE) return false;
		if (JS::confirm(I18N::T('eq_wait_join', '您确认标记该预约等待用户信息已完成?'))) {
			$waiter->status = Eq_Wait_Join_Model::COMPLETE;
			$waiter->save();
			JS::refresh();
		}
	}

	public function index_delete_waiter_click()
	{
		$form = Input::form();
		$waiter = O('eq_wait_join', (int)$form['id']);
		if (!L('ME')->is_allowed_to('管理预约等待', $waiter->equipment)) return false;
		if (!$waiter->id || $waiter->status != Eq_Wait_Join_Model::NO_USE) return false;
		if (JS::confirm(I18N::T('eq_wait_join', '您确定删除该预约等待用户信息?'))) {
			$waiter->delete();
			JS::refresh();
		}
	}

	public function index_reserv_fail_wait_join_user_click()
	{
		$form = Input::form();
		$equipment = O('equipment', (int)$form['eqId']);
		$user = L('ME');
		JS::dialog(V('eq_wait_join:equipment/reserv_fail_wait_join_user', [
			'equipment' => $equipment,
			'user' => $user,
			'form' => $form
		]), [
			'title' => H(I18N::T('eq_wait_join', '申请预约等待'))
		]);
	}

	public function index_reserv_fail_wait_join_user_submit()
	{
		$form = Form::filter(Input::form());
		$equipment = O('equipment', (int)$form['id']);
		$user = O('user', (int)$form['user_id']);
		$form
			->validate('sample', 'compare(>0)', I18N::T('eq_wait_join', '请输入样品数!'))
			->validate('time', 'compare(>0)', I18N::T('eq_wait_join', '请输入预计时间!'));

		if ($form->no_error) {
			$waiter = O('eq_wait_join');
			$waiter->user = $user;
			$waiter->equipment = $equipment;
			$waiter->ctime = Date::time();
			$waiter->sample = H($form['sample']);
			$waiter->description = H($form['description']);
			$waiter->time = H($form['time']);
			$waiter->time_format = H($form['time_format']);
			$waiter->save();
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_wait_join', '申请预约等待信息成功!'));
			JS::redirect($equipment->url('wait_join'));
		}
		else {
			JS::dialog(V('eq_wait_join:equipment/reserv_fail_wait_join_user', [
				'equipment' => $equipment,
				'user' => $user,
				'form' => $form
			]), [
				'title' => H(I18N::T('eq_wait_join', '申请预约等待'))
			]);
		}
	}
}
