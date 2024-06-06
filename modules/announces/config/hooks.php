<?php 
$config['is_allowed_to[列表文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
$config['is_allowed_to[上传文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
$config['is_allowed_to[下载文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
$config['is_allowed_to[删除文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
$config['is_allowed_to[修改文件].announce'][] = 'Announce_Access::announce_attachments_ACL';


$config['is_allowed_to[查看所有].announce'][] = 'Announce_Access::announce_ACL';
$config['is_allowed_to[删除].announce'][] = 'Announce_Access::announce_ACL';
$config['is_allowed_to[修改].announce'][] = 'Announce_Access::announce_ACL';
$config['is_allowed_to[添加].announce'][] = 'Announce_Access::announce_ACL';
$config['is_allowed_to[管理].announce'][] = 'Announce_Access::announce_ACL';

$config['controller[*].ready'][] = ['callback' => 'Announce::force_read', 'weight' => 100];
$config['announce_model.saved'][] = 'Announce::on_announce_saved';

$config['announce.before.add'][] = 'Announce::delete_attachments';
