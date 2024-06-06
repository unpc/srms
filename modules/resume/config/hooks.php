<?php
$config['controller[!update].ready'][] = 'Resume::setup_update';
$config['position_model.update.message'][] = 'Resume::get_update_message';
$config['position_model.update.message_view'][] = 'Resume::get_update_message_view';
$config['resume_model.saved'][] = 'Resume::on_resume_saved';
$config['get.datas'][] = 'Resume::get_update_parameter';

$config['is_allowed_to[查看].resume'][] = 'Resume::resume_ACL';
$config['is_allowed_to[修改].resume'][] = 'Resume::resume_ACL';
$config['is_allowed_to[添加].resume'][] = 'Resume::resume_ACL';
$config['is_allowed_to[导出].resume'][] = 'Resume::resume_ACL';
$config['is_allowed_to[领导批示].resume'][] = 'Resume::resume_ACL';
$config['is_allowed_to[生成新员工].resume'][] = 'Resume::resume_ACL';

$config['is_allowed_to[查看].position'][] = 'Position::position_ACL';
$config['is_allowed_to[添加].position'][] = 'Position::position_ACL';
$config['is_allowed_to[修改].position'][] = 'Position::position_ACL';


$config['module[resume].is_accessible'][] = 'Resume::is_accessible';
