<?php

$config['tn_project_model.update.message'][] = 'Treenote::get_update_message';
$config['tn_project_model.saved'][] = 'Treenote::on_project_saved';
$config['tn_task_model.saved'][] = 'Treenote::on_task_saved';
$config['tn_note_model.saved'][] = 'Treenote::on_note_saved';

$config['controller[!update].ready'][] = 'Treenote::setup_update';
$config['controller[!people/profile].ready'][] = 'Treenote::setup_profile';
$config['nfs.stat'][] = 'Treenote::nfs_sphinx_update';
/*
  == 权限 ==
*/
$config['is_allowed_to[查看].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[列表].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[添加].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[修改].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[锁定].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[解锁].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[删除].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[完成].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[激活].tn_project'][] = 'Treenote::project_ACL';
$config['is_allowed_to[添加任务].tn_project'][] = 'Treenote::project_ACL';

$config['is_allowed_to[查看].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[列表].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[添加].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[修改].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[锁定].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[解锁].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[删除].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[评审].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[清除锁定].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[添加记录].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[添加任务].tn_task'][] = 'Treenote::task_ACL';
$config['is_allowed_to[管理任务].tn_task'][] = 'Treenote::task_ACL';

$config['is_allowed_to[查看].tn_note'][] = 'Treenote::note_ACL';
$config['is_allowed_to[列表].tn_note'][] = 'Treenote::note_ACL';
$config['is_allowed_to[添加].tn_note'][] = 'Treenote::note_ACL';
$config['is_allowed_to[修改].tn_note'][] = 'Treenote::note_ACL';
$config['is_allowed_to[锁定].tn_note'][] = 'Treenote::note_ACL';
$config['is_allowed_to[删除].tn_note'][] = 'Treenote::note_ACL';


$config['is_allowed_to[发表评论].tn_note'][] = "Treenote::note_comment_ACL";
$config['is_allowed_to[发表评论].tn_task'][] = "Treenote::task_comment_ACL";

$config['is_allowed_to[列表文件].tn_note'][] = 'Treenote::note_attachments_ACL';
$config['is_allowed_to[上传文件].tn_note'][] = 'Treenote::note_attachments_ACL';
$config['is_allowed_to[下载文件].tn_note'][] = 'Treenote::note_attachments_ACL';
$config['is_allowed_to[修改文件].tn_note'][] = 'Treenote::note_attachments_ACL';
$config['is_allowed_to[删除文件].tn_note'][] = 'Treenote::note_attachments_ACL';

$config['is_allowed_to[列表文件].tn_task'][] = 'Treenote::task_attachments_ACL';
$config['is_allowed_to[上传文件].tn_task'][] = 'Treenote::task_attachments_ACL';
$config['is_allowed_to[下载文件].tn_task'][] = 'Treenote::task_attachments_ACL';
$config['is_allowed_to[修改文件].tn_task'][] = 'Treenote::task_attachments_ACL';
$config['is_allowed_to[删除文件].tn_task'][] = 'Treenote::task_attachments_ACL';

$config['is_allowed_to[列表用户任务].user'][] = 'Treenote::user_ACL';

/* LIMS2 无 labs模块， 使用 treenote (xiaopei.li@2011.06.01) */
$config['achievements.publication.edit'][] = 'Treenote::on_achievement_edit';
$config['achievements.patent.edit'][] = 'Treenote::on_achievement_edit';
$config['achievements.award.edit'][] = 'Treenote::on_achievement_edit';

$config['publication_model.before_delete'][] = 'Treenote::before_project_relation_delete';
$config['patent_model.before_delete'][] = 'Treenote::before_project_relation_delete';
$config['award_model.before_delete'][] = 'Treenote::before_project_relation_delete';

$config['tn_task_model.before_delete'][] = 'Treenote::before_task_delete';

$config['achievements.publication.save_access'][] = 'Treenote::on_project_relation_saved';
$config['achievements.patent.save_access'][] = 'Treenote::on_project_relation_saved';
$config['achievements.award.save_access'][] = 'Treenote::on_project_relation_saved';

$config['comment_model.saved'][] = 'Treenote::on_comment_saved';
$config['comment_model.deleted'][] = 'Treenote::comment_deleted';
$config['newsletter.get_contents[research]'][] = 'Treenote::treenote_newsletter_content';
