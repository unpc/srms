<?php

$config['is_allowed_to[查看].role'] = 'Role::user_ACL';

$config['people_users_table.prerender'][] = 'Role::prerender_people_users_table';

$config['people.index.search.submit'][] = 'Role::people_role_selector';

$config['role.set_roles'][] = 'Role::set_roles';


$config['role_perm.connect'][] = 'Role::role_perm_connect';
$config['module[roles].is_accessible'][] = 'Role::is_accessible';


$config['api.v1.user.permissions.GET'][] = 'Role_API::Perms_get';
$config['api.v1.role.list.GET'][] = 'Role_API::role_list';
