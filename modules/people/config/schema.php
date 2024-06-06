<?php

$config['user']['fields'] = (array) $config['user']['fields'] + [
        'token'         => ['type' => 'varchar(150)', 'null' => true],
        'email'         => ['type' => 'varchar(150)', 'null' => true, 'default' => null],
        'name'          => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'card_no'       => ['type' => 'varchar(150)', 'null' => true],
        'card_no_s'     => ['type' => 'varchar(150)', 'null' => true],
        'dfrom'         => ['type' => 'bigint', 'null' => false, 'default' => 0],
        'dto'           => ['type' => 'bigint', 'null' => false, 'default' => 0],
        'weight'        => ['type' => 'int', 'null' => false, 'default' => 0],
        'atime'         => ['type' => 'bigint', 'null' => false, 'default' => 0],
        'ctime'         => ['type' => 'bigint', 'null' => false, 'default' => 0],
        'mtime'         => ['type' => 'bigint', 'null' => false, 'default' => 0],
        'hidden'        => ['type' => 'tinyint', 'null' => false, 'default' => 0],
        'name_abbr'     => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'phone'         => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'address'       => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'address_abbr'  => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'group'         => ['type' => 'object', 'oname' => 'tag_group'],
        'member_type'   => ['type' => 'int', 'null' => true],
        'creator'       => ['type' => 'object', 'oname' => 'user'],
        'creator_abbr'  => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'auditor'       => ['type' => 'object', 'oname' => 'user'],
        'auditor_abbr'  => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'ref_no'        => ['type' => 'varchar(40)', 'null' => true, 'default' => null],
        'binding_email' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'approval'      => ['type' => 'tinyint', 'null' => false, 'default' => 0],
];

$config['user']['indexes'] = (array) $config['user']['indexes'] + [
        'token'     => ['type' => 'unique', 'fields' => ['token']],
        'email'     => ['type' => 'unique', 'fields' => ['email']],
        'card_no'   => ['type' => 'unique', 'fields' => ['card_no']],
        // 短卡号目前可能因为长卡号后6位的相同导致无法正常保存进来用户
        // 'card_no_s'=>['type'=>'unique', 'fields'=>['card_no_s']],
        'card_no_s' => ['fields' => ['card_no_s']],
        'ref_no'    => ['type' => 'unique', 'fields' => ['ref_no']],
        'weight'    => ['fields' => ['weight']],
        'name'      => ['fields' => ['name']],
        'dfrom'     => ['fields' => ['dfrom']],
        'dto'       => ['fields' => ['dto']],
        'atime'     => ['fields' => ['atime']],
        'ctime'     => ['fields' => ['ctime']],
        'mtime'     => ['fields' => ['mtime']],
        'phone'     => ['fields' => ['phone']],
        'group'     => ['fields' => ['group']],
        'creator'   => ['fields' => ['creator']],
        'auditor'   => ['fields' => ['auditor']],
        'approval'  => ['fields' => ['approval']],
];

// $config['user']['fields']['lab'] =  ['type'=>'object', 'oname'=>'lab'];
// $config['user']['fields']['lab_abbr'] =  ['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''];
// $config['user']['indexes']['lab'] = ['fields'=>['lab']];
// $config['user']['indexes']['lab_abbr'] =  ['fields'=>['lab_abbr']];

$config['follow'] = [
    'fields'  => [
        'user'   => ['type' => 'object', 'oname' => 'user'],
        'object' => ['type' => 'object'],
        'ctime'  => ['type' => 'int', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'user'   => ['fields' => ['user']],
        'object' => ['fields' => ['object']],
        'ctime'  => ['fields' => ['ctime']],
    ],
];

$config['lab'] = [
    'fields'  => [
        'creator'     => ['type' => 'object', 'oname' => 'user'],
        'auditor'     => ['type' => 'object', 'oname' => 'user'],
        'owner'       => ['type' => 'object', 'oname' => 'user'],
        'secretary'   => ['type' => 'object', 'oname' => 'user'], //临时PI
        'ref_no'      => ['type' => 'varchar(40)', 'null' => true, 'default' => null],
        'name'        => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'description' => ['type' => 'text', 'null' => true],
        'rank'        => ['type' => 'int', 'null' => false, 'default' => 0],
        'ctime'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'mtime'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'atime'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'name_abbr'   => ['type' => 'varchar(150)', 'null' => true],
        'contact'     => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        # 实验室编号 ref_no
        # 实验室类别 lab_type
        # 房屋使用面积 util_area
        # 所属学科 lab_subject
        'group'       => ['type' => 'object', 'oname' => 'tag_group'],
        'hidden'      => ['type' => 'tinyint', 'null' => false, 'default' => 0],
        'approval'    => ['type' => 'tinyint', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'owner'     => ['fields' => ['owner']],
        'ref_no'    => ['fields' => ['ref_no']],
        'name'      => ['fields' => ['name']],
        'ctime'     => ['fields' => ['ctime']],
        'mtime'     => ['fields' => ['mtime']],
        'name_abbr' => ['fields' => ['name_abbr']],
        'group'     => ['fields' => ['group']],
        'contact'   => ['fields' => ['contact']],
        'approval'  => ['fields' => ['approval']],
    ],
];

$config['card'] = [
    'fields'  => [
        'ref' => ['type' => 'varchar(150)', 'null' => false], // 引用号，如学工号
        'no'  => ['type' => 'int unsigned', 'null' => false], // 物理卡号
    ],
    'indexes' => [
        'ref' => ['type' => 'unique', 'fields' => ['ref']],
        'no'  => ['type' => 'unique', 'fields' => ['no']],
    ],
];

// 偏好设置字段:边栏菜单, 提示设置, 自定义首页, 区域设置-语言, 默认机构, 隐私设置
$config['user_info']['fields']['user']              = ['type' => 'object', 'oname' => 'user'];
$config['user_info']['fields']['sbmenu_categories'] = ['type' => 'json'];
$config['user_info']['fields']['hide_all_tips']     = ['type' => 'tinyint', 'null' => false, 'default' => 0];
$config['user_info']['fields']['home']              = ['type' => 'varchar(20)', 'null' => false, 'default' => 'me'];
$config['user_info']['fields']['locale']            = ['type' => 'varchar(20)', 'null' => false, 'default' => 'zh_CN'];
$config['user_info']['fields']['default_group_id']  = ['type' => 'int', 'null' => false, 'default' => 0];
$config['user_info']['fields']['privacy']           = ['type' => 'int', 'null' => false, 'default' => 0];

$config['user_info']['indexes']['user'] = ['fields' => ['user']];
