<?php

$config['export_sj_tri'] = FALSE;

$config['sj_tri'] = [
    'used_samples' => [
        'name' => '使用测样数'
    ],
    'used_charge' => [
        'name' => '使用收费'
    ],
    'train_count' => [
        'name' => '培训人数'
    ],
    'train_stu_count' => [
        'name' => '培训学生'
    ],
    'train_tea_count' => [
        'name' => '培训教师'
    ],
    'train_oth_count' => [
        'name' => '培训其他人'
    ],
    'education_pro_count' => [
        'name' => '服务教学项目数'
    ],
    'research_pro_count' => [
        'name' => '服务科研项目数'
    ],
    'service_pro_count' => [
        'name' => '服务社会项目数'
    ],
];

$config['export.start'] = '20150901';
$config['export.end'] = '20160831';
$config['people.self_group_name'] = '南开大学';
$config['people.role.other'] = ['min' => 20, 'max' => 29];
$config['people.role.teacher'] = ['min' => 10, 'max' => 19];
$config['people.role.student'] = ['min' => 0, 'max' => 9];
$config['use_meter'] = FALSE;
$config['publication.three_pubs'] = '三大检索';
$config['publication.core_pubs_count'] = '核心刊物';
$config['award.national'] = '国家级';
$config['award.province'] = '省部级';
$config['patent.teacher'] = '教师';
$config['patent.student'] = '学生';