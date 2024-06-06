<?php
$config['controller[!vidmon/vidcam/index].ready'][] = 'EQ_Vidcam::setup';
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Vidcam::setup_vidcam';
$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Vidcam::setup_equipment';

$config['is_allowed_to[管理仪器视频监控].equipment'][] = 'EQ_vidcam::operate_eq_vidcam_is_allowed';
$config['is_allowed_to[查看仪器视频监控].equipment'][] = 'EQ_vidcam::operate_eq_vidcam_is_allowed';

$config['is_allowed_to[查看关联仪器].vidcam'][] = 'EQ_vidcam::vidcam_ACL';
$config['is_allowed_to[查看历史记录].vidcam'][] = 'EQ_vidcam::vidcam_ACL';
$config['is_allowed_to[查看].vidcam'][] = 'EQ_vidcam::vidcam_ACL';
$config['is_allowed_to[列表].vidcam'][] = 'EQ_vidcam::vidcam_ACL';