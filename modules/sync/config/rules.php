<?php
/**
 * 人员、仪器、课题组等基础信息同步默认规则
 */
$config['sync_rules']['user'] = [
    'allow_delete' => true,//允许各个站点删除
];

$config['sync_rules']['lab'] = [
    'allow_delete' => true,//允许各个站点删除
];

$config['sync_rules']['equipment'] = [
    'allow_delete' => true,//允许各个站点删除
];