<?php

class Auth_Controller extends Base_Controller {

	function apply_user($id=0) {

		$meeting = O('meeting', $id);
		$user = L('ME');
		$auth = O('um_auth', ['meeting'=>$meeting, 'user'=>$user,]);
		if ($auth->id && $auth->status != UM_Auth_Model::STATUS_REFUSE) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '您已经申请使用该的会议室!'));
		}
		if ($auth->id && $auth->status == UM_Auth_Model::STATUS_REFUSE) {
			$auth->status = UM_Auth_Model::STATUS_APPLIED;
		}
		else {
			$auth->user = $user;
			$auth->meeting = $meeting;
			$auth->status = UM_Auth_Model::STATUS_APPLIED;
			$auth->type = $user->member_type;
		}
		if ($auth->save()) {
			$incharges = Q("{$meeting} user.incharge");
			foreach ($incharges as $incharge) {
				Notification::send('meeting.apply_user_auth', $incharge, [
					'%incharge' => Markup::encode_Q($incharge),
					'%user' => Markup::encode_Q($user),
					'%meeting' => $meeting->name,
					'%link' => $meeting->url('auth'),
				]);
			}
		}
			
		URI::redirect($meeting->url());
	}


	function reject_user($id=0) {
		//拒绝
		$auth = O('um_auth', $id);

		if (!$auth->id) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您的操作有误!'));
		}

		$meeting = $auth->meeting;
		$me = L('ME');
		$user = $auth->user;
		if ($auth->status == UM_Auth_Model::STATUS_APPROVED) {
			$auth->delete();
		}
		else {
			$auth->status = UM_Auth_Model::STATUS_REFUSE;
			$auth->save();
		}
				
		if ($auth->status == UM_Auth_Model::STATUS_REFUSE) {
			Notification::send('meeting.apply_rejected', $user, [
					'%incharge' => Markup::encode_Q($me),
					'%user' => Markup::encode_Q($user),
					'%meeting' => $meeting->name,
			]);
		
            Log::add(strtr('[meeting] %user_name[%user_id]拒绝%meeting_name[%meeting_id]会议室的个人培训申请[%auth_id]', [
                '%user_name'=> $me->name,
                '%user_id'=> $me->id,
                '%meeting_name'=> $meeting->name,
                '%meeting_id'=> $meeting->id,
                '%auth_id'=> $auth->id
            ]), 'journal');
		
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '已拒绝%name的会议室使用申请', ['%name'=>H($user->name)]));
			URI::redirect($meeting->url('auth.applied'));
		}
		else {
			Notification::send('meeting.delete_user_auth', $user, [
					'%user' => Markup::encode_Q($user),
					'%meeting' => $meeting->name,
			]);

            Log::add(strtr('[meeting] %user_name[%user_id]删除%meeting_name[%meeting_id]会议室的个人授权记录[%auth_id]', [
                '%user_name'=> $me->name,
                '%user_id'=> $me->id,
                '%meeting_name'=> $meeting->name,
                '%meeting_id'=> $meeting->id,
                '%auth_id'=> $auth->id
            ]), 'journal');
			
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '已删除%name的会议室使用资格', ['%name'=>H($user->name)]));
			URI::redirect($meeting->url('auth.approved'));
		}
	}
}

class Auth_AJAX_Controller extends AJAX_Controller {

	function index_add_approved_tag_click () {
		$form = Input::form();
		$view = V('auths/add_tag', ['meeting_id' => $form['meeting_id']]);
		JS::dialog($view);
	}

	function index_add_approved_tag_submit () {
		$me = L('ME');
		$form = Input::form();
		$meeting = O('meeting', $form['meeting_id']);
		
		if (!$meeting->id) URI::redirect('error/404');
		if (!$me->is_allowed_to('修改', $meeting) && !$me->is_allowed_to('管理授权', $meeting)) URI::redirect('error/401');
		
		$tags = @json_decode($form['approved_tag'], TRUE);
		if (!count($tags)) {
			JS::alert(I18N::T('meeting', '请正确选择用户标签！'));
			return;
		}

		if ($form['atime']) {
			$today = getdate(time());
			$now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
			$dl = getdate($form['deadline']);
			$deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
			if ($now - $deadline > 0) {
				JS::alert(I18N::T('meeting', '过期时间不能小于当前时间!'));
				return;
			}
		}

		$root = $meeting->get_root();
		if ($tags) foreach ($tags as $id => $name) {
			// 限制该会议室设定的标签必须是会议室root下真实存在的标签
			$t = O('tag', ['root' => $root, 'id' => $id]);
			$tt = O('tag_meeting_user_tags', ['root'=> Tag_Model::root('meeting_user_tags'), 'id'=> $id]);
			if ($t->id || $tt->id) {
				$auth = O('um_auth', ['meeting' => $meeting, 'user'=>$user,]);
				$auth->tag = $t->id ? $t : $tt;
				$auth->meeting = $meeting;
				$auth->status = UM_Auth_Model::STATUS_APPROVED;
				// $auth->type = $user->member_type;
				$auth->atime = $form['atime'] ? $form['deadline']: '0';
				$auth->save();
				
				Log::add(strtr('[meeting] %user_name[%user_id]添加%meeting_name[%meeting_id]会议室的标签授权记录[%auth_id]', [
					'%user_name'=> $me->name,
					'%user_id'=> $me->id,
					'%meeting_name'=> $meeting->name,
					'%meeting_id'=> $meeting->id,
					'%auth_id'=> $auth->id
				]), 'journal');
			}
		}

		JS::refresh();
	}

	function index_add_approved_user_click () {
		$form = Input::form();

		JS::dialog(V('auths/add_member', ['meeting_id'=>$form['meeting_id']]));
	}

	function index_add_approved_user_submit () {
		$form = Input::form();
		$meeting = O('meeting', $form['meeting_id']);
		if (!$meeting->id) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '添加已通过授权用户出错'));
			JS::refresh();
			return;
		}

		$approved_users = json_decode($form['approved_users']);
		if (!count($approved_users)) {
			JS::alert(I18N::T('meeting', '请正确选择用户！'));
			return;
		}

		if ($form['atime']) {
			$today = getdate(time());
			$now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
			$dl = getdate($form['deadline']);
			$deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
			if ($now - $deadline > 0) {
				JS::alert(I18N::T('meeting', '过期时间不能小于当前时间!'));
				return;
			}
		}

		foreach ($approved_users as $id=>$name) {
			$user = O('user', $id);
			if (!$user->id)
				continue;
			$auth = O('um_auth', ['meeting'=>$meeting, 'user'=>$user,]);
			$auth->user = $user;
			$auth->meeting = $meeting;
			$auth->status = UM_Auth_Model::STATUS_APPROVED;
			$auth->type = $user->member_type;
			$auth->atime = $form['atime'] ? $form['deadline']: '0';
			$auth->save();

			$me = L('ME');
            Log::add(strtr('[meeting] %user_name[%user_id]添加%meeting_name[%meeting_id]会议室的个人授权记录[%auth_id]', [
                '%user_name'=> $me->name,
                '%user_id'=> $me->id,
                '%meeting_name'=> $meeting->name,
                '%meeting_id'=> $meeting->id,
                '%auth_id'=> $auth->id
            ]), 'journal');
		}

		JS::refresh();
	}
	
	function index_edit_approved_user_click() {
		$form = Input::form();
		$auth = O('um_auth', $form['tid']);

		if (!$auth->id) {
			JS::redirect(URI::url('error/404'));
			return;
		}
		
		JS::dialog(V('auths/approve_user', ['tid' => $auth->id, 'atime' => $auth->atime]));	
	}

	function index_edit_approve_user_submit () {
		//同意
		$form = Input::form();
		$auth = O('um_auth', $form['tid']);

		if (!$auth->id) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您的操作有误!'));
		}

		$meeting = $auth->meeting;
		$me = L('ME');

		if ($form['atime']) {
			$today = getdate(time());
			$now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
			$dl = getdate($form['deadline']);
			$deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
			if ($now - $deadline > 0) {
				JS::alert(I18N::T('meeting', '过期时间不能小于当前时间!'));
				return;
			}
		}

		$auth->status = UM_Auth_Model::STATUS_APPROVED;
		$auth->atime = $form['atime'] ? $form['deadline']: '0';
		$auth->save();

        Log::add(strtr('[meeting] %user_name[%user_id]修改%meeting_name[%meeting_id]会议室的标签授权记录[%auth_id]', [
            '%user_name'=> $me->name,
            '%user_id'=> $me->id,
            '%meeting_name'=> $meeting->name,
            '%meeting_id'=> $meeting->id,
            '%auth_id'=> $auth->id
        ]), 'journal');

		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '已修改%name的会议室使用申请', ['%name'=>H($user->name)]));

		JS::refresh();
	}

	function index_approve_user_click() {
		$form = Input::form();

		JS::dialog(V('auths/approve_user', ['tid'=>$form['tid']]));
	}

	function index_approve_user_submit() {
		//同意
		$form = Input::form();

		$auth = O('um_auth', $form['tid']);

		if (!$auth->id) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您的操作有误!'));
		}

		$meeting = $auth->meeting;
		$me = L('ME');

		if ($form['atime']) {
			$today = getdate(time());
			$now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
			$dl = getdate($form['deadline']);
			$deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
			if ($now - $deadline > 0) {
				JS::alert(I18N::T('meeting', '过期时间不能小于当前时间!'));
				return;
			}
		}
		
		$user = $auth->user;
		$auth->status = UM_Auth_Model::STATUS_APPROVED;
		$auth->atime = $form['atime'] ? $form['deadline']: '0';

		if ($auth->save()) {
			Notification::send('meeting.user_apply_approved', $user, [
				'%user' => Markup::encode_Q($user),
				'%meeting' => $meeting->name,
			]);
		}

        Log::add(strtr('[meeting] %user_name[%user_id]修改%meeting_name[%meeting_id]会议室的个人授权记录[%auth_id]', [
            '%user_name'=> $me->name,
            '%user_id'=> $me->id,
            '%meeting_name'=> $meeting->name,
            '%meeting_id'=> $meeting->id,
            '%auth_id'=> $auth->id
        ]), 'journal');

		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('meeting', '已修改%name的会议室使用申请', ['%name'=>H($user->name)]));

		JS::refresh();
	}
}
