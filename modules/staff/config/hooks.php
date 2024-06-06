<?php

$config['is_allowed_to[查看].staff'][] = "Staff::staff_ACL";
$config['is_allowed_to[修改].staff'][] = "Staff::staff_ACL";
$config['is_allowed_to[管理].staff'][] = "Staff::staff_ACL";

$config['controller[!people/profile].ready'][] = 'Staff::setup_people';
$config['controller[!resume/resume].ready'][] = 'Staff::setup_resume';
$config['user.before_delete_message'][] = 'Staff::people_user';

$config['people.base.tab'][] = 'Staff::people_base_tab';

$config['admin.people.tab'][] = 'Staff_Admin::admin_people_tab';
