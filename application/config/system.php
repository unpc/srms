<?php
$config['application'] = 'LIMS2';
$config['syslog'] = TRUE; // 同时使用 syslog 记录日志 (xiaopei.li@2012-10-13)


// TODO 希望有开关能方便地切换所有站点 locale, 以便于 labscout.com 部署
$config['locale'] = 'zh_CN';
$config['locales'] = [ 'zh_CN'=>'中文','en_US'=>'English'];

$config['version'] = 'Release/1.0';

$config['login_page'] = 'login';

$config['allow_register'] = TRUE;

$config['tmp_dir'] = '/tmp/lims/';

$config['email_address'] = 'support@booguo.com';
$config['email_name'] = 'LabScout LIMS';

$config['supported_browsers'] = [
	'safari' => 3.0,
	'firefox' => 3.5,
	'chrome' => 4,
	'opera' => 9,
	'ie' => 9,
];

$path_prefix = preg_replace('/([^\/])$/', '$1/', dirname($_SERVER['SCRIPT_NAME']));

$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$config['base_url'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . $path_prefix;
$config['script_url'] = $scheme . '://' . $_SERVER['HTTP_HOST'] . $path_prefix;

$config['24hour'] = TRUE;

$config['company_link'] = 'http://www.geneegroup.com';
$config['yiqikong_link'] = 'http://www.17kong.com';

$config['customer_service_tel_text'] = '400-017-KONG';

$config['customer_service_tel'] = '400-017-5664';

$config['excel_path'] = '/home/disk/'.$_SERVER['SITE_ID'] . '/' . $_SERVER['LAB_ID'].'/'.'excel/';

$config['heartbeat'] = TRUE;
