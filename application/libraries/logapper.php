<?php

use \GuzzleHttp\Client;

class LoGapper
{
    private static $_supported_methods = ['get', 'post', 'delete', 'put', 'patch'];

	private $_http_options = null;
    public function __construct()
    {
		$this->_http_options = Config::get('logapper');
    }

	public function setAccessToken($token)
	{
		$this->_http_options['access-token'] = $token;
		return $this;
	}

    public function __call($method, $params)
    {
        if ($method===__FUNCTION__) return;
		$method = strtolower($method);
        if (in_array($method, self::$_supported_methods)) {
			if ($params[0] != 'auth/refresh-token') {
				$headers = [
					'X-Gapper-OAuth-Token'=> $this->_http_options['access-token'] ?: self::getUserAccessToken(Auth::token()),
				];
			}
			if (in_array($method, ['post', 'put'])) {
				$headers['Content-Type'] = 'application/json';
			}
			$client = new \GuzzleHttp\Client([
				'headers'=> $headers,
				'base_uri'=> $this->_http_options['server'],
				'timeout'=> $this->_http_options['timeout'],
				'http_errors'=> @$this->_http_options['http_errors'] ? true : false,
			]);

			list($path, $kvs, $withClientAuth) = $params;
			if (!is_array($kvs)) $kvs = [];
			if ($withClientAuth) {
				$kvs['client_id'] = $this->_http_options['client_id'];
				$kvs['client_secret'] = $this->_http_options['client_secret'];
			};
			switch ($method) {
				case 'post':
				case 'put':
					$kvs = [
						'body'=> json_encode($kvs, JSON_UNESCAPED_UNICODE)
					];
					break;
				case 'get':
					$kvs = [
						'query'=> $kvs
					];
					break;
				default:
			}

			$data = @json_decode($client->$method($path, $kvs)->getBody()->getContents(), true);

            return $data;
        }
    }

	private static function _getAccessCacheKey($token)
	{
		return "logapper.{$token}.access-token";
	}

	private static function _getRefreshCacheKey($token)
	{
		return "logapper.{$token}.refresh-token";
	}

	public static function getUserAccessToken($userToken)
	{
		if (!$userToken) return;

		$cache = Cache::factory('redis');
		$cacheAccessKey = self::_getAccessCacheKey($userToken);
		$accessToken = $cache->get($cacheAccessKey);
		if ($accessToken) return $accessToken;

		$cacheRefreshKey = self::_getRefreshCacheKey($userToken);
		$refreshToken = $cache->get($cacheRefreshKey);
		if (!$refreshToken) return;

		$logapper = new LoGapper();
		$result = $logapper->post('auth/refresh-token', [
			'refresh_token'=> $refreshToken
		], true);
		if (!is_array($result)) $result = @json_decode($result, true);
		if (!$result) return;
		if (!$result['access_token']) return;
		self::cacheUserOAuthToken($userToken, $result['access_token'], $result['expires_in']);
		return $result['access_token'];
	}

	public static function cacheUserOAuthToken($userToken, $accessToken, $accessTokenExpiresIN, $refreshToken=null)
	{
		if (!$userToken) return false;
		$cache = Cache::factory('redis');
		$cacheAccessKey = self::_getAccessCacheKey($userToken);
		$acT = (int)$accessTokenExpiresIN - 100;
		$cache->set($cacheAccessKey, $accessToken, $acT);
		if ($refreshToken) {
			$rfT = Config::get('logapper.refresh-token-timeout') ?: 604800;
			$cacheRefreshKey = self::_getRefreshCacheKey($userToken);
			$cache->set($cacheRefreshKey, $refreshToken, $rfT);
		}
	}

	public static function login_view($e) {
		$e->return_value = V('gapper_login');
	}
}
