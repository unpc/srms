<?php

// 信用分规则表
$config['credit_rule'] = [
    'fields'  => [
        'ref_no'       => ['type' => 'varchar(64)', 'null' => true, 'default' => null], // 内部识别码, 类似于组织机构对应的机构编码
        'name'         => ['type' => 'varchar(255)', 'null' => false, 'default' => ''], // 规则名称
        'score'        => ['type' => 'int', 'null' => false, 'default' => 0], // 该规则计分
        'hidden'       => ['type' => 'int', 'null' => false, 'default' => 0], // 系统默认隐藏项, 不可编辑 [0 => 非隐藏项, 1 => 隐藏项]
        'type'         => ['type' => 'int', 'null' => false, 'default' => 0], // 计分类型 [0 => 加分, 1 => 扣分]
        'is_custom'    => ['type' => 'int', 'null' => false, 'default' => 0], // 是否自定义 [0 => 非自定义项, 1 => 自定义项]
        'is_disabled' => ['type' => 'int', 'null' => false, 'default' => 0], // 是否启用 [0 => 启用, 1 => 禁用]
        'ctime'        => ['type' => 'int', 'null' => false, 'default' => 0], // 规则增加时间
        'description'  => ['type' => 'varchar(255)', 'null' => true], // 规则详情描述
    ],
    'indexes' => [
        'ref_no' => ['fields' => ['ref_no'], 'type' => 'unique'],
        'name'   => ['fields' => ['name']],
        'type'   => ['fields' => ['type']],
        'score'  => ['fields' => ['score']],
    ],
];

// 用户信用分增减明细表
$config['credit_record'] = [
    'fields'  => [
        'user'        => ['type' => 'object', 'oname' => 'user'], // 使用者
        'equipment'   => ['type' => 'object', 'oname' => 'equipment'], // 关联仪器
        'credit_rule' => ['type' => 'object', 'oname' => 'credit_rule'], // 关联计分规则
        'source'      => ['type' => 'object'], // 关联的预约或其他内容 可为空
        'ctime'       => ['type' => 'int', 'null' => false, 'default' => 0], // 计分变化时间
        'score'       => ['type' => 'int', 'null' => false, 'default' => 0], // 本次得分 可以正负
        'is_auto'     => ['type' => 'int', 'null' => false, 'default' => 1], // 是否是系统自动计分
        'total'       => ['type' => 'int', 'null' => false, 'default' => 0], // 本次得分后当前总分
        'description' => ['type' => 'varchar(255)', 'null' => true], // 备注, 手动计分可能会用到
        'operator'    => ['type' => 'object', 'oname' => 'user'], // 操作人
        'operation_time' => ['type' => 'int', 'null' => false, 'default' => 0], // 操作时间
        'status' => ['type' => 'int', 'null' => false, 'default' => 0], //奖惩状态，0未处理 1已处理
    ],
    'indexes' => [
        'credit_rule' => ['fields' => ['credit_rule']],
        'ctime'       => ['fields' => ['ctime']],
        'operator'    => ['fields' => ['operator']],
        'operation_time' => ['fields' => ['operation_time']],
    ],
];

// 用户信用分等级
$config['credit_level'] = [
    'fields'  => [
        'level'      => ['type' => 'int', 'null' => false, 'default' => 0], // 等级代码
        'name'       => ['type' => 'varchar(128)', 'null' => false, 'default' => ''], // 等级名称
        'rank_start' => ['type' => 'int', 'null' => false, 'default' => 0], // 排名百分比(开始)
        'rank_end'   => ['type' => 'int', 'null' => false, 'default' => 0], // 排名百分比(结束)
        'ctime'      => ['type' => 'int', 'null' => false, 'default' => 0], // 生成时间
    ],
    'indexes' => [
        'level' => ['fields' => ['level']],
    ],
];

// 用户积分表
$config['credit'] = [
    'fields'  => [
        'user'         => ['type' => 'object', 'oname' => 'user'], // 使用者
        'credit_level' => ['type' => 'object', 'oname' => 'credit_level'], // 关联用户等级
        'ctime'        => ['type' => 'int', 'null' => false, 'default' => 0], // 生成时间
        'utime'        => ['type' => 'int', 'null' => false, 'default' => 0], // update Time 计算用户等级时间
        'mtime'        => ['type' => 'int', 'null' => false, 'default' => 0], // 记录更新时间
        'percent'      => ['type' => 'int', 'null' => false, 'default' => 0], // 当前超越了系统中{percent}%的用户
        'total'        => ['type' => 'int', 'null' => false, 'default' => 0], // 当前总分
    ],
    'indexes' => [
        'user' => ['fields' => ['user'], 'type' => 'unique'],
    ],
];

// 信用分资格限制表
$config['credit_limit'] = [
    'fields'  => [
        'enable' => ['type' => 'tinyint', 'null' => false, 'default' => 0], // 是否开启
        'score' => ['type' => 'int', 'null' => false, 'default' => 0], // 限制条件
        //阀值通知合并，故取消send_msg，threshold字段
        //'send_msg' => ['type' => 'tinyint', 'null' => false, 'default' => 0], // 发送通知
        //'threshold' => ['type' => 'int', 'null' => false, 'default' => 0], // 阈值
        'measures' => ['type' => 'object', 'oname' => 'credit_measures'], // 奖惩措施
        'is_custom' => ['type' => 'int', 'null' => false, 'default' => 0], // 是否个别资格限制 0:系统固定,其他:个别资格
    ],
    'indexes' => [
        'enable' => ['fields' => ['enable']],
        'score' => ['fields' => ['score']],
        //'send_msg' => ['fields' => ['send_msg']],
        //'threshold' => ['fields' => ['threshold']],
        'measures' => ['fields' => ['measures']],
        'is_custom' => ['fields' => ['is_custom']],
    ],
];

// 奖惩措施表
$config['credit_measures'] = [
    'fields'  => [
        'ref_no'          => ['type' => 'varchar(64)', 'null' => false, 'default' => ''], // 唯一内部编码 eg. cannot_reserv_equipment
        'name'          => ['type' => 'varchar(255)', 'null' => false, 'default' => ''], // 描述 eg. 禁止用户预约仪器
        'type'          => ['type' => 'int', 'null' => false, 'default' => 0], // [0 => 惩罚, 1 => 奖励]
        'is_disposable' => ['type' => 'int', 'null' => false, 'default' => 0], // [0 => 非一次性奖惩措施, 1 => 一次性奖惩措施]
        'ctime'         => ['type' => 'int', 'null' => false, 'default' => 0], // 生成时间
    ],
    'indexes' => [
        'name' => ['fields' => ['name'], 'type' => 'unique'],
    ],
];

// 消息读取设置，如果设置过不再提醒
$config['notification_read_setting'] = [
    'fields' => [
        'source' => ['type' => 'object', 'name' => 'credit_measures'], //提醒规则对象
        'user' => ['type' => 'object', 'name' => 'user'], // 设置用户
        'type' => ['type' => 'varchar(16)', 'defalut' => ''], // 提醒类型,[0=>不再提醒，1=>明天提醒....]
        'last_time' => ['type' => 'int', 'null' => false, 'default' => 0], // 上次提醒时间
    ],
    'indexes' => [
        'name' => ['fields' => ['source']],
        'user' => ['fields' => ['user']],
    ],
];
