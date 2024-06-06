<?php

$config['billing_manage']['管理所有财务'] = FALSE;
$config['billing_manage']['管理下属机构财务'] = FALSE;
$config['billing_manage']['管理负责实验室财务'] = FALSE;

$config['billing_manage']['#name'] = '财务管理';
$config['billing_manage']['#icon'] = '!billing/icons/32/billing.png';

$config['default_roles']['课题组负责人']['default_perms'][] = "管理负责实验室财务";