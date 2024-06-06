<?php

$config['group'] = '组织机构';

// 从tag表分离出的类型配置, 除了把tag_name配置进去, 
// 还得在application/models/tag定义model
// 还得在config/schema配置表结构

$config['separated'] = [
    'group',
    'equipment',
    'equipment_technical',
    'equipment_education',
    'achievements_patent',
    'achievements_award',
    'achievements_publication',
    'equipment_user_tags', // 仪器用户标签
    'meeting_user_tags', // 仪器用户标签
    'location',
    'service_type',//服务类型
];