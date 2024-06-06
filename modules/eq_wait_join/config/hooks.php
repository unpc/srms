<?php
//仪器的equipment页面增加排队预约的列表模块
$config['controller[!equipments/equipment/index].ready'][] = 'Wait_Join::setup';

//仪器的equipment页面中预约模块进行保存时候处理Form信息
$config['eq_reserv.equipment_edit_time_form_submit'][] = 'Wait_Join::on_equipment_reserv_form_submit';

//预约排队功能注入到预约失败的界面
$config['cal_component.add_failed_link'][] = 'Wait_Join::on_component_add_failed';

// 权限配置
$config['is_allowed_to[管理预约等待].equipment'][] = 'Wait_Join::equipment_ACL';
