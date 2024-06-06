<?php

use \GuzzleHttp\Client;

class Remote_Billing_Manage {
	private static function keygen($len = 40)
    {
        $bytes = openssl_random_pseudo_bytes($len * 2, $strong);
        if ($bytes === false || $strong === false) {
            throw new \Exception('Error Generating Key');
        }

        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $len);
    }

	public static function getAuthToken()
    {
		$user = L("ME");
        if (!$user->id) {
            if (PHP_SAPI != 'cli') {
                Auth::logout();
                URI::redirect('/');
            }
            if (Module::is_installed('gateway')) {
                return Gateway::getToken();
            }
            return null;
        } 
		
		$cache = Cache::factory('redis');
		$token = self::keygen();
		$cache->set($token, $user->id, 60 * 60 * 2);
		return $token;
    }

	public static function remote($url, $method = 'GET', $options = [])
    {
        try {
			$server = Config::get('billing_manage.server');
			$url = "{$server["url"]}/$url";
            $client   = new \GuzzleHttp\Client(['timeout' => 30]);
			$options['headers'] = ['authorization' => self::getAuthToken()];
            $response = $client->request($method, $url, $options);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                return $response;
            }
        } catch (Exception $e) {
        }
        return false;
    }

	public static function build_query($params)
    {
        $str    = '';
        $i      = 0;
        $length = count($params);
        foreach ($params as $key => $value) {
            $str .= "$key=$value";
            if ($i < $length - 1) {
                $str .= '&';
            }
            $i++;
        }
        return $str;
    }

	public static function callRemote($key, $params)
    {
		$funcs = [
			"getFunds" => ["path" => "funds", "method" => "get"],
			"postTopic" => ["path" => "topic", "method" => "post"],
            "getFund" => ["path" => "fund/".$params['path']['fundId'], "method" => "get"],
		];
        if(isset($params['path'])) unset($params['path']);
		$func = $funcs[$key];
		$response = [];
		try {
			$server = Config::get('billing_manage.server');
			$rest = new Client(['base_uri' => $server["url"]]);
			$method = $func['method'];
			$url = $func["path"];
			switch (strtoupper($func['method'])) {
				case 'GET':
					$url .= '?' . static::build_query($params);
					break;
			}
			$response = $rest->$method($url, [
				'json' => $params,
				'form_params' => $params,
				'headers' => [
					'authorization' => self::getAuthToken()
				]
			]);
			$body = $response->getBody();
			$response = json_decode($body->getContents(), true);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
        return $response;
    }
}

