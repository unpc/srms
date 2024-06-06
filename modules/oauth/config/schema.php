<?php
// client-side schema
$config['oauth_user'] = [
	// access token
	'fields' => [
		'server' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
		// server 目前按 config/oauth.php $config['clients'] 的 key 设置,
		// 以后 server 可能要存到数据库中, 并提供新增 server/client 的 controller
		// (xiaopei.li@2012-12-18)
		'remote_id' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''], // 用户在 provider 处的唯一标示(ID), 此项可选
		'user' => ['type' => 'object', 'oname' => 'user'],
		'version' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], // oauth 1 or 2
		'access_token' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // for 1 and 2
		'access_token_secret' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // for 1
		'expires_in' => ['type' => 'bigint', 'null' => FALSE, 'default' => 0], // for 2
		'refresh_token' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // for 2
		],
	'indexes' => [
		'unique' => ['type' => 'unique', 'fields' => ['server', 'remote_id']], // pending, remote_id 不一定有, 但若要单点登录, 则 remote_id 必须有;
		// 'unique' => array('type' => 'unique', 'fields' => array('server', 'user', 'remote_id')), // pending, 用户可否在一个 server 绑多个账号?
		'user' => ['fields' => ['user']],
		],
];

// server-side schema
// 目前 consumer 皆存在配置中, 以后也许需建立 consumer 类
$config['oauth_consumer_nonce'] = [
	'fields' => [
		'consumer' => ['type' => 'varchar(50)', 'null' => false],
		'timestamp' => ['type' => 'bigint', 'null' => false],
		'nonce' => ['type' => 'varchar(255)', 'null' => false],
		],
	'indexes' => [
		],
];

$config['oauth_token'] = [
	'fields' => [
		'consumer' => ['type' => 'varchar(50)', 'null' => false],
		'type' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0],
		'token' => ['type' => 'varchar(255)', 'null' => false],
		'token_secret' => ['type' => 'varchar(255)', 'null' => false],
		'verifier' => ['type' => 'varchar(255)', 'null' => false],
		'callback_url' => ['type' => 'text'],
		'user' => ['type' => 'object', 'oname' => 'user'],
	],
	'indexes' => [
		'unique' => ['type' => 'unique', 'fields' => ['token']],
		'user' => ['fields' => ['user']],
	]
];

$config['oauth2_session'] = [
	'fields' => [
		'client_id' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''],
		'redirect_uri' => ['type' => 'varchar(255)', 'default' => ''],
		'type' => ['type' => 'varchar(63)', 'null' => FALSE, 'default' => 'user'], // user or client, but what client type is?
		'type_id' => ['type' => 'int', 'null' => TRUE, 'default' => 0],
		// 'client' => array('type' => 'type' => 'object', 'oname' => 'oauth2_client'),
		'auth_code' => ['type' => 'varchar(255)', 'null' => TRUE, 'default' => ''],
		'access_token' => ['type' => 'varchar(255)', 'null' => TRUE, 'default' => ''],
		'refresh_token' => ['type' => 'varchar(255)', 'null' => TRUE, 'default' => ''],
		'access_token_epires' => ['type' => 'bigint', 'null' => TRUE, 'default' => 0],
		'stage' => ['type' => 'varchar(63)', 'default' => 'requested'], // requested or granted
		'first_requested' => ['type' => 'bigint', 'null' => TRUE, 'default' => 0],
		'last_updated' => ['type' => 'bigint', 'null' => TRUE, 'default' => 0],
		'scopes' => ['type' => 'text'],
		],
	'indexes' => [
		'client_id' => ['fields' => ['client_id']],
		],
	];


