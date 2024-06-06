<?php

use \GuzzleHttp\Client;

class HiExam
{
    private static $_supported_methods = ['get', 'post', 'delete', 'put', 'patch'];

	private $_http_options = null;
    public function __construct($options=[])
    {
		if (empty($options)) {
			$options = Config::get('hiexam');
		}
		$this->_http_options = $options;
		if (!$this->_http_options['auth-token']) {
			$this->_http_options['auth-token'] = Auth::token();
		}
    }

	public function setAuthToken($token)
	{
		$this->_http_options['auth-token'] = $token;
		return $this;
	}

	private static function _computeHash($accessToken, $clientSecret)
	{
		if (!$accessToken) return;
		if (L('ME')->gapper_id) {
			$userID = L('ME')->gapper_id;
		} else {
			$logapper = new LoGapper();
			$logapper->setAccessToken($accessToken);
			$result = $logapper->get('current-user');
			if (!$result) return;
			if (!$result['id']) return;
			$userID = $result['id'];
			$me = L('ME');
			if ($me->id) {
				$me->gapper_id = $userID;
				$me->save();
			}
		}
		$salt = '$6$rounds=2000$' . $userID . '$';
		return crypt($clientSecret, $salt);
	}

	// 考试系统要改
    public function __call($method, $params)
    {
        if ($method===__FUNCTION__) return;
		$method = strtolower($method);
        if (in_array($method, self::$_supported_methods)) {
			$accessToken = LoGapper::getUserAccessToken($this->_http_options['auth-token']);
			$headers = [
				'X-Exam-Request-Client'=> trim($this->_http_options['client_id']),
				'X-Exam-Access-Token'=> trim($accessToken),
				'X-Exam-Request-Hash'=> trim(self::_computeHash($accessToken, $this->_http_options['client_secret'])),
			];
			if (in_array($method, ['post', 'put'])) {
				$headers['Content-Type'] = 'application/json';
			}
			$client = new \GuzzleHttp\Client([
				'headers'=> $headers,
				'base_uri'=> $this->_http_options['server'],
				'timeout'=> $this->_http_options['timeout'],
				'http_errors'=> @$this->_http_options['http_errors'] ? true : false,
			]);

			list($path, $kvs, $withHash) = $params;
			if (!is_array($kvs)) $kvs = [];

			switch ($method) {
				case 'post':
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
}