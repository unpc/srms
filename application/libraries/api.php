<?php

class API_Exception extends Exception {}

class API {

	private $_debug = FALSE;
	function debug($debug=TRUE) {
		$this->_debug = $debug;
	}

	function dispatch() {
		// 首先解析body中的json格式
        $content = file_get_contents('php://input');
		$data = @json_decode($content, TRUE);
        if (!$_SERVER['HTTP_GINIROLECOOKIE']) {
            Cache::L('IS_API_REQUEST', true);
        }

		if (!is_array($data)) {
			$data = $_POST;
			if (!is_array($data['params'])) {
				$data['params'] = (array) @json_decode((string)$data['params']);
			}
		}

		if ($this->_debug) {
			Log::add(@json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'api');
		}

		$JSONRPC2 = ($data['jsonrpc'] == '2.0');
		
		$path = strtolower($data['method']);
		$params = $data['params'];
		
		try {
			if (!$path) throw new API_Exception("Method not found", -32601);

			$path_arr = explode('/', $path);
			$class = 'api_' . implode('_', $path_arr);

			if (Core::load(LIBRARY_BASE, 'api/'.$path, '*') && class_exists($class, FALSE) && method_exists($class, '_default')) {
				$object = new $class();
				$callback = [$object, '_default'];
			}
			else {
				$method = array_pop($path_arr);
				$path = implode('/', $path_arr);
				$class = 'api_' . implode('_', $path_arr);
				if ($method[0] != '_' && count($path_arr) > 0
					&& Core::load(LIBRARY_BASE, 'api/'.$path, '*') && class_exists($class, FALSE) && method_exists($class, $method)
				) {
					$object = new $class();
					$callback = [$object, $method];
				}
			}

			if (!is_callable($callback)) {
				throw new API_Exception("Method not found", -32601);
			}

			if ($this->_debug) {
				$method = $callback[1];
				$params_str = preg_replace('/\[(.*)\]/', '$1', @json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
				Log::add(strtr('<<< %class->%method(%params_str)', [
							'%class' => $class,
							'%method' => $method,
							'%params_str' => $params_str,
				]), 'api');
			}

			$result = call_user_func_array($callback, $params);

			if ($JSONRPC2) {
				$response = [
					'jsonrpc' => '2.0',
					'result' => $result,
					'id' => $data['id'],
				];
			}
			// 向下兼容
			else {
				$response = [
					'success' => TRUE,
					'response' => $result,
				];
			}

			if ($this->_debug) {
				Log::add(strtr('>>> %response', [
							'%response' => @json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
				]), 'api');
			}
			
		}
		catch (API_Exception $e) {
			if ($this->_debug) {
				Log::add($e->getMessage(), 'api');
			}

			if ($JSONRPC2) {
				$response = [
					'jsonrpc' => '2.0',
					'error' => [
						'code' => $e->getCode(),
						'message' => $e->getMessage(),
						],
					'id' => $data['id'],
				];
			}
			// 向下兼容
			else {
				$response = [
					'error' => $e->getMessage(),
				];
			}
		}

		while(ob_end_clean());

        	header('Content-Type: application/json; charset=utf-8');
		echo @json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		exit;
	}
}

class API_Common {

    protected static $errors = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
		404 => 'Not Found'
	];

    protected function _ready ($scope = '*') {
		$client_id = $_SERVER['HTTP_CLIENTID'];
        $client_secret = $_SERVER['HTTP_CLIENTSECRET'];
		if (!$client_id || !$client_secret) throw new API_Exception(self::$errors[401], 401);

		$identity = Config::get("rpc.{$client_id}");
		if ($identity && $identity['client_secret'] == $client_secret) return;

		$identity = Config::get('rpc.identity');
		$secret = $identity[$client_id]['secret'];
		if ($secret != $client_secret) {
			throw new API_Exception(self::$errors[401], 401);
		}

		// ->_ready()调用时仅需检验配置中的scope是否也为*
		if ($scope == '*') {
			if ($identity[$client_id]['scope'] !== ['*']) {
				throw new API_Exception(self::$errors[401], 401);
			}
		// ->_ready(Array)调用时,需检验配置中的Array是scope的子集
		} elseif (is_array($scope)) {
			$diff = array_diff($scope, $identity[$client_id]['scope']);
			if (count($diff)) {
				throw new API_Exception(self::$errors[401], 401);
			}
		// ->_ready(String)调用时,需检验配置中的String是scope的子集 或 scope为*
		} else {
			if ($identity[$client_id]['scope'] !== ['*'] && !in_array($scope, $identity[$client_id]['scope'])) {
				throw new API_Exception(self::$errors[401], 401);
			}
		}
		return;
    }
}
