<?php

class Recovery_Controller extends Layout_Controller {
	
	function index() {
		$me = L('ME');
		if ( $me->id ) {
			URI::redirect($me->home_url());
		}
		$form = Form::filter(Input::form());
		
		/*清除过早的recovery*/
		$recoverys = Q("recovery[overdue][overdue<".(time() - Config::get('recovery.overdue'))."]");
		foreach ($recoverys as $res) {
			$res->delete();
		}
		
		if ($form['submit']) {
			if (H($form['recovery_token']) != md5($_SESSION['recovery_token'])) {
				URI::redirect('error/401');
			}
			if ($form['token']) {
				$backend = $form['token_backend'];
				$backends = Config::get('auth.backends');
				if (!$backends[$backend] || $backends[$backend]['readonly']) {
					$form->set_error('', I18N::T('people', '该验证后台不支持找回密码!'));
				}
				$user = O('user', ['token'=>Auth::normalize($form['token'], $backend)]);
				//如果输入了email，并且帐号的email和输入的email不同，则显示错误
				if ($user->id && $form['email'] && ($user->email != $form['email'])) {
					$form->set_error(NULL, I18N::T('people', '您输入的帐号和邮箱地址指向不同的帐号, 请重新输入, 或只输入其中一个!'));
				}
			}
			else {
				$user = O('user', ['email'=>$form['email']]);
			}
			
			if (!$user->id) {
				$form->set_error(NULL,  I18N::T('people', '您输入的帐号 / Email有误, 请重新输入!'));
			}
			else {
				$count = count(Q("recovery[user={$user}]"));
				if ($count >= Config::get('recovery.reset_request_limit')) {
					Lab::message(LAB::MESSAGE_ERROR, I18N::T('people', '您执行了过多次密码重置操作，请您稍后再试。'));
					URI::redirect('/');
				}
			}
			Event::trigger('recovery.form.submit', $form);
			
			if ($form->no_error) {
				$key = md5($user->email.uniqid().mt_rand());
				$recovery = O('recovery');
				$recovery->user = $user;
				$recovery->key = $key;
				$recovery->overdue = Config::get('recovery.overdue') + time();
				if ($recovery->save()) {
					Log::add(strtr('[application] %user_name[%user_id]帐号申请重置密码', [
								'%user_name' => $user->name,
								'%user_id' => $user->id,
					]), 'journal');
					/*
						该处Email功能暂时不同，email指定到postfix上，仅仅是postfix上不work，待之后解决。
					*/
					$mail = new Email();
					
					$mail->to($user->email);
					$mail->subject(I18N::T('people', Config::get('recovery.default_email_title'), ["%name"=>$user->name, '%system'=>Config::get('system.email_name')]));
					$mail->body(I18N::T('people', Config::get('recovery.default_email_body'), [
							'%name' => $user->name,
							'%url' => URI::url('recovery/reset_password', ['key'=>$key]),
							'%system' => Config::get('system.email_name'),
							'%system_url' => URI::url()
						]));
					Log::add(URI::url('recovery/reset_password', ['key'=>$key]), 'mail');
					if ($mail->send()) {
						Lab::message(LAB::MESSAGE_NORMAL, I18N::T('people', '请尽快到您的邮箱查看邮件，并通过邮件重设密码。'));
						URI::redirect();
					}
				}
				$recovery->delete();
				Lab::message(LAB::MESSAGE_ERROR, I18N::T('people', '未知原因找回失败！'));
			}
		}

		$token = $_SESSION['recovery_token'] = 'cf_recovery_'.uniqid();
		$this->layout->body = V('application:recovery/index', ['form'=>$form, 'token' => $token]);
	}	
	
	function reset_password() {
		$form = Form::filter(Input::form());
		$key = $form['key'];
	
		if (Auth::logged_in()) {
			$user = L('ME');
			Auth::logout();
			if ($user->id) {
                Log::add(strtr('[application] 为了重设密码，登出当前登录的用户：%user_name[%user_id]', [
                    '%user_name'=> $user->name,
                    '%user_id'=> $user->id
                ]), 'mail');
			}
			$redirect = URI::url(null, ['key'=>$key]);
			URI::redirect($redirect);
		}
		/*自动运行程序，用来清除已经过期的recovery*/
		$recoverys = Q("recovery[overdue][overdue<".time()."]");
		foreach ($recoverys as $res) {
			$res->delete();
		}
		
		$recovery = O("recovery", ['key'=>$key]);
		if (!$recovery->id) {
			URI::redirect('error/404');
		}
		
		if ($form['submit']) {
			$form
				->validate('new_pass', 'not_empty', I18N::T('people', '新密码不能为空！'))
				->validate('confirm_pass', 'not_empty', I18N::T('people', '确认新密码不能为空！'));
			

			if ($form['new_pass']) {
				if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['new_pass'])) {
					$form->set_error('new_pass', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
				}

				$form
					->validate('confirm_pass', 'compare(==new_pass)', I18N::T('people', '两次输入的密码不一致！'))
					->validate('new_pass', 'length(8, 24)', I18N::T('people', '输入的密码不能小于8位，最长24位！'));
			}
			if ($form->no_error) {
				$user = $recovery->user;
				try {
					$auth = new Auth($user->token);
					if ($auth->change_password($form['new_pass'])) {
						$recovery->delete();
						Log::add(strtr('[application] %user_name[%user_id]重置密码成功', [
									'%user_name' => $user->name,
									'%user_id' => $user->id,
						]), 'journal');
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户密码已更新！'));					//由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
						if (Module::is_installed('nfs_windows')) {
							$_SESSION['fs_usertoken']['password'] = $form['new_pass'];
							//为了兼容之前bug导致的没有初始化fs_usertoken的用户 这边模拟一个name
							//另外这里是不是应该统一模块抽离顺带走个hook?
							NFS_Windows::fs_usertoken_saved(new stdClass(), $user, ['name' => '--'], []);
						}
						URI::redirect('/');
					}
					else {
						throw new Exception(I18N::T('people', '用户密码更新失败！'));
					}
				}
				catch (Exception $e){
					$message = $e->getMessage();
					if ($message) Lab::message(Lab::MESSAGE_ERROR, $message);
				}
			}
		}
		
		$this->layout->body = V('application:recovery/reset_password', ['form'=>$form, 'key'=>$key]);
		
	}
}
class Recovery_AJAX_Controller extends AJAX_Controller
{

    function index_fogret_password_submit()
    {
        $me = L('ME');
        if ($me->id) {
            URI::redirect($me->home_url());
        }
        $form = Form::filter(Input::form());

        /*清除过早的recovery*/
        $recoverys = Q("recovery[overdue][overdue<" . (time() - Config::get('recovery.overdue')) . "]");
        foreach ($recoverys as $res) {
            $res->delete();
        }
        if (H($form['recovery_token']) != md5($_SESSION['recovery_token'])) {
            URI::redirect('error/401');
        }
        if ($form['token']) {
            $backend = $form['token_backend'];
            $backends = Config::get('auth.backends');
            if (!$backends[$backend] || $backends[$backend]['readonly']) {
                $form->set_error('', I18N::T('people', '该验证后台不支持找回密码!'));
            }
            $user = O('user', ['token' => Auth::normalize($form['token'], $backend)]);
            //如果输入了email，并且帐号的email和输入的email不同，则显示错误
            if ($user->id && $form['email'] && ($user->email != $form['email'])) {
                $form->set_error(NULL, I18N::T('people', '您输入的帐号和邮箱地址指向不同的帐号, 请重新输入, 或只输入其中一个!'));
            }
        } else {
            $user = O('user', ['email' => $form['email']]);
        }
        if (!$user->id) {
            $form->set_error(NULL, I18N::T('people', '您输入的帐号 / Email有误, 请重新输入!'));
        } else {
            $count = count(Q("recovery[user={$user}]"));
            if ($count >= Config::get('recovery.reset_request_limit')) {
                $form->set_error(NULL, '您执行了过多次密码重置操作，请您稍后再试。');
            }
        }
        Event::trigger('recovery.form.submit', $form);
        if ($form->no_error) {
            $key = md5($user->email . uniqid() . mt_rand());
            $recovery = O('recovery');
            $recovery->user = $user;
            $recovery->key = $key;
            $recovery->overdue = Config::get('recovery.overdue') + time();
            if ($recovery->save()) {
                Log::add(strtr('[application] %user_name[%user_id]帐号申请重置密码', [
                    '%user_name' => $user->name,
                    '%user_id' => $user->id,
                ]), 'journal');
                /*
                    该处Email功能暂时不同，email指定到postfix上，仅仅是postfix上不work，待之后解决。
                */
                $mail = new Email();

                $mail->to($user->email);
                $mail->subject(I18N::T('people', Config::get('recovery.default_email_title'), ["%name" => $user->name, '%system' => Config::get('system.email_name')]));
                $mail->body(I18N::T('people', Config::get('recovery.default_email_body'), [
                    '%name' => $user->name,
                    '%url' => URI::url('recovery/reset_password', ['key' => $key]),
                    '%system' => Config::get('system.email_name'),
                    '%system_url' => URI::url()
                ]));
                Log::add(URI::url('recovery/reset_password', ['key' => $key]), 'mail');
                if ($mail->send()) {
                    JS::dialog(V('recovery/tips_success'));
                    return;
                }
            }
            $recovery->delete();
            $form->set_error(NULL, '未知原因找回失败！');
            JS::dialog(V('recovery/tips_failed', ['form' => $form]));
        } else {
            JS::dialog(V('recovery/tips_failed', ['form' => $form]));
        }
    }

    function index_close_submit()
    {
        JS::redirect('login');
    }

}
