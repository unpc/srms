<?php
/*
  使用步骤:
  - LIMS2 升级, 确保 xgc/xgc_cf 的 auth.php/oauth.php 配置正确
  - 暂停 WEB 访问
  - mysqldump lims2_xgc > xgc.sql;
  - mysql lims2_xgc_cf < xgc.sql;
  - SITE_ID=lab LAB_ID=xgc php init_oauth_user.php

  若按此步骤操作成功, 则 xgc 用户可使用 xgc cf OAuth 登录, 且登录后正常

  (xiaopei.li@2013-01-08)
*/
require(dirname(dirname(__FILE__)) . '/base.php');


$n = 0;
$n_ok = 0;
$n_fail = 0;

$oauth_provider = 'xgc_cf';
$oauth_auth_backend = 'xgc_cf';

foreach (Q('user') as $user) {
	$n++;

	// 新建 oauth_user
	$oauth_user = O('oauth_user');
	$oauth_user->server = $oauth_provider;
	$oauth_user->user = $user;
	$oauth_user->remote_id = $user->id;	// 由于 xgc_cf 的初始数据是由 xgc 导入, 所以 remote user id == user id
	if ($oauth_user->save()) {
		// 修改 user token_backend 为 xgc_cf
		// list($token, $backend) = Auth::parse_token($user->token);
		$new_token = Auth::make_token($oauth_user->remote_id, $oauth_auth_backend);
		$user->token = $new_token;
		if ($user->save()) {
			$n_ok++;
			echo $user->name . " ok\n";
		}
		else {
			$n_fail++;
			echo $user->name . " 保存失败\n";
		}
	}
	else {
		$n_fail++;
		echo $user->name . " oauth_user 创建失败\n";
	}
}

echo "=========================\n";
echo "======== 共处理 $n 名用户\n";
echo "======== 成功 $n_ok 名用户\n";
echo "======== 失败 $n_fail 名用户\n";
