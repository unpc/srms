<?php
$base_url = $_SERVER['HTTP_HOST'];
$url_host = parse_url($base_url, PHP_URL_HOST);
if ($url_host) {
    $base_host = parse_url($base_url, PHP_URL_PORT) ? $url_host.':'.parse_url($base_url, PHP_URL_PORT) : $url_host;
}else {
    $base_host = substr($base_url, 0, strrpos($base_url,':') ? : strlen($base_url));
}
//$base_host = parse_url($base_url, PHP_URL_HOST) ? : (substr($base_url, 0, strrpos($base_url,':') ? : strlen($base_url)));
$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $base_host;

$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$host = $scheme . '://' . $_SERVER['HTTP_HOST'];

$config['base_url'] = $host.'/control';

$config['control_user'] = [
    'url'         => 'http://172.17.42.1:4023'
];
