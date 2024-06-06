<?php

// http://xxxx/lims/?oauth2=nrii&equipment_id=804
class OAuth_Client_OAuth2_Nrii extends OAuth_Client_OAuth2
{
    function __construct( $conf )
    {
        parent::__construct( $conf );
        $this->user_endpoint = $conf['user_url'];
    }

    function apicall_current_user()
    {
        // 先获取Oauth access_token才能获取相关资源
        $this->set_token();

        // 获取用户信息
        $response = $this->client->fetch($this->user_endpoint);

        if ($response['code'] == 200 && $response['result']) {
            $user_info = [
                'username' => $response['result']['username'],
                'name' => $response['result']['nickname'],
                'email' => $response['result']['email'],
                'phone' => $response['result']['phone'],
                'organization' => $response['result']['institution'],
            ];
            return $user_info;
        }

        return FALSE;
    }

    function get_token( $info = NULL )
    {
        if (!$info) {

            if ($_SESSION['oauth2_token']) {
                $info = $_SESSION['oauth2_token'];
            }
            else {
                $_SESSION['oauth_refer'] = URI::url(NULL, Input::form());
                $this->authorization_request();
            }
        }

        if (is_string($info)) {
            $info = json_decode($info, TRUE);
        }

        $access_token = $info['access_token'];

        return $access_token;
    }

    function get_authorization_request_url()
    {
        $state = uniqid( 'oauth2_', TRUE );
        $_SESSION['oauth2_state'] = $state;

        // Log::add("@oauth2 req authorization url: $state", 'oauth');

        $extra_parameters = [
            'scope' => 'read',
            'state' => $state,
            ];
        $code_url = $this->client->getAuthenticationUrl(
            $this->authorization_endpoint,
            $this->callback,
            $extra_parameters);

        // Log::add("@oauth2 got authorization url: $code_url", 'oauth');

        return $code_url;
    }
}
