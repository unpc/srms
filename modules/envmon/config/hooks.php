<?php

//$config['controller[admin/index].ready'][] = 'Node_Admin::setup';

$config['is_allowed_to[查看].env_node'][]   = 'Node::node_ACL';
$config['is_allowed_to[添加].env_node'][]   = 'Node::node_ACL';
$config['is_allowed_to[修改].env_node'][]   = 'Node::node_ACL';
$config['is_allowed_to[删除].env_node'][]   = 'Node::node_ACL';
$config['is_allowed_to[添加传感器].env_node'][]   = 'Node::node_ACL';

$config['is_allowed_to[添加].env_sensor'][] = 'Sensor::sensor_ACL';
$config['is_allowed_to[修改].env_sensor'][] = 'Sensor::sensor_ACL';
$config['is_allowed_to[删除].env_sensor'][] = 'Sensor::sensor_ACL';

$config['module[envmon].is_accessible'][]   = 'Node::is_accessible';
$config['newsletter.get_contents[security]'][] = 'Sensor::envmon_newsletter_content';
