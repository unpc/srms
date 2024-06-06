<?php

class Login_Controller extends Layout_Controller {

	function index(){

		if (Auth::logged_in()) {
			URI::redirect('/');
		}

		$form = Form::filter(Input::form());
		if ($form['submit']) {
			if (Config::get('safe.mode', FALSE) && H($form['verify_token']) != md5($_SESSION['verify_token'])) {
				unset($_SESSION['verify_token']);
				URI::redirect('error/401');
			}

			try {
				$form
					->validate('token', 'not_empty', I18N::T('people', '登录帐号不能为空！'))
					->validate('password', 'length(1,)', I18N::T('people', '密码不能为空！'));
				Event::trigger('login.form.submit', $form);
				if (!$form->no_error) {
					throw new Error_Exception;
				}

				$token = trim($form['token']);
				$backend = trim(H($form['token_backend']));
				$token = Auth::normalize($token, $backend);

				$user = O('user', ['token'=>$token]);

				Event::trigger('before.auth.verify', $user);
				$auth = new Auth($token);
				if (!$auth->verify($form['password'])) {
					if ($user->id) {
						Log::add(strtr('[application] %user_name[%user_id]登录失败', [
									'%user_name' => $user->name,
									'%user_id' => $user->id,
						]), 'logon');
					}
					else {
						Log::add(strtr('[application] %token登录失败', [
									'%token' => $token,
						]), 'logon');
					}
					Event::trigger('login.field.attempt', $user);
                    $form->set_error('password', I18N::T('people', '帐号和密码不匹配! 请您重新输入.'));
					throw new Error_Exception;
				}
				Event::trigger('login.success.attempt', $user);

				Event::trigger('login.extra_validate', $user);

				// 处理gapper用户登录的逻辑
				if (!$user->id && Module::is_installed('gapper')) {
					if($gapper_info = Gapper::get_user_by_identity($token)) {
						$token = $gapper_info['username'];
					}
					else {
						$local_user = O('gapper_fallback_user', ['token'=>$token]);
						if ($local_user->user->id) {
							$token = $local_user->user->token;
						}
					}
				}

				Auth::login($token);

				unset($_SESSION['CF_Token']);

				/*
				 * 临时修复为密码小于8位的话将自动需要修复密码
				 */

				if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
					if (!$auth->is_readonly() && !$user->must_change_password ) {
						$user->must_change_password = TRUE;
						$user->save();
					}
				}

				if (!$user->id) {
					URI::redirect('signup');
                }
                
                if (!$_SESSION['system.locale'] && $form['submit'] == 'English') {
                    $user->locale = 'en_US';
                    $user->save();
                    I18N::shutdown();
                    $_SESSION['system.locale'] = $user->locale;
                    Config::set('system.locale', $user->locale);
                    I18N::setup();
                } elseif (!$_SESSION['system.locale']) {
                    $user->locale = 'zh_CN';
                    $user->save();
                    I18N::shutdown();
                    $_SESSION['system.locale'] = $user->locale;
                    Config::set('system.locale', $user->locale);
                    I18N::setup();
                }

				if ($form['persist']) {
					Lab::remember_login($user);
				}

				Log::add(strtr('[application] %user_name[%user_id]登录系统成功', [
							'%user_name' => $user->name,
							'%user_id' => $user->id,
				]), 'logon');

				Log::add(strtr('[application] %user_name[%user_id]登录系统成功', [
							'%user_name' => $user->name,
							'%user_id' => $user->id,
				]), 'journal');

				Event::trigger('login.save_locale', $user, $form);

				if(Module::is_installed('nfs_windows')) {
					$check_user_token = Event::trigger('check_user_token', $user);
					if(!$check_user_token) return;
				}

				Event::trigger('impression.eject.view', $user);

				//用户自助登录后，清空LOGOUT_REFERER
				unset($_SESSION['#LOGOUT_REFERER']);

				if (isset($_COOKIE['#LOGIN_REFERER']) || isset($_SESSION['#LOGIN_REFERER'])) {
					$http_referer = $_COOKIE['#LOGIN_REFERER'] ?: ($_SESSION['#LOGIN_REFERER'] ?: null);
					unset($_COOKIE['#LOGIN_REFERER']);
					unset($_SESSION['#LOGIN_REFERER']);
					if ($http_referer) {
						URI::redirect($http_referer);
					}
				}
				elseif ($user->must_change_password == TRUE) {
					URI::redirect('!people/index/password');
				}
				URI::redirect('/');
			}
			catch (Error_Exception $e) {
				header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
				header("Status: 401 Unauthorized");
			}
		}

		$this->layout->body = Event::trigger('login.view') ? : V('login', [
			'token' => $token, 
			'default_backend' => trim(H($form['default_backend'])) ? : ""
		]);
		$this->layout->body->form = $form;

	}

}
