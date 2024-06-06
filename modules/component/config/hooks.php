<?php

$config['module[component].is_accessible'][] = 'Dashboard_Access::is_accessible';

$config['is_allowed_to[查看].dashboard'][] = 'Dashboard_Access::dashboard_ACL';
