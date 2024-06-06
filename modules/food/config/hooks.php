<?php
	
$config['is_allowed_to[指定人员].food'][] = 'Food::operate_food_is_allowed';

$config['is_allowed_to[添加].food'][] = 'Food::operate_food_is_allowed';
$config['is_allowed_to[修改].food'][] = 'Food::operate_food_is_allowed';
$config['is_allowed_to[删除].food'][] = 'Food::operate_food_is_allowed';

$config['is_allowed_to[查看].fd_order'][] = 'Fd_order::operate_fd_order_is_allowed';
$config['is_allowed_to[添加].fd_order'][] = 'Fd_order::operate_fd_order_is_allowed';	
$config['is_allowed_to[修改].fd_order'][] = 'Fd_order::operate_fd_order_is_allowed';
$config['is_allowed_to[删除].fd_order'][] = 'Fd_order::operate_fd_order_is_allowed';

$config['controller[!people/profile].ready'][] = 'Food::setup_profile';
