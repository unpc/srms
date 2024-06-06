<?php 
//$config['is_allowed_to[列表文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
//$config['is_allowed_to[上传文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
//$config['is_allowed_to[下载文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
//$config['is_allowed_to[删除文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
//$config['is_allowed_to[修改文件].announce'][] = 'Announce_Access::announce_attachments_ACL';
//
//
//$config['is_allowed_to[查看所有].announce'][] = 'Announce_Access::announce_ACL';
//$config['is_allowed_to[删除].announce'][] = 'Announce_Access::announce_ACL';
//$config['is_allowed_to[修改].announce'][] = 'Announce_Access::announce_ACL';
$config['is_allowed_to[添加].announce'][] = 'Announce_Approval_Access::announce_ACL';
$config['is_allowed_to[列表审批].announce'][] = 'Announce_Approval_Access::announce_ACL';
$config['is_allowed_to[审批].announce'][] = 'Announce_Approval_Access::announce_ACL';

$config['is_allowed_to[列表文件].announce'][] = 'Announce_Approval_Access::announce_attachments_ACL';
$config['is_allowed_to[上传文件].announce'][] = 'Announce_Approval_Access::announce_attachments_ACL';
$config['is_allowed_to[下载文件].announce'][] = 'Announce_Approval_Access::announce_attachments_ACL';
$config['is_allowed_to[删除文件].announce'][] = 'Announce_Approval_Access::announce_attachments_ACL';
$config['is_allowed_to[修改文件].announce'][] = 'Announce_Approval_Access::announce_attachments_ACL';

$config['announces.need.approval'][] = 'Announce_Approval_Access::need_approval';



$config['announce.links'][] = 'Announce_Approval::links';
$config['announces.primary.tab'][] = 'Announce_Approval::announce_primary_tab';
$config['announces.primary.content'][] = 'Announce_Approval::announce_primary_content';
$config['announce.extra.selector'][] = 'Announce_Approval::extra_selector';
$config['announce_model.before_save'][] = 'Announce_Approval::on_announce_saved';




