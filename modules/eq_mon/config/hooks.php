<?php

$config['equipment.links'][] = 'EQ_Mon::links';
$config['is_allowed_to[监控].equipment'][] = 'EQ_Mon::control_is_allowed';

$config['device_computer.remote_command.cam_channels'][] = 'EQ_Mon::on_command_cam_channels';
$config['device_computer.remote_command.observers'][] = 'EQ_Mon::on_command_observers';
$config['device_computer.remote_command.cam_capture'][] = 'EQ_Mon::on_command_cam_capture';
$config['device_computer.remote_command.chat'][] = 'EQ_Mon::on_command_chat';
$config['device_computer.agent_command.cam_channels'][] = 'EQ_Mon::command_cam_channels';
$config['device_computer.agent_command.chat'][] = 'EQ_Mon::command_chat';
$config['device_computer.agent_command.cam_capture'][] = 'EQ_Mon::command_cam_capture';
