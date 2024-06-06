<?php

class REST_Exception extends Exception {}

class REST {

	private $_url;
	private $_path;
    private $_version;
    private $_header = [];
    

    //用于存储cookie_file对象
    private $_cookie_file;
	
	function __construct($url, $path=NULL, Cookie_File $cookie_file = NULL) {
		$this->_url = $url;
		$this->_path = $path;

        if ($cookie_file === NULL) {
            $this->_cookie_file = new Cookie_File;
        }
        else {
            $this->_cookie_file = $cookie_file;
        }
	}

	function __get($name) {
		return new REST($this->_url, $this->_path ? $this->_path . '/' . $name : $name, $this->_cookie_file);
	}

	function __call($method, $params) {
		if ($method === __FUNCTION__) return NULL;

        if ($this->_path) {
            $path = '/' . $this->_path;
        }
        $path .= '/' . array_shift($params);

		if ($this->_path) $method = $this->_path . '/' . $method;

		$id = uniqid();
        
		$raw_data = $this->action([
            'requset' => strtoupper($method),
            'params' => $params[0],
            'path' => $path,
		]);

        $data = @json_decode($raw_data, true);
        if (isset($data['error'])) {
            $message = sprintf('remote error: %s', $data['error']['message']);
            $code = $data['error']['code'];
				throw new REST_Exception($message, $code);
        } 
        elseif (is_null($data) && !array_key_exists('total', (array) $data)) {
            $message = sprintf('unknown error with raw data: %s', $raw_data ?: '(null)');
				throw new REST_Exception($message, 404);
        }

        if(is_array($data) && !empty($data['total'])){
            unset($data['total']); // 这里没有做通用处理所以先unset掉
        }
		return $data;
	}

	function action($post_data, $timeout = 5) {
		$cookie_file = $this->_cookie_file->path;        
        
        $ch = curl_init();

        $this->_header['Accept'] = "application/{$this->_url}+json; version={$this->_version}"; 
        // convert to Key: Value format
        $header = function($array) {
            $response = [];
            foreach (array_merge($array, $this->_header) as $key => $value) {
                $response[] = "$key: $value";
            }
            return $response;
        };
        $query = (string)http_build_query($post_data['params'] ? : []);

        $options = [
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_AUTOREFERER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ? : 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
        ];
        curl_setopt_array($ch, $options);

        switch ($post_data['requset']) {
            case 'PUT':
            case 'PATCH':
                $this->_header['Content-Type'] = 'application/x-www-form-urlencoded';
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->_url . '/' . $post_data['path'],
                    CURLOPT_POSTFIELDS => $query,
                    CURLOPT_CUSTOMREQUEST => $post_data['requset']
                ]);
                break;
            case 'POST':
                parse_str($query, $output);
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->_url . '/' . $post_data['path'],
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $output,
                ]);
                break;
            default:
                $this->_header['Content-Type'] = 'application/json';
                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST => $post_data['requset'],
                    CURLOPT_URL => $this->_url . '/' . $post_data['path'] . '?' . $query,
                ]);
                break;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        
		$data = curl_exec($ch);
		
		$errno = curl_errno($ch);
		if ($errno) {
			$err = curl_error($ch);
			error_log("CURL ERROR: $err");
			$data = NULL;
		}

		curl_close($ch);
		return $data;
	}
}
