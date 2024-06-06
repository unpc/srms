<?php

$config['rule']['credit_add']['#name'] = '加分项';
$config['rule']['credit_cut']['#name'] = '扣分项';

$config['classification']['credit_add'] = [
    [
        'title'  => '每日登录, ',
        'hidden' => '0',
        'body'   => '%at_user, 您好: \n\n%user 在 %link 的评论中提到了您, 具体内容如下: \n\n %content',
    ],
];

$config['classification']['credit_cut'] = [
    [
        'title'  => '每日登录, ',
        'hidden' => '0',
        'body'   => '%at_user, 您好: \n\n%user 在 %link 的评论中提到了您, 具体内容如下: \n\n %content',
    ],
];

// 默认加分扣分项
// $config['default']['user_active']  = ['title' => '用户激活', 'description' => '用户首次激活时, 自动充值基准信用分100分', 'type' => Credit_Rule_Model::STATUS_ADD, 'score' => 100, 'is_disabled' => false, 'hidden' => true];
$config['default']['init_credit_score'] = ['title' => '初始化信用分', 'description' => '系统用户初始化信用分', 'type' => Credit_Rule_Model::STATUS_ADD, 'score' => 100, 'is_disabled' => false, 'hidden' => true];
$config['default']['login']             = ['title' => '每日登录', 'description' => '每日多次登录, 仍加1次分', 'type' => Credit_Rule_Model::STATUS_ADD];
$config['default']['reserv']            = ['title' => '正常预约使用一次仪器', 'description' => '“正常使用”指当次预约记录正常生成使用记录, 且未被标记违规状态; ', 'type' => Credit_Rule_Model::STATUS_ADD];
$config['default']['qualication']       = ['title' => '获取一个上岗资质', 'description' => '“获取上岗资质”指通过仪器培训获得使用仪器资格; ', 'type' => Credit_Rule_Model::STATUS_ADD];
$config['default']['publication']       = ['title' => '添加一个科研成果(论文、获奖、专利)', 'description' => '', 'type' => Credit_Rule_Model::STATUS_ADD];
$config['default']['achivement']        = ['title' => '添加成果为核心成果', 'description' => '“核心成果”指被添加成果标签的成果记录. ', 'type' => Credit_Rule_Model::STATUS_ADD];
$config['default']['late']              = ['title' => '迟到一次', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default']['early']             = ['title' => '早退一次', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default']['timeout']           = ['title' => '超时一次', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default']['miss']              = ['title' => '爽约一次', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
// $config['default']['ban']               = ['title' => '新增一条违规行为', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default']['feedback']          = ['title' => '新增一条超24h未反馈的使用记录', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default']['reserv_cacel']      = ['title' => '取消一次预约记录', 'description' => '', 'type' => Credit_Rule_Model::STATUS_CUT];
$config['default'][Credit_Rule_Model::CUSTOM_ADD] = ['title' => '自定义加分项', 'description' => '自定义加分项(仅在添加信用明细时做关联使用,系统设置里不可见该设置)', 'type' => Credit_Rule_Model::STATUS_ADD, 'is_custom' => true, 'is_disabled' => false];
$config['default'][Credit_Rule_Model::CUSTOM_CUT] = ['title' => '自定义减分项', 'description' => '自定义减分项(仅在添加信用明细时做关联使用,系统设置里不可见该设置)', 'type' => Credit_Rule_Model::STATUS_CUT, 'is_custom' => true, 'is_disabled' => false];

$config['default_levels'] = [
    1 => '大众用户',
    2 => '白银用户',
    3 => '黄金用户',
    4 => '铂金用户',
    5 => '钻石用户',
];

$config['default_measures'] = [
    'can_not_reserv' => '禁止用户预约仪器',
    'ban'            => '加入系统黑名单',
    'unactive_user'  => '用户账号变为未激活',
    'send_msg'       => '发送阀值消息通知用户',
    'system_ban'       => '自动加入系统黑名单',
    'eq_ban'       => '加入仪器黑名单',
    'lab_ban'       => '全组自动被加入系统黑名单',
];

$config['export_columns.credit'] = [
    'name'         => '姓名',
    'lab'          => '所属课题组',
    'group'        => '所属组织机构',
    'level'        => '信用等级',
    'credit_score' => '信用分',
];

$config['export_credit_record_columns.credit'] = [
    'id'        => '编号',
    'ctime'     => '计分时间',
    'name'      => '姓名',
    'event'     => '计分事件',
    'equipment' => '关联仪器',
    'score'     => '分数',
    'total'     => '信用分',
    'operator'  => '操作人',
    'operation_time' => '操作时间',
];
