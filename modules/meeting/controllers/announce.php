<?php
class Announce_AJAX_Controller extends AJAX_Controller {

	function index_view_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['a_id'];
		$announce = O('meeting_announce', $id);
		if ($announce->id) {
			JS::dialog(V('meeting:announce/view', ['announce'=>$announce]), ['width'=>600]);
		}
	}

	function index_view_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		$id = $form['a_id'];
		$announce = O('meeting_announce', $id);
		$meeting = $announce->meeting;
		if ($announce->id) {
			if ($form['has_read'] == 'on') {
				if (!$me->connected_with($announce, 'read')) {
					$me->connect($announce, 'read');

                    Log::add(strtr('[meeting] %user_name[%user_id]阅读了%meeting_name[%meeting_id]会议室上公告%announce_title[%announce_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%meeting_name'=> $meeting->name,
                        '%meeting_id'=> $meeting->id,
                        '%announce_title'=> $announce->title,
                        '%announce_id'=> $announce->id
                    ]), 'journal');

					JS::refresh();
				}
				else {
					JS::alert(I18N::T('meeting', '您已读过此公告!'));
				}
			}
			else {
				if ($me->is_allowed_to('修改公告', $meeting)) {
					JS::close_dialog();
				}
				else {
					JS::alert(I18N::T('meeting', '您需阅读过会议室公告，才可使用会议室!'));
				}
			}
		}
    }

    function index_view_and_close_announce_submit() {
        $form = Input::form();
        $me = L('ME');
        $announce = O('meeting_announce', $form['a_id']);
        if (!$announce->id) return FALSE;
        $me->connect($announce, 'read');
        JS::refresh();
    }

	function index_edit_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['a_id'];
		$announce = O('meeting_announce', $id);
		$meeting = $announce->meeting;

		if (L('ME')->is_allowed_to('修改公告', $meeting)) {
			JS::dialog(V('meeting:announce/edit', ['announce'=>$announce]), ['title' => I18N::T('meeting', '添加公告')]);
		}
	}

	function index_edit_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		$announce = O('meeting_announce', $form['a_id']);

		if (!$announce->id) {
			JS::alert(I18N::T('meeting', '更新失败!'));
		}

		$meeting = $announce->meeting;
		if ($form['submit']) {
			$form->validate('title', 'not_empty', I18N::T('meeting', '请填写公告标题!'))
				->validate('content',  'not_empty', I18N::T('meeting', '请填写公告内容!'));
			if ($form->no_error) {
				$announce->title = $form['title'];
				$announce->content = $form['content'];
				$announce->author = $me;
				$announce->is_sticky = ($form['stick']=='on');

				if ($announce->save()) {
                    Log::add(strtr('[meeting] %user_name[%user_id]修改了%meeting_name[%meeting_id]会议室上公告%announce_title[%announce_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%meeting_name'=> $meeting->name,
                        '%meeting_id'=> $meeting->id,
                        '%announce_title'=> $announce->title,
                        '%announce_id'=> $announce->id
                    ]), 'journal');

					if ($form['important_edit'] == 'on') {
						foreach(Q("$announce<read user") as $user) {
							$user->disconnect($announce, 'read');
						}
                    }
                    $me->connect($announce, 'read');
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '公告更新成功!'));
					JS::refresh();
				}
				else {
					JS::alert(I18N::T('meeting', '更新失败!'));
				}
			}
			else {
				JS::dialog(V('meeting:announce/edit', ['announce'=>$announce, 'form'=>$form]));
			}
		}
	}

	function index_delete_announce_click() {
		if (!JS::confirm( I18N::T('meeting', '你确定要删除该公告吗?请谨慎操作!') )) {
			return;
		}
		$form = Form::filter(Input::form());
		$announce = O('meeting_announce', $form['a_id']);
		if (!$announce->id) return;
		$meeting = $announce->meeting;
		if (!L('ME')->is_allowed_to('删除公告', $meeting)) return;
		$me = L('ME');
		if ($announce->delete()) {
            Log::add(strtr('[meeting] %user_name[%user_id]删除了%meeting_name[%meeting_id]会议室上公告%announce_title[%announce_id]', [
                '%user_name'=> $me->name,
                '%user_id'=> $me->id,
                '%meeting_name'=> $meeting->name,
                '%meeting_id'=> $meeting->id,
                '%announce_title'=> $announce->title,
                '%announce_id'=> $announce->id
            ]), 'journal');
		}
		JS::refresh();
	}

	function index_add_announce_click() {
		$form = Form::filter(Input::form());
		$id = $form['e_id'];
		$meeting = O('meeting', $id);
			JS::dialog(V('meeting:announce/add', ['eid'=>$meeting->id]), ['title' => I18N::T('meeting', '添加公告')]);
	}

	function index_add_announce_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		$meeting = O('meeting', $form['eid']);

		if ($form['submit']) {
			$form->validate('title', 'not_empty', I18N::T('meeting', '请填写公告标题!'))
				->validate('content',  'not_empty', I18N::T('meeting', '请填写公告内容!'));
			if ($form->no_error) {
				$announce = O('meeting_announce');
				$announce->title = $form['title'];
				$announce->content = $form['content'];
				$announce->author = $me;
				$announce->meeting = $meeting;
				$announce->is_sticky = intval($form['stick']=='on');

				if ($announce->save()) {
                    Log::add(strtr('[meeting] %user_name[%user_id]在%meeting_name[%meeting_id]会议室添加了公告%announce_title[%announce_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%meeting_name'=> $meeting->name,
                        '%meeting_id'=> $meeting->id,
                        '%announce_title'=> $announce->title,
                        '%announce_id'=> $announce->id
                    ]), 'journal');

					$me->connect($announce, 'read');

                    Log::add(strtr('[meeting] %user_name[%user_id]阅读了%meeting_name[%meeting_id]会议室上公告%announce_title[%announce_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%meeting_name'=> $meeting->name,
                        '%meeting_id'=> $meeting->id,
                        '%announce_title'=> $announce->title,
                        '%announce_id'=> $announce->id
                    ]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '公告添加成功!'));
					JS::refresh();
				}
				else {
					JS::alert(I18N::T('meeting', '添加失败!'));
				}
			}
			else {
				JS::dialog(V('meeting:announce/add', ['eid'=>$meeting->id, 'form'=>$form]));
			}
		}
	}
}
