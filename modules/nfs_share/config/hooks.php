<?php

$config['nfs.user_access'][] = 'NFS_Share::user_access';

$config['is_allowed_to[列表文件].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[上传文件].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[创建目录].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[下载文件].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[修改文件].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[删除文件].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[修改目录].user'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[删除目录].user'][] = 'NFS_Share::regular_ACL';

$config['is_allowed_to[列表文件].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[上传文件].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[创建目录].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[下载文件].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[修改文件].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[删除文件].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[修改目录].lab'][] = 'NFS_Share::regular_ACL';
$config['is_allowed_to[删除目录].lab'][] = 'NFS_Share::regular_ACL';

$config['is_allowed_to[管理文件分区].user'][] = 'NFS_Share::admin_ACL';
$config['is_allowed_to[管理文件分区].lab'][] = 'NFS_Share::admin_ACL';

$config['user_model.perms.enumerates'][] = 'NFS_Share::on_enumerate_user_perms';
$config['user_lab.connect'][] = 'NFS_Share::on_user_connect_lab';
$config['user_lab.disconnect'][] = 'NFS_Share::on_user_disconnect_lab';

$config['system.ready'][] = 'NFS_Share::setup';

$config['nfs.filter.files'][] = 'NFS_Share::filter_no_lab_files';

$config['nfs.stat'][] = 'NFS_Share::nfs_stat';

$config['lab.auto_open_lab'][] = 'NFS_Share::auto_open_lab';
$config['people.auto_open_people'][] = 'NFS_Share::auto_open_people';
$config['nfs_share.auto_open_all_people'][] = 'NFS_Share::auto_open_all_people';
$config['nfs_share.auto_open_all_lab'][] = 'NFS_Share::auto_open_all_lab';

$config['get_prefix_path'][] = 'NFS_Share::get_prefix_path';
$config['get_link_path'][] = 'NFS_Share::get_link_path';
$config['nfs_sub_path_tips'][] = 'NFS_Share::show_nfs_tips';

$config['nfs.list_dir'][] = 'NFS_Share::list_dir';

$config['is_allowed_to[查看各实验室分区].nfs_share'][] = 'NFS_Share::admin_ACL';
$config['is_allowed_to[查看文件系统所有].nfs_share'][] = 'NFS_Share::admin_ACL';

$config['sort.condition.selector'][] = 'NFS_Share::sort_condition_selector';
