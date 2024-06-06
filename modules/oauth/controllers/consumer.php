<?php
class Consumer_Controller extends Controller {


	// 该方法供调试
	/*
	function hi( $server ) {

		$client = OAuth_Client::factory($server);
		if (!$client) {
			URI::redirect('error/401');
		}

		var_dump($client->get_token());

		if ($client->support_rpc()) {
			$oauth_rpc = new OAuth2_RPC($server);
			$hello = $oauth_rpc->hello('world');
		}
		else {
			$hello = $client->apicall_hello('world');
		}

		var_dump($hello);
		die;

	}
	*/

	function sso() {
		$server = Input::form('server');
		$client = OAuth_Client::factory($server);
		if (!$client) {
			URI::redirect('error/401');
		}

		$rpc_servers = Config::get('rpc.servers');
		$url = $rpc_servers[$server]['api'];
        if ($url) {
            $rpc = new RPC($url);
            $username = $rpc->oauth->get_username($_SESSION['oauth2_token']['access_token']);
        }
		if (strpos($username, '|')) {
			$oauth_token = $username . '%' . $server;
		}
		else {
			$oauth_token = $username . '|' . $server;
		}
		$user = O('user', ['token' => $oauth_token]);
		if ( !$user->id ) {
			//单点登录，增加type，不跳转到success。
			$_SESSION['oauth_type'] = 'sso';
			URI::redirect("!oauth/consumer/request_login?server=$server");
		}

		$oauth_sso_referer = $_SESSION['oauth_sso_referer'];
		Auth::login($oauth_token);
		URI::redirect($oauth_sso_referer ? : '/');
	}

	function authorization_request() {
		$server = Input::form('server');
		$client = OAuth_Client::factory($server);
		if (!$client) {
			URI::redirect('error/401');
		}

		$client->authorization_request();
	}

	function authorization_grant() {

		$server = Input::form('server');

		$client = OAuth_Client::factory($server);

		if (!$client) {
			//URI::redirect('error/401');
			URI::redirect(URI::url('!oauth/redirect/fail'));
		}

		// exchange for access token
		$form = Input::form();
		if ($client->authorization_grant($form)) {
			$refer = $_SESSION['oauth_refer'];
            Log::add($refer, 'oauth');

			URI::redirect($refer);
		}
		else {
			//URI::redirect('error/401');
			URI::redirect(URI::url('!oauth/redirect/fail'));
		}

	}

	function request_login() {

		$server = Input::form('server');

		if (L('ME')->id) {
			//跳转到成功页面，会自动跳转
			URI::redirect(URI::url('!oauth/redirect/success'));
		}

		$client = OAuth_Client::factory($server);
		if (!$client) {
			//URI::redirect('error/401');
			//跳转到成功页面，会自动跳转
			URI::redirect(URI::url('!oauth/redirect/fail'));
		}

		if ($client->support_rpc()) {
			$oauth_rpc = new OAuth2_RPC($server);

			$user_info = $oauth_rpc->user->info($_SESSION['oauth2_token']['access_token']);
		}
		else {
			$user_info = $client->apicall_current_user();
		}
        
		$username = $user_info['username'];

        try {
            //如果现有backend存在，不进行过滤
            $backends = Config::get('auth.backends');
            foreach($backends as $backend => $info) {
                $pos = strpos($username, $backend);

                if ($pos && $pos + strlen($backend) == strlen($username)) {
                    $user_token = $username;
                    throw new Error_Exception;
                }
            }

            $hostname = Config::get('rpc.hostname');
            if (strpos($username, $hostname)) {
                $user_token = substr($username, 0, strpos($username, $hostname) -1);
                throw new Error_Exception;
            }

            if (strpos($username, '|')) {
                $user_token = $username . '%' . $server;
            }
            else {
                $user_token = $username . '|' . $server;
            }
        }
        catch(Error_Exception $e) {
        }

		$user = O('user', ['token' => $user_token]);

		if ($user->id) {
			// 登录
			Auth::login($user_token);
			//URI::redirect('/');
			//跳转到成功页面，会自动跳转

			if($_SESSION['oauth_type'] == 'sso') {
				unset($_SESSION['oauth_type']);
                URI::redirect($_SESSION['oauth_sso_referer']);
			}
			else{
				URI::redirect(URI::url('!oauth/redirect/success'));
			}
		}
		else {
			list($token_name, $token_backend) = Auth::parse_token($user_token);

			$auth_backends = Config::get('auth.backends');

			if (!$auth_backends[$token_backend]) {
				// 如果未设置同名 auth backend, 则 401
				// 但以后可做成到此跳转注册本地用户, 注册后新用户绑定 token, 做成这样后, 需同步修改 OAuth_Client::get_oauth_login_links()
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('oauth', '不允许以此 OAuth 验证后台登录'));
				//URI::redirect('error/401');
				//跳转到成功页面，会自动跳转
				URI::redirect(URI::url('!oauth/redirect/fail'));
			}
			// $token = $remote_id . '@' . $server;
			Auth::login($user_token);

			$backend_opts = $auth_backends[$token_backend];

			if ($backend_opts['auto_signup']) {
				$user = $this->add_user($user_info, $backend_opts['auto_active']);
				// if ($user->id) {
				// 	URI::redirect('/');
				// }
			}

            //跳转到成功页面，会自动跳转
            $redirect = $_SESSION['oauth_sso_referer'] ? : '!oauth/redirect/success';
            URI::redirect(URI::url($redirect));
		}
	}

	private function add_user( $attrs, $active = FALSE ) {

		$auth_token = Auth::token();

		if (!$auth_token) {
			return FALSE;
		}

		$user = O('user');

		$user->token = $auth_token;

		$keys_should_unset = [
			'id', 'token',
			];

		foreach ($keys_should_unset as $k) {
			unset($attrs[$k]);
		}

		foreach ($attrs as $k => $v) {
			$user->$k = $v;
		}

		if ($active) {
			$user->atime = Date::time();
		}

		$user->save();

		return $user;

	}
}

class Consumer_AJAX_Controller extends AJAX_Controller {
	function index_oauth_login_click(){
		$href = Input::form('href');
		JS::dialog(V('oauth:oauth_login', ['href'=>$href]));
	}
}
