<?php

$config['module[fund_report].is_accessible'][] = 'Fund_Report::is_accessible';

$config['is_allowed_to[审批基金申报].fund_report_apply'][] = 'Fund_Report::apply_ACL';
$config['is_allowed_to[列表基金申报].fund_report_apply'][] = 'Fund_Report::apply_ACL';
$config['is_allowed_to[填报申请].fund_report_apply'][] = 'Fund_Report::apply_ACL';
