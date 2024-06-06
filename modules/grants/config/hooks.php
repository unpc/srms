<?php
$config['is_allowed_to[列表].grant'][] = 'Grants::grant_ACL';
$config['is_allowed_to[查看].grant'][] = 'Grants::grant_ACL';
$config['is_allowed_to[添加].grant'][] = 'Grants::grant_ACL';
$config['is_allowed_to[修改].grant'][] = 'Grants::grant_ACL';
$config['is_allowed_to[删除].grant'][] = 'Grants::grant_ACL';

$config['is_allowed_to[添加支出].grant'][] = 'Grants::grant_ACL';
$config['is_allowed_to[修改支出].grant'][] = 'Grants::grant_ACL';

$config['is_allowed_to[修改].grant_expense'][] = 'Grants::grant_expense_ACL';
//配置打印、导出
$config['is_allowed_to[导出].grant'][] = 'Grants::grant_ACL';
$config['user.view.general.sections'][] = 'Grants::user_general_sections';

$config['grant_expense_model.call.is_locked'][] = ['callback' => 'Grants::expense_is_locked'];
$config['newsletter.get_contents[finance]'][] = ['callback' => 'Grants::grant_newsletter_content', 'weight' => -1];

//BUG#5212删除
//$config['controller[admin/index].ready'][] = 'Grants_Admin::setup';
