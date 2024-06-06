<?php
$host_url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$host_url .= '/lims';
$gateway_url = 'http://uno.test.gapper.in/gapper/gateway';
$uno_url = 'http://uno.test.gapper.in/uno';

$config['login_url'] = $uno_url.'/oauth/authorize?client_id=lims&redirect_uri='.$host_url.'/gapper_login/auth?source=gateway&scope=user&response_type=code&approval_prompt=auto';
$config['logout_url'] = $uno_url . '/#/logout?redirect_uri=' . $host_url;
$config['signup_url'] = $uno_url . '/#/signup';

$config['oauth'] = [
       'provider'  => '',
       'key'       => 'lims',
       'secret'    => 'c088c68c66bf6b83ed48fa768d737438',
       'auth_url'  => $gateway_url.'/oauth/server/auth',
       'token_url' => $gateway_url.'/oauth/server/token',
       'callback'  => $host_url.'/gapper_login/auth?source=gateway',
];

$config['gateway'] = [
       'get_user_url'    => $gateway_url.'/api/v1/auth',
       'get_user_detail' => $gateway_url.'/api/v1/current-user'
];
