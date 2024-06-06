<?php

$config['controller[!eq_stat/perf/index].ready'][] = 'Eq_Perf::setup_stat';

$config['is_allowed_to[列表].eq_perf'][] = 'eq_perf::eq_perf_ACL';
$config['is_allowed_to[查看].eq_perf'][] = 'eq_perf::eq_perf_ACL';
$config['is_allowed_to[添加].eq_perf'][] = 'eq_perf::eq_perf_ACL';
$config['is_allowed_to[修改].eq_perf'][] = 'eq_perf::eq_perf_ACL';
$config['is_allowed_to[删除].eq_perf'][] = 'eq_perf::eq_perf_ACL';

$config['is_allowed_to[列表].eq_stat'][] = 'eq_stat::eq_stat_ACL';
$config['is_allowed_to[列表统计].eq_stat'][] = 'eq_stat::eq_stat_ACL';

$config['module[eq_stat].is_accessible'][] = 'eq_perf::is_accessible';

//const值获取trigger
$config['stat.const.tag.equipments_count'][] = 'Stat_basic::const_tag_equipments_count';
$config['stat.const.tag.equipments_value'][] = 'Stat_basic::const_tag_equipments_value';
$config['stat.const.equipment.equipments_count'][] = 'Stat_basic::const_equipment_equipments_count';
$config['stat.const.equipment.equipments_value'][] = 'Stat_basic::const_equipment_equipments_value';

//以下为eq_stat存储使用hooks
$config['stat.equipment.record_sample'][] = 'Stat_list::record_sample';
$config['stat.equipment.time_total'][] = 'Stat_list::time_total';
$config['stat.equipment.time_open'][] = 'Stat_list::time_open';
$config['stat.equipment.time_valid'][] = 'Stat_list::time_valid';
$config['stat.equipment.time_class'][] = 'Stat_list::time_class'; /* 教学机时统计（暂时） */
$config['stat.equipment.use_time'][] = 'Stat_list::use_time';
$config['stat.equipment.total_trainees'][] = 'Stat_list::total_trainees';
$config['stat.equipment.pubs'][] = 'Stat_list::pubs';
$config['stat.equipment.charge_total'][] = 'Stat_list::charge_total';

$config['stat.equipment.top3_pubs'][] = 'Stat_Basic::top3_pubs';
$config['stat.equipment.core_pubs'][] = 'Stat_Basic::core_pubs';
$config['stat.equipment.national_awards'][] = 'Stat_Basic::national_awards';
$config['stat.equipment.provincial_awards'][] = 'Stat_Basic::provincial_awards';
$config['stat.equipment.teacher_patents'][] = 'Stat_Basic::teacher_patents';
$config['stat.equipment.student_patents'][] = 'Stat_Basic::student_patents';

$config['stat.equipment.projects_teaching'][] = 'Stat_Basic::projects_teaching';
$config['stat.equipment.projects_research'][] = 'Stat_Basic::projects_research';
$config['stat.equipment.projects_public_service'][] = 'Stat_Basic::projects_public_service';

$config['stat.equipment.project_statistic_values'][] = 'Stat_list::project_statistic_values';

/* Cers项目需要属性 */
$config['stat.equipment.teaching_time'][] = 'Stat_Basic::teaching_time';
$config['stat.equipment.research_time'][] = 'Stat_Basic::research_time';
$config['stat.equipment.social_services_time'][] = 'Stat_Basic::social_services_time';

$config['stat.equipment.teacher_trainees'][] = 'Stat_Basic::teacher_trainees';
$config['stat.equipment.student_trainees'][] = 'Stat_Basic::student_trainees';
$config['stat.equipment.other_trainees'][] = 'Stat_Basic::other_trainees';

/* RPC提供额外字段 */
$config['people.extra.keys'][] = 'EQ_Stat::people_extra_keys';
