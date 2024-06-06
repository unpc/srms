<?php
$config['module[accounts].is_accessible'][] = 'Lims_Account_Helper::is_accessible';

$config['is_allowed_to[查看].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[添加].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[修改].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[删除].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[修改版本].lims_account'][] = 'Lims_Account_Helper::account_ACL';

// TODO 账户在显示时会hook一些tab, 如相关treenote任务, 仪器列表等 (xiaopei.li@2011-12-26)

$config['is_allowed_to[列表文件].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[上传文件].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[下载文件].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[修改文件].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[删除文件].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[创建目录].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[修改目录].lims_account'][] = 'Lims_Account_Helper::account_ACL';
$config['is_allowed_to[删除目录].lims_account'][] = 'Lims_Account_Helper::account_ACL';

$config['is_allowed_to[发表评论].lims_account'][] = 'Lims_Account_Helper::comment_ACL';
$config['is_allowed_to[删除].comment'][] = 'Lims_Account_Helper::comment_ACL';

