<?php

$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Meter::setup_eq_meter';
// 超大电流增加阈值
$config['api.eq_gmeter.connect.extra.keys'][] = 'EQ_Meter::api_eq_gmeter_connect_extra_keys';
