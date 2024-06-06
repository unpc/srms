<?php
class Announce_Controller extends Layout_Controller{
    function index(){
        URI::redirect('error/404');
    }
}
class Announce_AJAX_Controller extends AJAX_Controller {

	function index_view_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['a_id'];
		$announce = O('eq_announce', $id);
		if ($announce->id) {
			JS::dialog(V('equipments:announce/view', ['announce'=>$announce]), ['width'=>600]);
		}
	}

	function index_view_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		
		$id = $form['a_id'];
		$announce = O('eq_announce', $id);
		$equipment = $announce->equipment;
		if ($announce->id) {
			if ($form['has_read'] == 'on') {
				if (!$me->connected_with($announce, 'read')) {
					$me->connect($announce, 'read');
                    Event::trigger('user.eq_announce.connect',$me,$announce);
					Log::add(strtr('[equipments] %user_name[%user_id]阅读了%equipment_name[%equipment_id]仪器上公告%announce_title[%announce_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%announce_title'=> $announce->title, '%announce_id'=> $announce->id]), 'journal');

					JS::refresh();
				}
				else {
					JS::alert(I18N::T('equipments', '您已读过此公告!'));
				}
			}
			else {				/* 没有阅读直接提交 */
				if ($me->is_allowed_to('修改公告', $equipment)) {
					JS::close_dialog(); /* 管理员无需阅读公告，便可操作仪器 */
				}
				else {
					JS::alert(I18N::T('equipments', '您需阅读过仪器公告，方可使用仪器!'));
				}
			}
		}
    }

    function index_view_and_close_announce_submit() {
        $form = Input::form();
        $me = L('ME');

        $announce = O('eq_announce', $form['a_id']);
        if (!$announce->id) return FALSE;

        $me->connect($announce, 'read');
        Event::trigger('user.eq_announce.connect',$me,$announce);
        JS::refresh();
    }
	
	function index_edit_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['a_id'];
		$announce = O('eq_announce', $id);
		$equipment = $announce->equipment;
		
		if (L('ME')->is_allowed_to('修改公告', $equipment)) {
			JS::dialog(V('equipments:announce/edit', ['announce'=>$announce]), ['title' => I18N::T('equipments', '修改公告')]);
		}
	}

	function index_edit_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		
		$announce = O('eq_announce', $form['a_id']);

		if (!$announce->id) {
			JS::alert(I18N::T('equipments', '更新失败!'));
		}
		
		$equipment = $announce->equipment;
		if ($form['submit']) {
			$form->validate('title', 'not_empty', I18N::T('equipments', '请填写公告标题!'))
				->validate('content',  'not_empty', I18N::T('equipments', '请填写公告内容!'));
			if ($form->no_error) {
				$announce->title = $form['title'];
				$announce->content = $form['content'];
				$announce->author = $me;
				$announce->is_sticky = (int) ($form['stick']=='on');
                $announce->dtstart = Date::get_day_start($form['dtstart']);
                $announce->dtend = Date::get_day_end($form['dtend']);
				
				if ($announce->save()) {

					Log::add(strtr('[equipments] %user_name[%user_id]修改了%equipment_name[%equipment_id]仪器上公告%announce_title[%announce_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%announce_title'=> $announce->title, '%announce_id'=> $announce->id]), 'journal');

					if ($form['important_edit'] == 'on') {
						foreach(Q("$announce<read user") as $user) {
							$user->disconnect($announce, 'read');
                            Event::trigger('user.eq_announce.disconnect',$me,$announce);
						}
                    }
                    $me->connect($announce, 'read');
                    Event::trigger('user.eq_announce.connect',$me,$announce);
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '公告更新成功!')); /* bu work */
					JS::refresh();
				}
				else {
					JS::alert(I18N::T('equipments', '更新失败!'));
				}
			}
			else {
				JS::dialog(V('equipments:announce/edit', ['announce'=>$announce, 'form'=>$form]), ['title' => I18N::T('equipments', '修改公告')]);
			}
		}
	}

	function index_delete_announce_click() {
		if (!JS::confirm( I18N::T('equipments', '你确定要删除该公告吗?请谨慎操作!') )) {
			return;
		}
		$form = Form::filter(Input::form());
		$announce = O('eq_announce', $form['a_id']);
		if (!$announce->id) return;
		$equipment = $announce->equipment;
		if (!L('ME')->is_allowed_to('删除公告', $equipment)) return;
		$me = L('ME');
		if ($announce->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '公告删除成功!'));

            Log::add(strtr('[equipments] %user_name[%user_id]删除了%equipment_name[%equipment_id]仪器上公告%announce_title[%announce_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%announce_title'=> $announce->title, '%announce_id'=> $announce->id]), 'journal');

		}
		JS::refresh();
	}
	
	function index_add_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['e_id'];
		$equipment = O('equipment', $id);
		if (L('ME')->is_allowed_to('添加公告', $equipment)) {
			JS::dialog(V('equipments:announce/add', ['eid'=>$equipment->id]), ['title' => I18N::T('equipments', '添加公告')]);
		}
	}

	function index_add_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		$equipment = O('equipment', $form['eid']);
		
		if ($form['submit']) {
			$form->validate('title', 'not_empty', I18N::T('equipments', '请填写公告标题!'))
				->validate('content',  'not_empty', I18N::T('equipments', '请填写公告内容!'));
			if ($form->no_error) {
				$announce = O('eq_announce');
				$announce->title = $form['title'];
				$announce->content = $form['content'];
				$announce->author = $me;
				$announce->equipment = $equipment;
				$announce->is_sticky = (int) ($form['stick']=='on');
                $announce->dtstart = Date::get_day_start($form['dtstart']);
                $announce->dtend = Date::get_day_end($form['dtend']);

				if ($announce->save()) {
					Log::add(strtr('[equipments] %user_name[%user_id]在%equipment_name[%equipment_id]仪器上添加了公告%announce_title[%announce_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%announce_title'=> $announce->title, '%announce_id'=> $announce->id]), 'journal');

					$me->connect($announce, 'read');
                    Event::trigger('user.eq_announce.connect',$me,$announce);

					Log::add(strtr('[equipments] %user_name[%user_id]阅读了%equipment_name[%equipment_id]仪器上公告%announce_title[%announce_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%announce_title'=> $announce->title, '%announce_id'=> $announce->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '公告添加成功!')); /* bu work */
					JS::refresh();
				}
				else {
					JS::alert(I18N::T('equipments', '添加失败!'));
				}
			}
			else {
				JS::dialog(V('equipments:announce/add', ['eid'=>$equipment->id, 'form'=>$form]), ['title' => I18N::T('equipments', '添加公告')]);
			}
		}
	}
}
