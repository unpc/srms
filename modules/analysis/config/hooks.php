<?php

$config['eq_reserv_model.saved'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_sample_model.saved'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_record_model.saved'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_reserv_model.deleted'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_sample_model.deleted'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_record_model.deleted'][] = ['callback' => 'Analysis::mark', 'weight' => 999];
$config['eq_reserv_model.before_save'][] = ['callback' => 'Analysis::mark_before', 'weight' => 999];
$config['eq_sample_model.before_save'][] = ['callback' => 'Analysis::mark_before', 'weight' => 999];
$config['eq_record_model.before_save'][] = ['callback' => 'Analysis::mark_before', 'weight' => 999];

$config['ue_training_model.saved'][] = ['callback' => 'Analysis_Training::mark', 'weight' => 999];
$config['ue_training_model.deleted'][] = ['callback' => 'Analysis_Training::mark', 'weight' => 999];

$config['field.use_dur.refresh'][] = ['callback' => 'Analysis_Field::use_dur_refresh', 'weight' => 0];
$config['field.sample_dur.refresh'][] = ['callback' => 'Analysis_Field::sample_dur_refresh', 'weight' => 0];
$config['field.reserv_dur.refresh'][] = ['callback' => 'Analysis_Field::reserv_dur_refresh', 'weight' => 0];
$config['field.use_time.refresh'][] = ['callback' => 'Analysis_Field::use_time_refresh', 'weight' => 0];
$config['field.sample_time.refresh'][] = ['callback' => 'Analysis_Field::sample_time_refresh', 'weight' => 0];
$config['field.reserv_time.refresh'][] = ['callback' => 'Analysis_Field::reserv_time_refresh', 'weight' => 0];
$config['field.use_fee.refresh'][] = ['callback' => 'Analysis_Field::use_fee_refresh', 'weight' => 0];
$config['field.sample_fee.refresh'][] = ['callback' => 'Analysis_Field::sample_fee_refresh', 'weight' => 0];
$config['field.reserv_fee.refresh'][] = ['callback' => 'Analysis_Field::reserv_fee_refresh', 'weight' => 0];
$config['field.success_sample.refresh'][] = ['callback' => 'Analysis_Field::success_sample_refresh', 'weight' => 0];
$config['field.use_sample.refresh'][] = ['callback' => 'Analysis_Field::use_sample_refresh', 'weight' => 0];
$config['field.sample_sample.refresh'][] = ['callback' => 'Analysis_Field::sample_sample_refresh', 'weight' => 0];
$config['field.use_project.refresh'][] = ['callback' => 'Analysis_Field::use_project_refresh', 'weight' => 0];
$config['field.sample_project.refresh'][] = ['callback' => 'Analysis_Field::sample_project_refresh', 'weight' => 0];
$config['field.reserv_project.refresh'][] = ['callback' => 'Analysis_Field::reserv_project_refresh', 'weight' => 0];

$config['analysis.training.student_count.refresh'][] = ['callback' => 'Analysis_Training::student_count_refresh', 'weight' => 0];
$config['analysis.training.teacher_count.refresh'][] = ['callback' => 'Analysis_Training::teacher_count_refresh', 'weight' => 0];
$config['analysis.training.other_count.refresh'][] = ['callback' => 'Analysis_Training::other_count_refresh', 'weight' => 0];
$config['analysis.training.apply_count.refresh'][] = ['callback' => 'Analysis_Training::apply_count_refresh', 'weight' => 0];

$config['analysis.limit.equipment'][] = 'Analysis_Limit::equipment';
$config['analysis.limit.polymerize'][] = 'Analysis_Limit::polymerize';

$config['people.extra.keys'][] = 'Analysis::people_extra_keys';

$config['analysis.limit.yit'][] = 'Analysis_Limit::polymerize';
$config['analysis.limit.equipment'][] = 'Analysis_Limit::equipment';
$config['analysis.limit.polymerize'][] = 'Analysis_Limit::polymerize';

$config['analysis.init.table'][] = 'Analysis_Training::init';
$config['analysis.init.table'][] = 'Analysis_Maintain::init';
$config['analysis.init.table'][] = 'Analysis_Init::init_group';
$config['analysis.init.table'][] = 'Analysis_Init::init_equipment';
$config['analysis.init.table'][] = 'Analysis_Init::init_equipment_group';
$config['analysis.init.table'][] = 'Analysis_Init::init_user';
$config['analysis.init.table'][] = 'Analysis_Init::init_user_equipment';
$config['analysis.init.table'][] = 'Analysis_Init::init_user_group';
$config['analysis.init.table'][] = 'Analysis_Init::init_user_lab';
$config['analysis.init.table'][] = 'Analysis_Init::init_lab';
$config['analysis.init.table'][] = 'Analysis_Init::init_lab_group';
$config['analysis.init.table'][] = 'Analysis_Init::init_project';
$config['analysis.init.table'][] = 'Analysis_Init::init_roles';
$config['analysis.init.table'][] = 'Analysis_Achievement::init';
$config['analysis.init.table'][] = 'Analysis_Project_Publication::init';
$config['analysis.init.table'][] = 'Analysis_Project_Awards::init';
$config['analysis.init.table'][] = 'Analysis_Project_Patent::init';

$config['analysis.full.data'][] = 'Analysis_Training::full';
$config['analysis.full.data'][] = 'Analysis_Maintain::full';
$config['analysis.full.data'][] = 'Analysis::full_group';
$config['analysis.full.data']['equipment'] = 'Analysis::full_equipment';
$config['analysis.full.data'][] = 'Analysis::full_equipment_group';
$config['analysis.full.data'][] = 'Analysis::full_user';
$config['analysis.full.data'][] = 'Analysis::full_user_equipment';
$config['analysis.full.data'][] = 'Analysis::full_user_group';
$config['analysis.full.data'][] = 'Analysis::full_user_lab';
$config['analysis.full.data'][] = 'Analysis::full_lab';
$config['analysis.full.data'][] = 'Analysis::full_lab_group';
$config['analysis.full.data'][] = 'Analysis::full_project';
$config['analysis.full.data'][] = 'Analysis_Achievement::full';
$config['analysis.full.data'][] = 'Analysis_Project_Publication::full';
$config['analysis.full.data'][] = 'Analysis_Project_Awards::full';
$config['analysis.full.data'][] = 'Analysis_Project_Patent::full';

$config['analysis.increment.data'][] = 'Analysis_Training::increment';
$config['analysis.increment.data'][] = 'Analysis_Maintain::increment';
$config['analysis.increment.data'][] = 'Analysis_Achievement::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Publication::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Awards::increment';
$config['analysis.increment.data'][] = 'Analysis_Project_Patent::increment';

/**
 * @todo 这里本想着记录删除时同步删除推送到godiva的数据
 * 由于bug24487提示接口报错，先作弃用处理
 */
// $config['eq_record_model.deleted'][] = ['callback' => 'Analysis::delete', 'weight' => 0];
// $config['eq_reserv_model.deleted'][] = ['callback' => 'Analysis::delete', 'weight' => 0];
// $config['eq_sample_model.deleted'][] = ['callback' => 'Analysis::delete', 'weight' => 0];
// $config['eq_charge_model.deleted'][] = ['callback' => 'Analysis::delete', 'weight' => 0];