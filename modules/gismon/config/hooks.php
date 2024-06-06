<?php
/* NO.TASK#310 为gismon设置权限	*/
/* (xiaopei.li@2010.12.20)		*/

$config['is_allowed_to[列表].gis_building'][] = 'GISMon::gis_building_ACL';
$config['is_allowed_to[查看].gis_building'][] = 'GISMon::gis_building_ACL';
$config['is_allowed_to[添加].gis_building'][] = 'GISMon::gis_building_ACL';
$config['is_allowed_to[修改].gis_building'][] = 'GISMon::gis_building_ACL';
$config['is_allowed_to[删除].gis_building'][] = 'GISMon::gis_building_ACL';

$config['is_allowed_to[修改].gis_device'][] = 'GISMon::gis_device_ACL';

/* NO.BUG#287 判断sidebar是否显示GISMon图标*/
$config['module[gismon].is_accessible'][] = 'GISMon::is_accessible';
