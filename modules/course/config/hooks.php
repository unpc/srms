<?php

$config['controller[admin/index].ready'][] = 'Course_Admin::setup';

$config['is_allowed_to[列表].course'][] = 'Course::Course_ACL';
$config['is_allowed_to[添加].course'][] = 'Course::Course_ACL';
$config['is_allowed_to[修改].course'][] = 'Course::Course_ACL';
$config['is_allowed_to[删除].course'][] = 'Course::Course_ACL';

$config['is_allowed_to[列表].school_term'][] = 'Course_Admin::School_Term_ACL';
$config['is_allowed_to[添加].school_term'][] = 'Course_Admin::School_Term_ACL';
$config['is_allowed_to[修改].school_term'][] = 'Course_Admin::School_Term_ACL';
$config['is_allowed_to[删除].school_term'][] = 'Course_Admin::School_Term_ACL';

$config['is_allowed_to[列表].course_session'][] = 'Course_Admin::Course_Session_ACL';
$config['is_allowed_to[添加].course_session'][] = 'Course_Admin::Course_Session_ACL';
$config['is_allowed_to[修改].course_session'][] = 'Course_Admin::Course_Session_ACL';
$config['is_allowed_to[删除].course_session'][] = 'Course_Admin::Course_Session_ACL';
