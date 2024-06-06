<?php

class CAS {

	static $TOKEN_NAME;

	static function init($opts = []) {

        if (!class_exists('phpCAS')) {
            self::loadVendor();
        }

        if (!count($opts)) {
            $opts = Config::get('cas.opts', []);
        }

        if ($opts['debug']) {
            \phpCAS::setDebug();
        }

		if (!\phpCAS::isInitialized()) {
			\phpCAS::client(CAS_VERSION_2_0
					, $opts['host']
					, $opts['port']
					, $opts['uri']
					, $opts['content']
				);

			self::setCasValidation($opts['certPath']);

            //用来配置curl
            if ($opts['curl_opts']) {
                foreach($opts['curl_opts'] as $k=> $v) {
                    \phpCAS::setExtraCurlOption($k, $v);
                }
            }
		}
	}

	static function setCasValidation($certPath='') {
		if ($certPath) {
			\phpCAS::setCasServerCACert($certPath);
		}
		else {
			\phpCAS::setNoCasServerValidation();
		}
	}

	static function getToken() {
		if (\phpCAS::checkAuthentication()) {
			return \phpCAS::getUser();
		}
		\phpCAS::forceAuthentication();
	}

	static function login($path='/') {
		if ($token = self::getToken()) {
			Auth::login(Auth::make_token($token, Config::get('auth.cas_backend')));
		}
		URI::redirect($path);
	}

	static function logout($path = '/') {
		\phpCAS::logoutWithRedirectService(URI::url($path));
	}

	static function auth_post_logout($e, $token) {
		list(, $backend) = explode('|', $token);
		if ($backend == Config::get('auth.cas_backend') &&
			Controller::$CURRENT instanceof Layout_Controller) {
			self::init();
			\phpCAS::logoutWithRedirectService(URI::url('/'));
		}
	}

	static function loadVendor() {
		$autoload = ROOT_PATH.'vendor/autoload.php';
		if (file_exists($autoload)) { require_once($autoload); }
	}

}
