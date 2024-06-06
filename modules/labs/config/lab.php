<?php
$config['deciare'] = 'm²';
$config['cannot_active'] = FALSE;

$config['signup_pass'] = TRUE;
// 列表的搜索不根据系统设置的展示列显隐的字段
$config['search_fields_no_follow_config'] = [
    'lab_name',
    'group',
    'creator',
    'auditor',
];