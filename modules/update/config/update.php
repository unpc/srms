<?php

$config['query.fetch_all'] = 
"SELECT u.* FROM `update` u
	INNER JOIN `follow` f ON u.object_id = f.object_id
	WHERE u.object_id = f.object_id
		AND u.object_name = f.object_name
		AND u.id NOT IN (
			SELECT id2 FROM `_r_user_update` WHERE id1 = %uid AND type = 'read'
		)
		AND f.user_id = %uid
		ORDER BY ctime DESC";

$config['query.fetch_partial'] = 
"SELECT u.* FROM `update` u
	INNER JOIN `follow` f ON u.object_id = f.object_id
	WHERE u.object_id = f.object_id
		AND u.object_name = f.object_name
		AND u.id NOT IN (
			SELECT id2 FROM `_r_user_update` WHERE id1 = %uid AND type = 'read'
		)
		AND f.user_id = %uid AND u.object_name='%oname'
		ORDER BY ctime DESC";

$config['query.count_updates'] = 
"SELECT COUNT(DISTINCT u.id) FROM  `update` u
	INNER JOIN `follow` f ON u.object_id = f.object_id
	WHERE u.object_id = f.object_id
		AND u.object_name = f.object_name
		AND u.id NOT IN (
			SELECT id2 FROM `_r_user_update` WHERE id1 = %uid AND type = 'read'
		)
		AND f.user_id = %uid";

/* TASK #1303::Update更新需要能够清除全部(kai.wu@2011.08.15) */	
$config['query.delete_partial'] =
"INSERT INTO `_r_user_update` (`id1`, `id2`, `type`, `approved`)
	SELECT %uid, u.id, 'read', 0 FROM `update` u
	INNER JOIN `follow` f ON u.object_id = f.object_id
	WHERE u.object_id = f.object_id
		AND u.id NOT IN (
			SELECT id2 FROM `_r_user_update` WHERE id1 = %uid AND type = 'read'
		)
		AND u.object_name = f.object_name
		AND f.user_id = %uid AND u.object_name='%oname'
	ON DUPLICATE KEY UPDATE `approved`=0";
	
$config['query.delete_all'] =
"INSERT INTO `_r_user_update` (`id1`, `id2`, `type`, `approved`)
	SELECT %uid, u.id, 'read', 0 FROM `update` u
	INNER JOIN `follow` f ON u.object_id = f.object_id
	WHERE u.object_id = f.object_id
		AND u.id NOT IN (
			SELECT id2 FROM `_r_user_update` WHERE id1 = %uid AND type = 'read'
		)
		AND u.object_name = f.object_name AND f.user_id = %uid
	ON DUPLICATE KEY UPDATE `approved`=0";

$config['auto_read_time'] = 5000;	//自动阅读时间设置
