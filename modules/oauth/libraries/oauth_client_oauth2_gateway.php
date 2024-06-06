<?php

class OAuth_Client_OAuth2_Gateway extends OAuth_Client_OAuth2 {

	function __construct( $conf ) {
		parent::__construct( $conf );

		$this->api_base_url = $conf['api_url'];
	}

	function apicall_current_user() {
        // 先获取Oauth access_token才能获取相关资源
        $this->set_token();

        // 获取用户信息
        $response = $this->client->fetch($this->api_base_url);

        if ($response['code'] == 200 && $response['result']['username'] != '') {
            $user_info = [
                'username' => $response['result']['username'],
            ];
            return $user_info;
        }

        return FALSE;
    }
}
