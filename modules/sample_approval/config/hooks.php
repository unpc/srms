<?php
$config['sample.colors'] = 'Sample_approval::color';
$config['sample.status'][] = 'Sample_approval::status';
$config['sample.charge_status'][] = 'Sample_approval::charge_status';
$config['people.extra.keys'][] = 'Sample_approval::people_extra_keys';
$config['extra.form.validate'][] = 'Sample_approval::extra_form_validate';
$config['sample.form.submit'][] = 'Sample_approval::sample_form_submit';
$config['extra.form.post_submit'][] = 'Sample_approval::sample_form_post_submit';

$config['module[sample_approval].is_accessible'][] = 'Sample_approval::is_accessible';

$config['eq_sample.links'][] = 'Sample_approval::sample_links';
$config['eq_sample.buttons'][] = 'Sample_approval::sample_buttons';
$config['eq_sample.message'][] = 'Sample_approval::sample_message';

$config['sample.extra.print'][] = 'Sample_approval::sample_extra_print';
$config['sample.print.format'][] = 'Sample_approval::sample_print_custom';
$config['sample.print.mode'][] = 'Sample_approval::sample_print_custom';


$config['eq_sample.requirement.extra.view'][] = 'Sample_approval::eq_sample_requirement_extra_view';
