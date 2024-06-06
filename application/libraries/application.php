<?php

class Application {

	static function load_globals($path) {
		$path = $path.'globals'.EXT;
		!file_exists($path) or @include($path);		
	}

	static function setup(){
		define('SITE_ID', $_SERVER['SITE_ID']?:'default');
		define('SITE_PATH', ROOT_PATH.'sites/'.SITE_ID.'/');
		if (!@is_dir(SITE_PATH)) {
			header("Status: 404 Not Found");
			die;
		}

		define('LAB_ID', $_SERVER['LAB_ID']?:'default');
		define('LAB_PATH', SITE_PATH.'labs/'.LAB_ID.'/');
		//check if the lab exists
		if (!@is_dir(LAB_PATH)) {
			header("Status: 404 Not Found");
			die;
		}

        //cli 应考虑跳过 disable 判断
        if (PHP_SAPI != 'cli' && file_exists(LAB_PATH . 'disable')) {
            header("Status: 404 Not Found");
            die;
        }

        if (file_exists(LAB_PATH . 'class_map')) {
            $GLOBALS['class_map'] = @json_decode(file_get_contents(LAB_PATH . 'class_map'));
        }

        if (file_exists(LAB_PATH . 'view_map')) {
            $GLOBALS['view_map'] = @json_decode(file_get_contents(LAB_PATH . 'view_map'));
        }

        if (file_exists(LAB_PATH . 'class_map')) {
            $GLOBALS['class_map'] = @json_decode(file_get_contents(LAB_PATH . 'class_map'), TRUE);
        }

        if (file_exists(LAB_PATH . 'view_map')) {
            $GLOBALS['view_map'] = @json_decode(file_get_contents(LAB_PATH . 'view_map'), TRUE);
        }

		Core::include_modules(SITE_PATH);
		Core::include_path('application', SITE_PATH);

		// 设置autoload cache prefix
		Cache::$CACHE_PREFIX = hash('md4', SITE_PATH.LAB_ID).':';

		if (preg_match('/^!(.+)$/', Input::arg(0), $parts)) {
			$mname = mb_convert_case($parts[1], MB_CASE_LOWER);
			if(!in_array($mname, ['system', 'application'])) define('MODULE_ID', $mname);
			array_shift(Input::args());
		}

		Core::include_modules(LAB_PATH);
		Core::include_path('application', LAB_PATH);
		Core::include_path('support', LAB_PATH . 'support/');

		Config::load(SITE_PATH, 'lab');
		Config::load(LAB_PATH, 'lab');

		$mods = (array) Config::get('lab.modules');

		if (count($mods) > 0) {
			Core::set_legal_modules($mods);
		}

		self::load_globals(SITE_PATH);
		self::load_globals(LAB_PATH);

		Config::setup();

		if ( file_exists(LAB_PATH . 'view_map') ) {
			$path_prefix = preg_replace('/([^\/])$/', '$1/', dirname($_SERVER['SCRIPT_NAME']));
			$cgi_path = 'http://'.$_SERVER['HTTP_HOST'].$path_prefix;
			Config::set('system.base_url', $cgi_path);
			Config::set('system.script_url', $cgi_path);
		}

		Misc::set_key_prefix(LAB_ID);

		if (!Config::get('database.default')) {
			Config::set('database.default', LAB_ID);
		}

		Config::set('system.log_path', LAB_PATH.'logs/%ident.log');

		Config::set('system.tmp_dir', sys_get_temp_dir().'/lims2/'.SITE_ID.'/'.LAB_ID.'/');

		// 为session_name添加LAB_ID后缀
		Config::set('system.session_name', 'session_lims2_'.SITE_ID.'_'.LAB_ID);

		if (defined('MODULE_ID') && Module::is_installed(MODULE_ID)) {
			Core::include_path(MODULE_ID, Core::module_path(MODULE_ID));
			Core::include_path(MODULE_ID, LAB_PATH.MODULE_BASE.MODULE_ID.'/');
		}

		ORM_Model::setup();
		Q::setup();

		date_default_timezone_set(Lab::get('system.timezone') ?: (Config::get('system.timezone') ?: 'Asia/Shanghai'));
		
		// 启动session 和 properties
		Session::setup();

		// TODO: 可能需要一组正则的配置文件来做白名单
		if (Config::get('system.frequency_check', FALSE) 
		&& $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'
		&& $_SERVER['PATH_INFO'] != '/api'  // 提炼白名单配置
		&& $_SERVER['PATH_INFO'] != '/' . LAB_ID . '/cas/login'
		&& strstr($_SERVER['PATH_INFO'], 'oauth') === FALSE
		&& strstr($_SERVER['PATH_INFO'], '/!labs/signup') === FALSE
		&& strstr($_SERVER['PATH_INFO'], '/!vidmon/snapshot/index') === FALSE) {
			// 如果是需要限制的站点 并且不是Ajax请求时 进行请求时间和次数记录
			$count = $_SESSION['auth.try_count'];
			$time = $_SESSION['#auth.try_time'];

			// 存储好之前的请求时间之后就把当前请求时间刷新
			$now = microtime(true);
			$_SESSION['#auth.try_time'] = $now;

			if (!$time) $count = 1; // 如果从来没记录过时间 请求次数初始化为1
			else if ($now - $time < 0.3) $count++; // 请求间隔小于阈值 请求次数加1
			else $count = 1; // 请求间隔存在且 大于阈值 请求次数重置为1

			if ($count > 3) { // 当请求次数大于4 视为过于频繁 进行登出
				session_unset();
				URI::redirect('error/429');
			}
			else $_SESSION['auth.try_count'] = $count; // 正常 记录请求次数;
		}

		Properties::setup();

		$pi_token = Lab::get('lab.pi');
		if ($pi_token) {
			Config::set('lab.pi', $pi_token);
		}

		// Cache::L('PERMS', Q("perm")->to_assoc('name', 'id'));

        if (Config::get('equipment.total_count')) {
            $cache = Cache::factory();

            if (!$cache->get('equipment_count')) {
                $count_selector = 'equipment[status=';
                $equipment_count=[
                    EQ_Status_Model::IN_SERVICE => Q($count_selector.EQ_Status_Model::IN_SERVICE.']')->total_count(),
                    EQ_Status_Model::OUT_OF_SERVICE => Q($count_selector.EQ_Status_Model::OUT_OF_SERVICE.']')->total_count(),
                    EQ_Status_Model::NO_LONGER_IN_SERVICE => Q($count_selector.EQ_Status_Model::NO_LONGER_IN_SERVICE.']')->total_count(),
                    'total' => Q('equipment')->total_count(),
                ];
                $cache->set('equipment_count', $equipment_count, 3600);
            }
        }

		Event::bind('system.ready', 'Application::ready');
		Core::bind_events();	// rebind events

        $autoload = ROOT_PATH.'vendor/autoload.php';
        if (file_exists($autoload)) require_once($autoload);
	}

	static function ready() {

        I18n::remember_system_locale();

		if (!defined('CLI_MODE')) {

			if ($white_list = Config::get('host.white_list', FALSE)) {
	            if (!is_array($white_list)) {
	                $white_list = [$white_list];
	            }
	            if (!in_array($_SERVER['HTTP_HOST'], $white_list)) {
	                header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
	                header("Status: 403 Forbidden");
	                die('Status: 403 Forbidden!');
	            }
	        }

            if ($oauth_provider = Input::form('oauth-sso')) {
                if (Auth::logged_in()) {
                    Auth::logout();
                }
                $_SESSION['oauth_sso_referer'] = URI::url();
                $_SESSION['from_lab'] = array_pop(explode('.', $oauth_provider));
                if (Input::form('q_params')) $_SESSION['q_params'] = Input::form('q_params');
                URI::redirect("!oauth/consumer/sso?server=$oauth_provider");
            }
            if ($oauth_provider = Input::form('oauth2')) {
                if (Auth::logged_in()) {
                    Auth::logout();
                }
                $_SESSION['oauth2_params'] = Input::form();
                $client = OAuth_Client::factory($oauth_provider);
                if (!$client) {
                    URI::redirect('error/401');
                }
                $client->get_token();

                $params = $_SESSION['oauth2_params'];
                unset($_SESSION['oauth2_params']);
                URI::redirect("!yiqikong/signup/accredit",[
                        'source' => 'oauth',
                        'remote' => $params['oauth2'],
                        'equipment_id' => $params['equipment_id']
                    ]);
            }

			$locale = Input::get('locale');
			if (isset($locale) && $_SESSION['system.locale'] !== $locale) {
				$_SESSION['system.locale'] = $locale;
			}


			if (!Auth::logged_in()) {
				Lab::check_remember_login();
			}
			
			if (Auth::logged_in()) {
				$token = Auth::token();
				$me = O('user', ['token'=>$token]);
                Cache::L('ME', $me);
				if ($me->id) {
					$locale = Properties::factory($me)->locale;
					if ($locale) {
						Config::set('system.locale', $locale);
					}
				}
			}
			else {
				Cache::L('ME', O('user'));		// 空用户
			}

			if (isset($_SESSION['system.locale'])) {
				Config::set('system.locale', $_SESSION['system.locale']);
			}

		}

		I18N::setup();

		/*
		 *  Cache::L -> Roles
		 *  -- 刚开始。系统默认做法如下，目前需要更改为带 Platform 的模式, 在 Platform 增加 system.ready 事件
		 *  -- 修改后 - 增加事件 trigger 的机制，让 ROLE 重设置更便捷
		 */

		Event::trigger('role.set_roles');

		/* BUG9766 既然我们需要预处理，那么从一开始就应该将equipment和Lab的实验室均添加成功 */
        if (Module::is_installed('labs')) Lab_Model::default_lab();
		// if (Module::is_installed('equipments')) Equipments::create_temporary_lab();
        if (Module::is_installed('credit') && $me->id) {
			Event::trigger('trigger_scoring_rule', $me, 'login'); // 触发用户首次激活自动计分规则
		}
	}

	static function shutdown() {
		Session::shutdown();
	}

	static function info() {
        $info = [
            'preload' => $GLOBALS['preload'],
            'config' => Config::export()
            ];

        return $info;
	}

    public static function auth_login($e, $token)
    {
        $u = O('user', ['token' => $token]);
        //该用户已注册，则跳回从站点登录
        if ($u->id && $_SESSION['from_slave']) {
            $from_slave = $_SESSION['from_slave'];
            $user_token = $_SESSION['user_token'];
            Auth::logout();
            URI::redirect(URI::url($from_slave, ['user_token' => $user_token]));
        }
    }
}

function truncate($text, $len=0) {
   if( (strlen($text) > $len) ) {

        $whitespaceposition = strpos($text," ",$len)-1;

        if( $whitespaceposition > 0 )
            $text = substr($text, 0, ($whitespaceposition+1));

        // close unclosed html tags
        if( preg_match_all("|<([a-zA-Z]+)>|",$text,$aBuffer) ) {

            if( !empty($aBuffer[1]) ) {

                preg_match_all("|</([a-zA-Z]+)>|",$text,$aBuffer2);

                if( count($aBuffer[1]) != count($aBuffer2[1]) ) {

                    foreach( $aBuffer[1] as $index => $tag ) {

                        if( empty($aBuffer2[1][$index]) || $aBuffer2[1][$index] != $tag)
                            $text .= '</'.$tag.'>';
                    }
                }
            }
        }
    }

    return $text;
}
