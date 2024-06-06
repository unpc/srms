<?php

//是否可支持中文token
$config['enable_cn_token'] = FALSE;

$config['backends']['database'] = [
	'handler' => 'database',
	'database.table' => '_auth',
	'title' => '本地用户',
];

$config['default_backend'] = 'database';

/*
$config['backends']['ldap'] = array(
	'title' => 'LDAP',
	'handler' => 'ldap',
	'ldap.token_base' => 'ou=Genee,dc=geneegroup,dc=com',
	'ldap.token_attr' => 'sAMAccountName',
	'ldap.options' => array(
		'host' => 'ldapi://%2Fvar%2Flib%2Fsamba%2Fprivate%2Fldapi',
		'root_dn' =>  'cn=LIMS,cn=Users,dc=geneegroup,dc=com',
		'root_pass' => '83719730',
		'pass_algo' => 'plain',
		'server_type' => 'ads',

		'posix.default_uid' => 2000,
		'posix.default_gid' => 513,

		'samba3.SID' => 'S-1-5-21-2135556578-2076237679-516340727',
		'samba3.groupSID' => 'S-1-5-21-2135556578-2076237679-516340727-513'
		
	),
);

$config['backends']['xxx'] = array(
	'title' => 'xxx',
	'handler' => 'rpc',
	'rpc.url' => '',
);

*/
