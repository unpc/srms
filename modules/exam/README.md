通用可配考试系统sites需配置项目

* config/gateway.php
```
$gateway_url = 'http://test.gapper.in/gateway';

$config['login_url'] = $gateway_url.'/oauth/server/auth?client_id=fudan-lims&redirect_uri='.$host_url.'/gapper_login/auth?source=gateway&scope=user&response_type=code&approval_prompt=auto';

$config['oauth'] = [
	'provider'  => '',
	'key'       => 'fudan-lims',
	'secret'    => 'D2FAAFBCB3964DE7BB95BCDD3DD7180F',
	'auth_url'  => $gateway_url.'/oauth/server/auth',
	'token_url' => $gateway_url.'/oauth/server/token',
	'callback'  => $host_url.'/gapper_login/auth?source=gateway',
];
```

* config/logapper.php
```
$config['client_id'] = 'fudan-lims';
$config['client_secret'] = 'D2FAAFBCB3964DE7BB95BCDD3DD7180F';

$gateway_url = 'http://test.gapper.in/gateway';
```

* config/hiexam.php
```
<?php
$host_url = 'http://test-env.labmai.com/labmai';
$config['client_id'] = '17kong';
$config['client_secret'] = 'imAmHQ5YXjHFunL4p9rVvDYsOibGdWSCYoRO0X7d';
```
