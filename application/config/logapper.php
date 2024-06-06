<?php

$config['refresh-token-timeout'] = 604800; // 7天
$config['server'] = '';
$config['timeout'] = 5;

$config['client_id'] = 'fudan-lims';
$config['client_secret'] = 'D2FAAFBCB3964DE7BB95BCDD3DD7180F';

$scheme = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
$host = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$gateway_url = $host . '/gateway';

$config['server'] = $gateway_url.'/api/v1/';
$config['oauth_server'] = $gateway_url.'/oauth/server/auth?client_id=%s&scope=user&response_type=code&approval_prompt=auto&redirect_uri=%s';
$config['oauth_auth_url'] = $gateway_url.'/oauth/server/auth';
$config['oauth_token_url'] = $gateway_url.'/oauth/server/token';
$config['oauth_user_url'] = $gateway_url.'/oauth/server/user';

$config['oauth_logout_url'] = $gateway_url . '/logout?redirect=' . urlencode("{$host}");
