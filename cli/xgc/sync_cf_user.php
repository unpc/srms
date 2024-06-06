<?php
/*
	xgc (定时)从 xgc_cf 同步信息,
	需 xgc_cf 的用户在 xgc 登录过才会同步.
*/

require dirname( dirname(__FILE__) ) . '/base.php';

$provider = 'xgc_cf';

$oauth_client = new OAuth_Client_LIMS2( $provider );

$i = 0;

foreach (Q("oauth_user[server=$provider][remote_id][user][access_token][access_token_secret]") as $oauth_user) {

	$oauth_client->set_token($oauth_user->access_token, $oauth_user->access_token_secret);

	$user_raw_info = $oauth_client->apicall_user();

	$user_info = json_decode($user_raw_info, TRUE);
	$user_info_keys = array_keys($user_info);

	$sync_fields = [
		'name',
		'email',
		'phone',
		'address',
		'gender',
		'member_type',
		'ref_no',
		'major',
		'organization',
		'card_no',
		];

	$user = $oauth_user->user;

	foreach ($sync_fields as $field) {
		if (in_array($field, $user_info_keys)) {
			$user->$field = $user_info[$field];
		}
	}

	if ($user->save()) {
		$i++;
	}

	error_log($user->name);

}
