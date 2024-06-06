<?php
$config['extra.form.validate'][] = 'Extra::validate_extra_value';
$config['extra.form.post_submit'][] = 'Extra::save_extra_value';

$config['is_allowed_to[修改].extra'][] = 'Extra_Access::extra_ACL';

$config['api.v1.equipment.booking-schema.GET'][] = 'Extra_API::equipment_reserv_schema';
$config['api.v1.equipment.sample-schema.GET'][] = 'Extra_API::equipment_sample_schema';
$config['equipment.api.v1.booking-schema.GET'][] = 'Extra_API::equipment_reserv_schema';
$config['equipment.api.v1.feedback-schema.GET'][] = 'Extra_API::equipment_feedback_schema';
$config['equipment.api.v1.log-schema.GET'][] = 'Extra_API::equipment_log_schema';
$config['equipment.api.v1.sample-schema.GET'][] = 'Extra_API::equipment_sample_schema';
