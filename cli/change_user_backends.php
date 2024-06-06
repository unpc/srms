<?php

  /**
   * @file   change_user_backends.php
   * @author  WYuSheng <932401986@qq.com>
   * @date   2017-04-19
   *
   * @brief  batch change users backends from database to casbackends
   *
   * usage: SITE_ID=cf LAB_ID=test ./change_user_backends.php
   *
   * 根据规则不同修改pregmatch的内容
   *
   */
require '../base.php';

foreach (Q('user') as $user) {
	$auth = new Auth($user->token);

	list($token, $backend) = Auth::parse_token($user->token);
	$new_backend = Config::get('auth.cas_backend');
	if ($backend == $new_backend) continue;
	if (preg_match('/^[0-9]+$/', $token)) {
		$new_token = $token;
		var_dump($token . "|" . $backend);

		try {
			$new_full_token = Auth::make_token($new_token, $new_backend);
			if (User_Model::is_reserved_token($new_token) || User_Model::is_reserved_token($new_full_token)) {
				throw new Exception(I18N::T('people', '%token帐号已被管理员保留。', ['%token'=>$new_full_token]));
			}

			$name_token = O('user', ['token' => $new_full_token]);
			if ($name_token->id && $name_token->id != $user->id ) {
				throw new Exception(I18N::T('people', '%token帐号在系统中已存在!', ['%token'=>$new_full_token]));
			}
			if ($backend != $new_backend) {

				$new_auth = new Auth($new_full_token);
				//设定临时auth
				if ($new_auth->create(uniqid())) {
					$user->token = $new_full_token;
					if ($user->save()) {
						$ret = $auth->remove();
					}
					else {
						throw new Exception(I18N::T('people', '用户登录帐号更新失败!'));
					}
				}
				else {
					throw new Exception(I18N::T('people', '新帐号创建失败'));
				}
			}
		} catch (Exception $e) {
			var_dump($e->getMessage());
		}
	}
}