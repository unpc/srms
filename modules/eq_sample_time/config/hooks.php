<?php

// $config['eq_sample.time_setting.breadcrumb'][] = 'EQ_Sample_Time::time_setting_breadcrumb';
// $config['eq_sample.time_setting.content'][] = 'EQ_Sample_Time::time_setting_content';
// $config['eq_sample_model.before_save'][] = 'EQ_Sample_Time::on_eq_sample_before_save';

$config['eq_sample.equipment_edit_form_submit'][] = 'EQ_Sample_Time::time_setting_content';
$config['extra.form.validate'][] = 'EQ_Sample_Time::extra_form_validate';
$config['calendar.cdata.get'][] = 'EQ_Sample_Time::get_components';