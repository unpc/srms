<?php

$config['api.v1.equipment-sheet.POST'][] = 'Sheet_API::equipment_sheet';
$config['api.v1.equipment-booking-sheet.POST'][] = 'Sheet_API::equipment_booking_sheet';
$config['api.v1.equipment-sample-sheet.POST'][] = 'Sheet_API::equipment_sample_sheet';
$config['api.v1.equipment-log-sheet.POST'][] = 'Sheet_API::equipment_log_sheet';

$config['eq_charge_model.before_save'][] = 'Sheet_API::on_eq_charge_before_save';
