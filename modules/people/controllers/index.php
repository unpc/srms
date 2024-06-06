<?php

class Index_Controller extends Layout_Controller {

	function index() {
		URI::redirect('!people/list');
	}

	function password(){
		
		$user = L('ME');
		if(!$user->id || !$user->must_change_password()){
			URI::redirect('error/404');
		}

		$form = Form::filter(Input::form());
		
	    /*
        NO.BUG#111（guoping.zhang@2010.11.12)
       	用户账户密码长度的限制（不小于6位，最长不能大于24位）
        */
		if (Input::form('submit')) {
			//由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
			if(Module::is_installed('nfs_windows')) {
				$_SESSION['fs_usertoken']['password'] = $form['new_pass'];
			}

			$form
				->validate('new_pass', 'length(8, 24)', I18N::T('people', '输入的密码不能小于8位，最长不能大于24位!'))
				->validate('confirm_pass', 'compare(==new_pass)', I18N::T('people', '您两次输入的密码不一致!'));

            $require_special = Config::get('labs.require_password_special');
            if ($require_special) {
                if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=.*[!@#.,$%*()_+^&])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['new_pass'])) {
                    $form->set_error('new_pass', I18N::T('people', '密码必须包含大写字母、小写字母、数字和特殊字符!'));
                }
            } else {
                if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['new_pass'])) {
                    $form->set_error('new_pass', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                }
            }


			if ($form->no_error) {
				$auth = new Auth($user->token);
				if ($auth->change_password($form['new_pass'])) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '您的登录密码已修改!'));
					if($user->must_change_password){
						$user->must_change_password = NULL;
						$user->save();
						
						if (isset($_SESSION['#LOGIN_REFERER'])) {
							$http_referer = $_SESSION['#LOGIN_REFERER'] ?: null;
							unset($_SESSION['#LOGIN_REFERER']);
							if ($http_referer) {
								URI::redirect($http_referer);
							}
						}
						URI::redirect($user->url());
					}
				} 
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您的登录密码更新失败! 请与系统管理员联系.'));
				}
			}

		}

		header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
		header("Status: 401 Unauthorized");
		$this->layout->body = V('people:password',['user'=>$user, 'form'=>$form]);
	
	}

}
