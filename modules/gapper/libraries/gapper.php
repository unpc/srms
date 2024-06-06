<?php

class Gapper {

	static function get_RPC() {
		//验证APP是否存在
		$config = Config::get('gapper.client');

		try {
			$rpc = new RPC($config['api']);
			$client_id = $config['client_id'];
			$client_secret = $config['client_secret'];

			$ret = $rpc->gapper->authorize($config['client_id'], $config['client_secret']);

			//如果 APP 不存在 跳转到
			if (!$ret) {
				return false;
			}

			return $rpc;
		}
		catch(Exception $e) {}
	}

	static function get_mall_groups() {

		try{
			//得到所有绑定了的商城
			$binded_sites = Mall::binded_sites();
			$mall_gapper_groups = [];
			foreach ($binded_sites as $source) {
				if(Mall::get_status($source) != Mall::BIND_STATUS_SUCCESS) continue;

		        $info = Mall::info($source);
		        $api = $info['api'];
		        if(!$api) return;

	            $mall_rpc = new RPC($api);
	            $uuid = Lab::get('lims.site_id');

	            $code = $mall_rpc->customer->get_site_code($uuid);
	            $ssl = new OpenSSL();
	            $signature = $ssl->sign($code, Lab::get('lims.private_key'));

	            if ($mall_rpc->customer->auth(base64_encode($signature))) {

	            	//检查这个组是否在gapper中存在
	            	$group = $mall_rpc->customer->get_gapper_group();

	            	if($group) {
	            		$mall_gapper_groups[$group] = [
	            			'id' => $group,
	            			'source' => $source
	        			];
	            	}
	            }
			}
		}
		catch(Exception $e){}

		return $mall_gapper_groups;
	}

	static function set_mall_group($group_id) {
		try{
			$binded_sites = Mall::binded_sites();
			$mall_gapper_groups = [];
			foreach ($binded_sites as $source) {
				if(Mall::get_status($source) != Mall::BIND_STATUS_SUCCESS) continue;

		        $info = Mall::info($source);
		        $api = $info['api'];
		        if(!$api) return;

	            $mall_rpc = new RPC($api);
	            $uuid = Lab::get('lims.site_id');

	            $code = $mall_rpc->customer->get_site_code($uuid);
	            $ssl = new OpenSSL();
	            $signature = $ssl->sign($code, Lab::get('lims.private_key'));

	            if ($mall_rpc->customer->auth(base64_encode($signature))) {
	            	$mall_rpc->customer->set_gapper_group($group_id);
	            }
			}
		}
		catch(Exception $e){}
	}

	//处理gapper用户登录
	static function ready() {
		$form = Input::form();

		if ($form['gapper-token']) {
			self::login_by_token($form['gapper-token']);
		}
	}

	static function is_gapper_user($user) {
		// 不发送RPC验证是否用户真的在gapper中存在，只是判断backend是gapper
		list(,$backend) = Auth::parse_token($user->token);

		if($backend == 'gapper') return true;
		return false;
	}

	static function link_identity($user, $identity) {
		if(!$user->id || !$identity) return false;

		list(,$backend) = Auth::parse_token($identity);
		$sources = Config::get('gapper.sources');

		//处理需要link的backends
		if(array_key_exists($backend, $sources)) {
			$source = $sources[$backend];
			try{
				$rpc = self::get_RPC();
				return $rpc->gapper->user->linkIdentity((string)$user->token, $source, $identity);
			}
			catch(Exception $e){
				return false;
			}
		}
		else { //如果不需要远程link则在本地生成gapper_fallback_user
			$gapper_fallback_user = O('gapper_fallback_user', ['user'=>$user]);
			$gapper_fallback_user->user = $user;
			$gapper_fallback_user->token = $identity;
			return $gapper_fallback_user->save();
		}

		//如果不处理返回TRUE
		return true;
	}

	static function get_user_by_identity($token) {
		if(!$token) return false;
		list(,$backend) = Auth::parse_token($token);

		$sources = Config::get('gapper.sources');
		//处理需要link的backends
		if(array_key_exists($backend, $sources)) {
			$source = $sources[$backend];

			try{
				$rpc = self::get_RPC();
				return $rpc->gapper->user->getUserByIdentity($source, $token);
			}
			catch(Exception $e){
				return false;
			}
		}

		return false;
	}

	static function login_by_token($login_token) {

		try{
			if (!$login_token) throw new Error_Exception;

			$rpc = self::get_RPC();
			$user_info = $rpc->gapper->user->authorizeByToken($login_token);

			if (!$user_info) throw new Error_Exception;

			if (Auth::logged_in()) {
				Auth::logout();
			}

			Auth::login($user_info['username']);
		}
		catch(Exception $e){
			URI::redirect('login');
		}
	}

	static function on_user_deleted($e, $user) {
		$gapper_fallback_user = O('gapper_fallback_user', ['user'=>$user]);
		if($gapper_fallback_user->id) {
			$token = $gapper_fallback_user->token;
			$gapper_fallback_user->delete();
			$auth = new Auth($token);
			$auth->remove();
		}
	}
}
