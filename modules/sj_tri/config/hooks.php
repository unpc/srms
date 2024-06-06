<?php

$config['module[sj_tri].is_accessible'][] = 'Sj_Tri::is_accessible';
/* RPC提供额外字段 */
$config['people.extra.keys'][] = 'Sj_Tri::people_extra_keys';
//$config['auth.post_logout'] = 'Sj_Tri::post_logout'; 暂缓Gini框架应用的logout