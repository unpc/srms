<?php

$config['module[vidmon].is_accessible'][] = 'Vidmon::is_accessible';

$config['is_allowed_to[列表].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[添加].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[修改].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[删除].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[查看].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[查看历史记录].vidcam'][] = 'Vidmon::vidcam_ACL';
$config['is_allowed_to[多屏监控].vidcam'][] = 'Vidmon::vidcam_ACL';

$config['vidcam_model.saved'][] = 'Vidmon::stop_snapshot_agent';
