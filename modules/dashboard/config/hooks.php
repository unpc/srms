<?php

$config['module[dashboard].is_accessible'][] = 'Dashboard_New_Access::is_accessible';
$config['is_allowed_to[查看].dashboard_new'][] = 'Dashboard_New_Access::dashboard_ACL';
