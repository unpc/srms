<?php
$config['is_allowed_to[列表].summary'][] = 'Summary_Access::eq_perf_ACL';
$config['is_allowed_to[查看].summary'][] = 'Summary_Access::eq_perf_ACL';
$config['is_allowed_to[添加].summary'][] = 'Summary_Access::eq_perf_ACL';
$config['is_allowed_to[修改].summary'][] = 'Summary_Access::eq_perf_ACL';
$config['is_allowed_to[删除].summary'][] = 'Summary_Access::eq_perf_ACL';

$config['is_allowed_to[列表].summary'][]       = 'Summary_Access::eq_stat_ACL';
$config['is_allowed_to[列表统计].summary'][] = 'Summary_Access::eq_stat_ACL';

$config['module[summary].is_accessible'][] = 'Summary_Access::is_accessible';

// 基表3
$config['summary.three.teaching_dur'][]          = 'Three::teaching_dur';
$config['summary.three.research_dur'][]          = 'Three::research_dur';
$config['summary.three.service_dur'][]           = 'Three::service_dur';
$config['summary.three.open_dur'][]              = 'Three::open_dur';
$config['summary.three.samples'][]               = 'Three::samples';
$config['summary.three.train_stu_count'][]       = 'Three::train_stu_count';
$config['summary.three.train_tea_count'][]       = 'Three::train_tea_count';
$config['summary.three.train_oth_count'][]       = 'Three::train_oth_count';
$config['summary.three.education_pro_count'][]   = 'Three::education_pro_count';
$config['summary.three.research_pro_count'][]    = 'Three::research_pro_count';
$config['summary.three.service_pro_count'][]     = 'Three::service_pro_count';
$config['summary.three.national_awards_count'][] = 'Three::national_awards_count';
$config['summary.three.province_awards_count'][] = 'Three::province_awards_count';
$config['summary.three.tea_patent_count'][]      = 'Three::tea_patent_count';
$config['summary.three.stu_patent_count'][]      = 'Three::stu_patent_count';
$config['summary.three.three_pubs_count'][]      = 'Three::three_pubs_count';
$config['summary.three.core_pubs_count'][]       = 'Three::core_pubs_count';


$config['user_model.extra_roles'][] = 'Summary_Access::extra_roles';
