#!/usr/bin/env php
<?php
require '../base.php';

/*
  将 token 符合 nk + 学工号 规则的用户转为一卡通用户,
  其他用户转为数据库用户, 生成随机密码;
*/

$u = new Upgrader;

// 数据库备份文件名
$time = Date::format(NULL, 'YmdHis');

$dbfile = "$time-1-sky_upgrade_user.sql";

$u->check = $u->verify = $u->post_upgrade = function() {
	return TRUE;
};

/* 备份 */
$u->backup = function() use ($dbfile) {
	Upgrader::echo_title('备份');

	$db = Database::factory();
	$db->snapshot($dbfile);
};

/* 还原 */
$u->restore = function() use ($dbfile) {
	Upgrader::echo_title('还原');

	$db = Database::factory();
	$db->restore($dbfile);
};

/* 升级 */
$u->upgrade = function() use ($time) {
	Upgrader::echo_title('升级');
	$db = Database::factory();

	$users = Q('user');

	Upgrader::echo_title('共有' . $users->total_count() . '名用户');

	$output = new CSV("$time-pw.csv", 'w');
	$output->write(['登录名', '密码', '手机']);

	$sql = [];
	$sql['update_user'] = "update user set token=%token, member_type=%type where id=%id";

	foreach ($users as $user) {
		if (preg_match('/\|less.nankai.edu.cn$/', $user->token) ||
			preg_match('/\|database$/', $user->token)) {
			// token 符合规则
			continue;
		}

		Upgrader::echo_title("{$user->name}[{$user->id}]");

		if (preg_match('/^nk(\d+)$/', $user->token, $matches)) {
			Upgrader::echo_title( "\t该用户应升级为一卡通用户");
			$new_token = $matches[1] . '|less.nankai.edu.cn';
			Upgrader::echo_title("\t{$new_token}");
		}
		else {
			Upgrader::echo_title( "\t该用户应升级为数据库用户");

			$new_token = $user->token . '|database';
			Upgrader::echo_title("\t{$new_token}");
			$password = Misc::random_password(6, 1);

			$auth = new Auth($new_token);

			if (!$auth->create($password)) {
				Upgrader::echo_fail( "\t密码创建失败");
				Upgrader::echo_fail( "\t用户更新失败");

				$output->close();
				return FALSE;
			}

			$output->write([$new_token, $password, $user->mobile]);
		}

		$query = strtr($sql['update_user'], [
						   '%token' => $db->quote($new_token),
						   '%type' => $db->quote($user->member_type ? : 'NULL'),
						   '%id' => $db->quote($user->id),
						   ]);

		// Upgrader::echo_title($query);

		$db->query($query);

		if ($db->affected_rows() != 1) {
			Upgrader::echo_fail( "\t用户更新失败");

			$output->close();
			return FALSE;
		}
		else {
			Upgrader::echo_success( "\t用户更新成功");
		}

	}

	$output->close();
	return TRUE;
};

$u->run();