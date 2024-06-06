<?php

/*
 * @file   q_query.php
 * @author RUI MA<rui.ma@geneegroup.com>
 * @date   2011-11-15
 *
 * @brief  测试q语句是否可正常转化成sql语句
 * @usage  SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/00-system/q_query
 *
 */
//涉及到billing lab等模块，cf以外的模块跳出
if (SITE_ID != 'cf') return true;
$q_query_and_sql_query_array = [
    [
        'user',
        'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`id`'
    ],
    [
        'user#1',
        "SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`id`='1' ORDER BY `t0`.`id`"
    ],
    [
    	'user:limit(5)',
    	'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`id` LIMIT 5'
    ],
    [
    	'user:limit(0,10)',
    	'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`id` LIMIT 0,10'
    ],
    [
    	'user[name]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE (`t0`.`name` IS NOT NULL AND `t0`.`name` != '') ORDER BY `t0`.`id`"
    ],
    [
    	'user[name=技术支持]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`name`='技术支持' ORDER BY `t0`.`id`"
    ],
    [
    	'user[name*=支持]',
    	'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`name` LIKE "%%支持%%" ORDER BY `t0`.`id`'
    ],
    [
    	'user[name!=支持]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`name`!='支持' ORDER BY `t0`.`id`"
    ],
    [
    	'user[atime>1]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`atime`>'1' ORDER BY `t0`.`id`"
    ],
    [
    	'user[name][atime>1]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE ((`t0`.`name` IS NOT NULL AND `t0`.`name` != '') AND `t0`.`atime`>'1') ORDER BY `t0`.`id`"
    ],
    [
    	'user[atime=0~1000]',
    	"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE (`t0`.`atime`>='0' AND `t0`.`atime`<='1000') ORDER BY `t0`.`id`"
    ],
    [
    	'user[name^=技术]',
    	'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`name` LIKE "技术%%" ORDER BY `t0`.`id`'
    ],
    [
    	'user[name$=支持]',
    	'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`name` LIKE "%%支持" ORDER BY `t0`.`id`'
	],
	[
		'user[id=1,2,3]',
		"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE (`t0`.`id`='1' OR `t0`.`id`='2' OR `t0`.`id`='3') ORDER BY `t0`.`id`"
	],
	[
		'user[id=1,2,3|atime>3]',
		"SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE ((`t0`.`id`='1' OR `t0`.`id`='2' OR `t0`.`id`='3') OR `t0`.`atime`>'3') ORDER BY `t0`.`id`"
	],
	[
		'user:sort(name)',
		'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`name` ASC'
	],
	[
		'user:sort(name D)',
		'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`name` DESC'
	],
	[
		'user:sort(name atime D)',
		'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` ORDER BY `t0`.`name` ASC, `t0`.`atime` DESC'
	],
	[
		'user lab',
		'SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`hidden`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`user` `t0`) ON `t0`.`lab_id`=`t1`.`id` GROUP BY `t1`.`id` ORDER BY `t1`.`id`'
	],
	[
		'lab user',
		'SELECT `t1`.`token`, `t1`.`email`, `t1`.`name`, `t1`.`card_no`, `t1`.`card_no_s`, `t1`.`dfrom`, `t1`.`dto`, `t1`.`weight`, `t1`.`atime`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`hidden`, `t1`.`name_abbr`, `t1`.`phone`, `t1`.`address`, `t1`.`group_id`, `t1`.`member_type`, `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`ref_no`, `t1`.`binding_email`, `t1`.`lab_id`, `t1`.`lab_abbr`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`wechat_bind_status`, `t1`.`wechat_openid`, `t1`.`gapper_id`, `t1`.`nl_cat_vis`, `t1`.`id`, `t1`.`_extra` FROM `user` `t1` INNER JOIN (`lab` `t0`) ON `t1`.`lab_id`=`t0`.`id` GROUP BY `t1`.`id` ORDER BY `t1`.`id`'
	],
	[
		'lab#0 user',
		"SELECT `t1`.`token`, `t1`.`email`, `t1`.`name`, `t1`.`card_no`, `t1`.`card_no_s`, `t1`.`dfrom`, `t1`.`dto`, `t1`.`weight`, `t1`.`atime`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`hidden`, `t1`.`name_abbr`, `t1`.`phone`, `t1`.`address`, `t1`.`group_id`, `t1`.`member_type`, `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`ref_no`, `t1`.`binding_email`, `t1`.`lab_id`, `t1`.`lab_abbr`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`wechat_bind_status`, `t1`.`wechat_openid`, `t1`.`gapper_id`, `t1`.`nl_cat_vis`, `t1`.`id`, `t1`.`_extra` FROM `user` `t1` INNER JOIN (`lab` `t0`) ON (`t0`.`id`='0' AND `t1`.`lab_id`=`t0`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		'lab publication',
		'SELECT `t1`.`title`, `t1`.`journal`, `t1`.`date`, `t1`.`volume`, `t1`.`issue`, `t1`.`page`, `t1`.`author`, `t1`.`lab_id`, `t1`.`content`, `t1`.`notes`, `t1`.`impact`, `t1`.`id`, `t1`.`_extra` FROM `publication` `t1` INNER JOIN (`lab` `t0`) ON `t1`.`lab_id`=`t0`.`id` GROUP BY `t1`.`id` ORDER BY `t1`.`id`'
	],
	[
		'user#1<owner lab',
		"SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`hidden`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`user` `t0`) ON (`t0`.`id`='1' AND `t1`.`owner_id`=`t0`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		'lab#9 user.owner',
		"SELECT `t1`.`token`, `t1`.`email`, `t1`.`name`, `t1`.`card_no`, `t1`.`card_no_s`, `t1`.`dfrom`, `t1`.`dto`, `t1`.`weight`, `t1`.`atime`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`hidden`, `t1`.`name_abbr`, `t1`.`phone`, `t1`.`address`, `t1`.`group_id`, `t1`.`member_type`, `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`ref_no`, `t1`.`binding_email`, `t1`.`lab_id`, `t1`.`lab_abbr`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`wechat_bind_status`, `t1`.`wechat_openid`, `t1`.`gapper_id`, `t1`.`nl_cat_vis`, `t1`.`id`, `t1`.`_extra` FROM `user` `t1` INNER JOIN (`lab` `t0`) ON (`t0`.`id`='9' AND `t0`.`owner_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		'user#1<lab lab',
		"SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`hidden`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`user` `t0`) ON (`t0`.`id`='1' AND `t0`.`lab_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		'user:not([name*=支持])',
		'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`id` NOT IN (SELECT `t1`.`id` FROM `user` `t1` WHERE `t1`.`name` LIKE "%%支持%%") ORDER BY `t0`.`id`'
	],
	[
		'user:not(user[name*=支持])',
		'SELECT `t0`.`token`, `t0`.`email`, `t0`.`name`, `t0`.`card_no`, `t0`.`card_no_s`, `t0`.`dfrom`, `t0`.`dto`, `t0`.`weight`, `t0`.`atime`, `t0`.`ctime`, `t0`.`mtime`, `t0`.`hidden`, `t0`.`name_abbr`, `t0`.`phone`, `t0`.`address`, `t0`.`group_id`, `t0`.`member_type`, `t0`.`creator_id`, `t0`.`auditor_id`, `t0`.`ref_no`, `t0`.`binding_email`, `t0`.`lab_id`, `t0`.`lab_abbr`, `t0`.`nfs_size`, `t0`.`nfs_mtime`, `t0`.`nfs_used`, `t0`.`wechat_bind_status`, `t0`.`wechat_openid`, `t0`.`gapper_id`, `t0`.`nl_cat_vis`, `t0`.`id`, `t0`.`_extra` FROM `user` `t0` WHERE `t0`.`id` NOT IN (SELECT `t1`.`id` FROM `user` `t1` WHERE `t1`.`name` LIKE "%%支持%%") ORDER BY `t0`.`id`',
	],
	[
		"user#1 lab",
		"SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`hidden`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`user` `t0`) ON (`t0`.`id`='1' AND `t0`.`lab_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		"user#1 billing_department",
		"SELECT `t1`.`name`, `t1`.`group_id`, `t1`.`mtime`, `t1`.`nickname`, `t1`.`id`, `t1`.`_extra` FROM `billing_department` `t1` INNER JOIN (`_r_user_billing_department` r2, `user` `t0`) ON (`t0`.`id`='1' AND `r2`.`type`=\"\" AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`",
	],
	[
		"user#1 lab publication",
		"SELECT `t3`.`title`, `t3`.`journal`, `t3`.`date`, `t3`.`volume`, `t3`.`issue`, `t3`.`page`, `t3`.`author`, `t3`.`lab_id`, `t3`.`content`, `t3`.`notes`, `t3`.`impact`, `t3`.`id`, `t3`.`_extra` FROM `publication` `t3` INNER JOIN (`lab` `t1`, `user` `t0`) ON (`t0`.`id`='1' AND `t0`.`lab_id`=`t1`.`id` AND `t3`.`lab_id`=`t1`.`id`) GROUP BY `t3`.`id` ORDER BY `t3`.`id`"
	],
	[
		"billing_account lab#1",
		"SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`hidden`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`billing_account` `t0`) ON (`t1`.`id`='1' AND `t0`.`lab_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		"billing_account[lab=lab#1]<department billing_department",
		"SELECT `t1`.`name`, `t1`.`group_id`, `t1`.`mtime`, `t1`.`nickname`, `t1`.`id`, `t1`.`_extra` FROM `billing_department` `t1` INNER JOIN (`billing_account` `t0`) ON (`t0`.`lab_id`='1' AND `t0`.`department_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		"billing_account[lab=lab#1]<department billing_department:limit(5)",
		"SELECT `t1`.`name`, `t1`.`group_id`, `t1`.`mtime`, `t1`.`nickname`, `t1`.`id`, `t1`.`_extra` FROM `billing_department` `t1` INNER JOIN (`billing_account` `t0`) ON (`t0`.`lab_id`='1' AND `t0`.`department_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id` LIMIT 5"
	],
	[
		"billing_account[lab=lab#1]<department billing_department:limit(0, 10)",
		"SELECT `t1`.`name`, `t1`.`group_id`, `t1`.`mtime`, `t1`.`nickname`, `t1`.`id`, `t1`.`_extra` FROM `billing_department` `t1` INNER JOIN (`billing_account` `t0`) ON (`t0`.`lab_id`='1' AND `t0`.`department_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id` LIMIT 0, 10"
	],
	[
		"billing_account[lab=lab#1]<department billing_department[name=财务部门]",
		"SELECT `t1`.`name`, `t1`.`group_id`, `t1`.`mtime`, `t1`.`nickname`, `t1`.`id`, `t1`.`_extra` FROM `billing_department` `t1` INNER JOIN (`billing_account` `t0`) ON (`t0`.`lab_id`='1' AND `t1`.`name`='财务部门' AND `t0`.`department_id`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t1`.`id`"
	],
	[
		"(user#1, billing_account[lab=lab#1]<department) billing_department",
		"SELECT `t2`.`name`, `t2`.`group_id`, `t2`.`mtime`, `t2`.`nickname`, `t2`.`id`, `t2`.`_extra` FROM `billing_department` `t2` INNER JOIN (`_r_user_billing_department` r4, `user` `t0`) ON (`t0`.`id`='1' AND `r4`.`type`=\"\" AND `r4`.`id1`=`t0`.`id` AND `r4`.`id2`=`t2`.`id`) INNER JOIN (`billing_account` `t1`) ON (`t1`.`lab_id`='1' AND `t1`.`department_id`=`t2`.`id`) GROUP BY `t2`.`id` ORDER BY `t2`.`id`"
	],
	//【Pull requests】Q语句排序增加表选择 sort tablename.field_name
	// #42 opened 17 days ago by diguangzhao
	[
		'user:sort(user.name_abbr) lab',
		'SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`ref_no`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`hidden`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`_r_user_lab` r2, `user` `t0`) ON (`r2`.`type`="" AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t0`.`name_abbr` ASC'
	],
	[
		'user lab:sort(user.name_abbr)',
		'SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`ref_no`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`hidden`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`_r_user_lab` r2, `user` `t0`) ON (`r2`.`type`="" AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t0`.`name_abbr` ASC'
	],
	[
		'user:sort(user.name_abbr user.atime D) lab',
		'SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`ref_no`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`hidden`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`_r_user_lab` r2, `user` `t0`) ON (`r2`.`type`="" AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t0`.`name_abbr` ASC, `t0`.`atime` DESC'
	],
	[
		'user lab:sort(user.name_abbr user.atime D)',
		'SELECT `t1`.`creator_id`, `t1`.`auditor_id`, `t1`.`owner_id`, `t1`.`ref_no`, `t1`.`name`, `t1`.`description`, `t1`.`rank`, `t1`.`ctime`, `t1`.`mtime`, `t1`.`atime`, `t1`.`name_abbr`, `t1`.`contact`, `t1`.`group_id`, `t1`.`hidden`, `t1`.`nfs_size`, `t1`.`nfs_mtime`, `t1`.`nfs_used`, `t1`.`id`, `t1`.`_extra` FROM `lab` `t1` INNER JOIN (`_r_user_lab` r2, `user` `t0`) ON (`r2`.`type`="" AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id`) GROUP BY `t1`.`id` ORDER BY `t0`.`name_abbr` ASC, `t0`.`atime` DESC'
	],
	[
		'( user, user#172 equipment.incharge ) eq_charge[amount!=0]:sort(user.name_abbr A)',
		"SELECT `t4`.`user_id`, `t4`.`lab_id`, `t4`.`equipment_id`, `t4`.`status`, `t4`.`ctime`, `t4`.`mtime`, `t4`.`dtstart`, `t4`.`dtend`, `t4`.`auto_amount`, `t4`.`amount`, `t4`.`custom`, `t4`.`transaction_id`, `t4`.`is_locked`, `t4`.`source_name`, `t4`.`source_id`, `t4`.`description`, `t4`.`id`, `t4`.`_extra` FROM `eq_charge` `t4` INNER JOIN (`equipment` `t1`, `_r_user_equipment` r2, `user` `t0`) ON (`t0`.`id`='172' AND `r2`.`type`='incharge' AND `r2`.`id1`=`t0`.`id` AND `r2`.`id2`=`t1`.`id` AND `t4`.`amount`!='0' AND `t4`.`equipment_id`=`t1`.`id`) INNER JOIN (`user` `t3`) ON (`t4`.`amount`!='0' AND `t4`.`user_id`=`t3`.`id`) GROUP BY `t4`.`id` ORDER BY `t3`.`name_abbr` ASC"
	],
	[
		'(billing_department#1<department,lab[hidden=0]) billing_account<account billing_transaction[income!=0|outcome!=0]:sort(lab.name_abbr A)',
		"SELECT `t5`.`account_id`, `t5`.`user_id`, `t5`.`reference_id`, `t5`.`status`, `t5`.`income`, `t5`.`outcome`, `t5`.`ctime`, `t5`.`mtime`, `t5`.`certificate`, `t5`.`source`, `t5`.`voucher`, `t5`.`manual`, `t5`.`transfer`, `t5`.`id`, `t5`.`_extra` FROM `billing_transaction` `t5` INNER JOIN (`billing_account` `t2`) ON ((`t5`.`income`!='0' OR `t5`.`outcome`!='0') AND `t5`.`account_id`=`t2`.`id`) INNER JOIN (`billing_department` `t0`) ON (`t0`.`id`='1' AND `t2`.`department_id`=`t0`.`id`) INNER JOIN (`lab` `t1`) ON (`t1`.`hidden`='0' AND `t2`.`lab_id`=`t1`.`id`) GROUP BY `t5`.`id` ORDER BY `t1`.`name_abbr` ASC"
	]
];


require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

Environment::init_site();

$db = Database::factory();

foreach ($q_query_and_sql_query_array as $q_query_and_sql_query) {

    $q_query = $q_query_and_sql_query[0];
    $sql_query = $q_query_and_sql_query[1];

	$q_maker = new Q_Query($db);
	$q_maker->reset_table_counter();
    $q_maker->parse_selector($q_query);
    $q_maker->makeSQL();

	Unit_Test::assert($q_query, trim($q_maker->SQL) === trim($sql_query), T("\n\t期望: %sql_query \n\t实际: %new_sql_query\n", ['%q_query'=>$q_query, '%sql_query'=>$sql_query, '%new_sql_query'=>$q_maker->SQL]));

}
