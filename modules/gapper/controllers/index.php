<?php

class Index_Controller extends Layout_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);
		$autoload = ROOT_PATH.'vendor/autoload.php';
		if(file_exists($autoload)) require_once($autoload);
		$browser = new \Ikimea\Browser\Browser;
        if ( $browser->getBrowser() == \Ikimea\Browser\Browser::BROWSER_IE && $browser->getVersion() < 9) {
			URI::redirect('!gapper/badbrowser');
		}
        $this->add_css('gapper:common');
        $this->layout->title = I18N::T('gapper', '升级到Gapper');
        $this->layout->body = V('body');

    }

    /**
     * app 入口，检查app是否可以正常使用
     * 不可用跳转到升级页面
     * 正常则获取gapper-token进行跳转
     */
	function index() {
		$form = Input::form();
		$me = L('ME');

		if($form['app']) {
			$app_key  = $form['app'];
			$app = Config::get('gapper.apps')[$app_key];
		}

		try {
			$rpc = Gapper::get_RPC();
			//如果 APP 不存在 跳转到
			if (!$rpc) {
				URI::redirect('error/401');
				return;
			}

			if(!count(Mall::binded_sites())) {
				throw new Error_Exception;
			}

			//目前只是lab使用，所以直接去default_lab。之后需要根据传入的lab进行判断
			$lab = Lab_Model::default_lab();
			//判断 gapper 是否有这个组
			$group_id = (int)$lab->gapper_group;
			if (!$group_id) {
				throw new Error_Exception;
			}

			if(!$rpc->gapper->group->getInfo($group_id)) {
				//gapper那边把组删除了，则应该清楚本地组的gapper_group
				$lab->gapper_group = 0;
				$lab->save();
				throw new Error_Exception;
			}

			//判断该用户是否有 gapper 帐号
			if(!Gapper::is_gapper_user($me)) {
				//gapper中用户不会删除，所以没有做gapper_user清空的处理
				throw new Error_Exception;
			}

			//不进行app在不在这个组里的判断，如果用户在gapper那边删除了该应用，则跳转过去会报错
			if (!$me->gapper_group) {
				if($rpc->gapper->group->addMember((int)$group_id, (string)$me->token)) {
					$me->gapper_group = $group_id;
					$me->save();
				}
			}

			//RPC 登录然后带着token进行跳转
			$login_token = $rpc->gapper->user->getLoginToken((string)$me->token, $app['client_id']);
			URI::redirect(URI::url($app['url'], ['gapper-token'=>$login_token, 'gapper-group'=>$group_id]));
		}
		catch(Error_Exception $e) {
			$this->app_intro($app);
		}
    }

    // app 信息页面
    public function app_intro($app) {
		$me = L('ME');

		try {
			$rpc = Gapper::get_RPC();
			if(!$rpc) throw new Error_Exception;

			$app_info = $rpc->gapper->app->getInfo($app['client_id']);
			if ($app_info['icon_url']) {
				$app['icon_url'] = $app_info['icon_url'];
			}
		}
		catch(Exception $e) {}

		$pi_token = Config::get('lab.pi');
		$view = $me->token == $pi_token ? 'incharge' : 'user';

		$lab = Lab_Model::default_lab();

		$cancel_url = $_SERVER['HTTP_REFERER'] ? : $me->url();
		$this->layout->body = V('gapper:upgrade/'.$view.'/intro', ['app'=>$app, 'lab'=>$lab]);
	}

	function help() {
		$this->layout->body = V('gapper:help');
	}

	/**
	 * 升级流程
	 * 区分用户操作步骤，区分pi与普通用户
	 */
	function upgrade() {

		$me = L('ME');
		$pi_token = Config::get('lab.pi');
		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		try {
			$rpc = Gapper::get_RPC();
			if (!$rpc) URI::redirect('error/401');

			if (!count(Mall::binded_sites())) {

				//只有pi可以绑定商城
				if ($me->token == $pi_token) {
					$this->mall_upgrade();
					return;
				}
				else {
					URI::redirect('error/401');
				}
			}
			elseif (($mall_gapper_groups = Gapper::get_mall_groups()) && $me->token == $pi_token) {
				if (!$gapper_group){
					$this->layout->body = V('gapper:upgrade/incharge/mall_groups', ['mall_gapper_groups'=>$mall_gapper_groups]);
					return;
				}
				elseif (!Gapper::is_gapper_user($me)) {
					$this->layout->body = V('gapper:upgrade/incharge/user_upgrade', ['mall_gapper_groups'=>$mall_gapper_groups]);
					return;
				}
			}
			elseif (!Gapper::is_gapper_user($me)) {

				//普通用户和负责人区分开
				if($me->token == $pi_token) {
					$this->incharge_upgrade();
					return;
				}
				else {
					$this->user_upgrade();
					return;
				}
			}
			elseif (!$gapper_group || !$rpc->gapper->group->getInfo($gapper_group)) {

				$this->group_upgrade();
				return;
			}

			//如果有组有用户，则直接往app跳转，app会有其他错误提示
			URI::redirect('!gapper/success');

		}
		catch(Error_Exception $e) {}
	}

	function mall_upgrade() {
		$me = L('ME');

		if($me->token != Lab::get('lab.pi')) URI::redirect('error/401');

		//如果已经有绑定则跳转到用户绑定
		if(count(Mall::binded_sites())) {
			URI::redirect('!gapper/incharge_upgrade');
		}

		$this->layout->body = V('gapper:upgrade/incharge/mall_upgrade');
	}

	//负责人 升级用户
	public function incharge_upgrade() {
		$me = L('ME');

		$form = Form::filter(Input::form());
		if ($me->token != Config::get('lab.pi')) URI::redirect('error/401');

		//如果没有绑定商城或者已经绑定了用户，则重新跳转到upgrade
		if (!count(Mall::binded_sites()) || Gapper::is_gapper_user($me)) {
			URI::redirect('!gapper/upgrade');
		}

		if ($form['submit']) {
			try {
				$rpc = Gapper::get_RPC();
				if (!$rpc) URI::redirect('error/401');

				$form
					->validate('name', 'not_empty', I18N::T('gapper','姓名不能为空!'))
					->validate('email', 'is_email', I18N::T('gapper','邮箱格式不正确!'))
                    ->validate('password', 'length(8,24)', I18N::T('gapper','输入的密码不能小于8位!'));

                if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
					$form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
				}

				if($form->no_error) {

					$user = [
						'name' => $form['name'],
						'email' => $form['email'],
						'username' => $form['email'],
						'password' => $form['password'],
					];

					$user_id = $rpc->gapper->user->registerUser($user);

					if(!$user_id) {
						$form->set_error('*', I18N::T('gapper','Gapper用户生成失败!'));

					}
					else{
						$gapper_user_info = $rpc->gapper->user->getInfo($form['email']);
						if(!$gapper_user_info['id']) {
							throw new Error_Exception;
						}

						$old_token = $me->token;
						$me->token = $gapper_user_info['username'];

						if($me->save()) {
							$lab = Lab_Model::default_lab();
							$gapper_group = (int)$lab->gapper_group;
							// 如果存在gapper_group则需要将用户加入到对应的组中
							if((!$gapper_group || $rpc->gapper->group->addMember($gapper_group, (int)$user_id))
								&& Gapper::link_identity($me, $old_token)) {
								Auth::login($me->token);
								Lab::set('lab.pi', $me->token);

								//lims升级的时候，升级关联的商城的用户
								Mall::set_mall_user_token($me);
								URI::redirect('!gapper/group_upgrade');
							}
							else {
								//添加用户失败的处理
								$me->token = $old_token;
								$me->save();
								$form->set_error('*', I18N::T('gapper','用户关联失败!'));
							}
						}
						else{
							$form->set_error('*', I18N::T('gapper','用户关联失败!'));
						}
					}
				}
			}
			catch(Exception $e) {
				if($e->getCode() == 10001) {
					$form->set_error('email', I18N::T('gapper','Email已存在!'));
				}
				else {
					$form->set_error('*', I18N::T('gapper','系统错误请联系管理员!'));
				}
			}
		}

		$mall_gapper_groups = Gapper::get_mall_groups();
		$this->layout->body = V('gapper:upgrade/incharge/user_upgrade', ['form'=>$form, 'mall_gapper_groups'=>$mall_gapper_groups]);
	}

	//负责人登录
	public function incharge_login() {
		$form = Form::filter(Input::form());

		$me = L('ME');
		if ($me->token != Config::get('lab.pi')) URI::redirect('error/401');

		//如果没有绑定商城或者已经绑定了用户，则重新跳转到upgrade
		if (!count(Mall::binded_sites()) || Gapper::is_gapper_user($me)) {
			URI::redirect('!gapper/upgrade');
		}

		if($form['submit']) {
			try{
				$form
					->validate('email', 'is_email', I18N::T('gapper','邮箱格式不正确!'))
					->validate('password', 'not_empty', I18N::T('gapper','密码不能为空!'));

				if($form->no_error) {

					$rpc = Gapper::get_RPC();
					if(!$rpc) URI::redirect('error/401');

					$ret = $rpc->gapper->user->verify($form['email'], $form['password']);

					if(!$ret) {
						$form->set_error('*', I18N::T('gapper','错误的电子邮箱、密码组合!'));
					}
					else{
						$gapper_user_info = $rpc->gapper->user->getInfo($form['email']);
						if (!$gapper_user_info) throw new Error_Exception;
						$user = O('user', ['token'=>$gapper_user_info['username']]);
						if ($user->id) {
							$form->set_error('*', I18N::T('gapper','您选择的账户已经绑定了其他用户'));
						}
						else {
							$old_token = $me->token;
							$me->token = $gapper_user_info['username'];

							if($me->save()) {
								$lab = Lab_Model::default_lab();
								$gapper_group = (int)$lab->gapper_group;
								if((!$gapper_group || $rpc->gapper->group->addMember($gapper_group, (string)$me->token))
									&& Gapper::link_identity($me, $old_token)) {
									Auth::login($me->token);
									Lab::set('lab.pi', $me->token);

									//lims升级的时候，升级关联的商城的用户
									Mall::set_mall_user_token($me);
									URI::redirect('!gapper/group_upgrade');
								}
								else {
									//添加用户失败的处理
									$me->token = $old_token;
									$me->save();
									$form->set_error('*', I18N::T('gapper','用户关联失败!'));
								}
							}
							else {
								$form->set_error('*', I18N::T('gapper','用户关联失败!'));
							}
						}
					}
				}
			}
			catch(Exception $e) {
				$form->set_error('*', I18N::T('gapper','系统错误请联系管理员!'));
			}
		}

		$mall_gapper_groups = Gapper::get_mall_groups();
		$this->layout->body = V('gapper:upgrade/incharge/login', ['form'=>$form, 'mall_gapper_groups'=>$mall_gapper_groups]);
	}

	//负责人升级组信息
	public function group_upgrade() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		if($me->token != Config::get('lab.pi')) URI::redirect('error/401');

		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		//如果已经绑定了组
		if($gapper_group || !count(Mall::binded_sites()) || !Gapper::is_gapper_user($me)) {
			URI::redirect('!gapper/upgrade');
		}
		try{

			$rpc = Gapper::get_RPC();
			if(!$rpc) URI::redirect('error/401');
			//绑定的商城已经有gapper组了则直接绑定跳转,如果有多个则应该让用户选择
			$mall_gapper_groups = Gapper::get_mall_groups();

			if(count($mall_gapper_groups)) {

				$this->layout->body = V('gapper:upgrade/incharge/mall_groups', ['mall_gapper_groups'=>$mall_gapper_groups]);
				return;
			}

			//获得自己是管理员的组
			$groups = $rpc->gapper->user->getGroups((string)$me->token);

			if($form['submit']) {

				$form
					->validate('name', 'not_empty', I18N::T('gapper','组标识不能为空!'))
					->validate('name', 'is_token', I18N::T('gapper','请填写符合规则的组标识!'))
					->validate('title', 'not_empty', I18N::T('gapper','组名称不能为空!'));

				if($form->no_error) {

					$group_info = [
						'user' => (string)$me->token,
						'name' => $form['name'],
						'title' => $form['title'],
					];

					$group_id = $rpc->gapper->group->create($group_info);

					if(!$group_id) {
						$form->set_error('*', I18N::T('gapper','Gapper组生成失败!'));
					}
					else {

						$lab->gapper_group = $group_id;
						if($lab->save()) {

							$lims_client_id = Config::get('gapper.client')['client_id'];
							$rpc->gapper->app->installTo($lims_client_id, 'group', (int)$group_id);

							$mall_apps = Config::get('gapper.apps');
							foreach ($mall_apps as $ma) {
								$rpc->gapper->app->installTo($ma['client_id'], 'group', (int)$group_id);
							}

							//打个标记，用户已经在组中
							$me->gapper_group = $group_id;
							$me->save();

							Gapper::set_mall_group($group_id);
							URI::redirect('!gapper/success');
						}
						else {
							$form->set_error('*', I18N::T('gapper','组关联失败!'));
						}
					}
				}
			}
		}
		catch(Exception $e) {
			if($e->getCode() == 10001) {
				$form->set_error('name', I18N::T('gapper','组标识已存在!'));
			}
			else {
				if($lab->gapper_group) {
					$lab->gapper_group = 0;
					$lab->save();
				}
				$form->set_error('*', I18N::T('gapper','系统错误请联系管理员!'));
			}
		}

		$this->layout->body = V('gapper:upgrade/incharge/group_upgrade', ['groups'=>$groups, 'form'=>$form]);
	}

	//升级成功
	public function success() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		if(!Gapper::is_gapper_user($me) || !$gapper_group || !count(Mall::binded_sites())) {
			URI::redirect('!gapper/upgrade');
		}
		$role = ($me->token == Config::get('lab.pi')) ? 'incharge' : 'user';

		$this->layout->body = V('gapper:upgrade/'.$role.'/success');
	}


	//普通用户升级用户
	public function user_upgrade() {
		$me = L('ME');

		$form = Form::filter(Input::form());

		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		if(!$gapper_group || !count(Mall::binded_sites()) || Gapper::is_gapper_user($me)) {
			URI::redirect('!gapper/upgrade');
		}


		if($form['submit']) {
			try {
				$rpc = Gapper::get_RPC();
				if(!$rpc) URI::redirect('error/401');
				$form
					->validate('name', 'not_empty', I18N::T('gapper','姓名不能为空!'))
					->validate('email', 'is_email', I18N::T('gapper','邮箱格式不正确!'))
                    ->validate('password', 'length(8,24)', I18N::T('gapper','输入的密码不能小于8位!'));
                    
				if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
					$form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
				}

				if($form->no_error) {

					$user = [
						'name' => $form['name'],
						'email' => $form['email'],
						'username' => $form['email'],
						'password' => $form['password'],
					];

					$user_id = $rpc->gapper->user->registerUser($user);

					if(!$user_id) {
						$form->set_error('*', I18N::T('gapper','Gapper用户生成失败!'));
					}
					else{

						$gapper_user_info = $rpc->gapper->user->getInfo((int)$user_id);
						if(!$gapper_user_info) throw new Error_Exception;

						$old_token = $me->token;
						$me->token = $gapper_user_info['username'];

						if($me->save()) {

							if($rpc->gapper->group->addMember($gapper_group, (int)$user_id) && Gapper::link_identity($me, $old_token)) {

								//打个标记，用户已经在组中
								$me->gapper_group = $gapper_group;
								$me->save();

								Auth::login($me->token);
								URI::redirect('!gapper/success');
							}
							else{
								//添加用户失败的处理
								$me->token = $old_token;
								$me->save();
								$form->set_error('*', I18N::T('gapper','用户关联失败!'));
							}
						}
						else{
							$form->set_error('*', I18N::T('gapper','用户关联失败!'));
						}
					}
				}
			}
			catch(Exception $e) {
				if($e->getCode() == 10001) {
					$form->set_error('email', I18N::T('gapper','Email已存在!'));
				}
				else {
					$form->set_error('*', I18N::T('gapper','系统错误请联系管理员!'));
				}
			}
		}

		$this->layout->body = V('gapper:upgrade/user/upgrade', ['form'=>$form]);
	}

	//普通用户登录
	public function user_login() {
		$me = L('ME');
		$form = Form::filter(Input::form());

		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		if(!$gapper_group || !count(Mall::binded_sites()) || Gapper::is_gapper_user($me)) {
			URI::redirect('!gapper/upgrade');
		}

		if($form['submit']) {
			try{
				$form
					->validate('email', 'is_email', I18N::T('gapper','邮箱格式不正确!'))
					->validate('password', 'not_empty', I18N::T('gapper','密码不能为空!'));

				if($form->no_error) {

					$rpc = Gapper::get_RPC();
					if(!$rpc) URI::redirect('error/401');

					$ret = $rpc->gapper->user->verify($form['email'], $form['password']);

					if(!$ret) {
						$form->set_error('*', I18N::T('gapper','错误的电子邮箱、密码组合!'));
					}
					else{
						$gapper_user_info = $rpc->gapper->user->getInfo($form['email']);
						if(!$gapper_user_info) throw new Error_Exception;
						$user = O('user', ['token'=>$gapper_user_info['username']]);
						if ($user->id) {
							$form->set_error('*', I18N::T('gapper','您选择的账户已经绑定了其他用户'));
						}
						else {
							$old_token = $me->token;
							$me->token = $gapper_user_info['username'];

                            //打个标记，用户已经在组中
                            $me->gapper_group = $gapper_group;

							if($me->save()) {

								if($ret && $rpc->gapper->group->addMember($gapper_group, (string)$me->token) && Gapper::link_identity($me, $old_token)) {
									Auth::login($me->token);
									URI::redirect('!gapper/success');
								}
								else {
									//添加用户失败的处理
									$me->token = $old_token;
									$me->save();
									$form->set_error('*', I18N::T('gapper','用户关联失败!'));
								}
							}
							else {
								$form->set_error('*', I18N::T('gapper','用户关联失败!'));
							}
						}
					}
				}
			}
			catch(Exception $e) {
				$form->set_error('*', I18N::T('gapper','系统错误请联系管理员'));
			}
		}

		$this->layout->body = V('gapper:upgrade/user/login', ['form'=>$form]);
	}

}

class Index_AJAX_Controller extends AJAX_Controller {

	//pi选择分组
	function index_bind_group_click() {

		if(!JS::confirm(H(I18N::T('gapper','您确定选择该组进行关联?')))) return;
		$form = Input::form();
		$group_id = (int)$form['gid'];
		if(!$group_id) return;

		$me = L('ME');
		if($me->token != Config::get('lab.pi')) return;

		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;

		try{
			$rpc = Gapper::get_RPC();
			if(!$rpc) JS::redirect('error/401');

			if($rpc->gapper->user->getGroupInfo((string)$me->token, $group_id)) {

				$lab->gapper_group = $group_id;
				if($lab->save()) {

					//把lims加到这个组里
					$lims_client_id = Config::get('gapper.client')['client_id'];
					$rpc->gapper->app->installTo($lims_client_id, 'group', $group_id);

					$gapper_apps = Config::get('gapper.apps');
					foreach ($gapper_apps as $ga) {
						$rpc->gapper->app->installTo($ga['client_id'], 'group', $group_id);
					}

					Gapper::set_mall_group($group_id);
					JS::redirect(URI::url('!gapper/success'));
				}
			}
		}
		catch(Exception $e){
			if($lab->gapper_group) {
				$lab->gapper_group = 0;
				$lab->save();
			}
			JS::alert(I18N::T('gapper','系统错误请联系管理员!'));
		}
	}

	//绑定的商城已经有gapper分组，只能选择商城的分组
	function index_choose_mall_group_click() {

		if(!JS::confirm(H(I18N::T('gapper','您确定选择该组进行关联?')))) return;
		$form = Input::form();
		$group_id = (int)$form['gid'];

		$mall_gapper_groups = Gapper::get_mall_groups();
		if(!in_array($group_id, array_keys($mall_gapper_groups))) return;

		$me = L('ME');
		if($me->token != Config::get('lab.pi')) return;

		$lab = Lab_Model::default_lab();
		$gapper_group = (int)$lab->gapper_group;
		if($gapper_group) return;

		try{
			$rpc = Gapper::get_RPC();
			if(!$rpc) throw new Error_Exception;

			$lab->gapper_group = $group_id;
			if (!$lab->save()) throw new Error_Exception;

			Gapper::set_mall_group($group_id);

			//把lims加到这个组里
			$lims_client_id = Config::get('gapper.client')['client_id'];
			if(!$rpc->gapper->app->installTo($lims_client_id, 'group', $group_id)) {
				throw new Error_Exception;
			}

			$mall_user = O('mall_user', ['user'=>$me, 'source'=>$form['source']]);
			if(!$mall_user->id);

			if($mall_rpc = Mall::get_RPC($form['source'])) {

            	$opt['ids'] = [$mall_user->source_uid];
            	$mall_gapper_user = $mall_rpc->customer->get_users($opt);
            	$gapper_token = $mall_gapper_user[$mall_user->source_uid]['token'];

            	if($gapper_token) {
            		list(,$backend) = Auth::parse_token($gapper_token);
            		if(!Gapper::is_gapper_user($me) && $backend == 'gapper' && $rpc->gapper->user->getGroupInfo((string)$gapper_token, (int)$group_id)) {

            			$old_token = $me->token;
            			$me->token = $gapper_token;
            			if($me->save() && Gapper::link_identity($me, $old_token)) {
            				Lab::set('lab.pi', $me->token);
            				Auth::login($me->token);
            				JS::redirect(URI::url('!gapper/success'));
            				return;
            			}
            		}
            	}
            }

			JS::redirect(URI::url('!gapper/upgrade'));
		}
		catch(Exception $e){
			if($lab->gapper_group) {
				$lab->gapper_group = 0;
				$lab->save();
			}
			JS::alert(I18N::T('gapper','系统错误请联系管理员!'));
		}
	}
}
