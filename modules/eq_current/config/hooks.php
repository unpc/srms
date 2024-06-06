<?php
//Device_Computer Hooks
$config['device_computer.remote_command.current'][] = 'EQ_Current::on_command_current';
$config['device_computer.remote_command.current_threshold'][] = 'EQ_Current::on_command_current_threshold';
$config['device_computer.agent_command.current'][] = 'EQ_Current::command_current';
$config['device_computer.agent_command.current_threshold'][] = 'EQ_Current::command_current_threshold';
$config['device_computer.keep_alive'][] = 'EQ_Current::keep_alive';



$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Current::setup_view';
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Current::setup_edit';



//eq_stat related
$config['stat.equipment.power_consum'][] = 'EQ_Current::stat_power_consum';


//ACL
$config['is_allowed_to[修改能耗设置].equipment'][] = 'EQ_Current_Access::equipment_ACL';
