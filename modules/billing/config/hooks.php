<?php

$config['controller[!billing/department/index].ready'][] = 'Billing_Account::setup';
$config['controller[!billing/department/index].ready'][] = 'Billing_Transaction::setup';

$config['controller[!labs/lab/index].ready'][] = 'Billing_Account::setup_lab';

$config['controller[admin/index].ready'][] = 'Billing_Admin::setup';

$config['billing_transaction_model.before_save'][] = 'Billing_Account::before_transaction_save';
$config['billing_transaction_model.saved'][] = 'Billing_Account::on_transaction_saved';
$config['lab_model.before_delete'][] = 'Billing_Account::before_lab_delete';
$config['billing_transaction_model.deleted'][] = 'Billing_Account::on_transaction_deleted';

//billing_department规则判断
$config['is_allowed_to[列表].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[查看].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[添加].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[修改].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[删除].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[列表财务帐号].billing_department'][] = 'Billing_Department::billing_department_ACL';
$config['is_allowed_to[添加财务帐号].billing_department'][] = 'Billing_Department::billing_department_ACL';
//打印、导出
$config['is_allowed_to[导出].billings'][] = 'Billing_Department::department_billings_ACL';
//lab中查看财务帐号权限规则判断
$config['is_allowed_to[列表财务帐号].lab'][] = 'Billing_Account::lab_ACL';

//billing_account权限规则判断
$config['is_allowed_to[查看].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[添加].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[修改].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[删除].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[充值].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[扣费].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[修改充值人员].billing_account'][] = 'Billing_Account::billing_account_ACL';
$config['is_allowed_to[修改扣费人员].billing_account'][] = 'Billing_Account::billing_account_ACL';

/*
NO.TASK#300(guoping.zhang@2010.12.11)
财务部门的列表收支明细的相关权限判断
*/
$config['is_allowed_to[列表收支明细].billing_department'][] = 'Billing_Transaction::billing_department_ACL';
$config['is_allowed_to[列表收支明细].billing_account'][] = 'Billing_Transaction::billing_account_ACL';
$config['is_allowed_to[列表收支明细].lab'][] = 'Billing_Transaction::lab_ACL';
$config['is_allowed_to[查看财务情况].lab'][] = 'Billing_Transaction::lab_ACL';
$config['is_allowed_to[查看财务概要].lab'][] = 'Billing_Transaction::lab_ACL';

$config['user.before_delete_message'][] = 'Billing_Transaction::before_user_save_message';

/* NO.BUG#286 根据权限判断sidebar是否显示billing图标 */
$config['module[billing].is_accessible'][] = 'Billing_Department::is_accessible';

$config['user_model.perms.enumerates'][] = 'Billing_Department::on_enumerate_user_perms';

$config['billing_check.setup'] = 'Billing_Check::setup';

$config['is_allowed_to[修改].billing_transaction'][] = 'Billing_Transaction::transaction_ACL';
$config['is_allowed_to[查看].billing_transaction'][] = 'Billing_Transaction::transaction_ACL';

#翻译备注
$config['billing_transaction_model.call.description'][] = 'Billing_Transaction::transaction_description';

$config['admin.billing.tab'][] = 'Billing_Admin::admin_billing_tab';

$config['equipment_model.call.cannot_access'][] = 'Billing_Check::cannot_access_equipment';
$config['equipment_model.call.cannot_be_reserved'][] = 'Billing_Check::cannot_reserv_equipment';
$config['equipment_model.call.cannot_be_sampled'][] = 'Billing_Check::cannot_sample_equipment';

// 提交送样、预约表单时对仪器限额做判断
$config['extra.form.validate'][] = 'Billing_Account::extra_form_validate';

//【通用可配】【上海交通大学医学院免疫所】RQ183304-系统定期给课题组PI发送财务明细
$config['controller[!billing/department].ready'][] = 'Billing_Notification::setup';
$config['lab_model.saved'][] = 'Billing_Notification::lab_saved';
$config['billing_notification.extra_display'][] = 'Billing_Notification::notification_show';

//判断是显示全部还是仅显示下属课题组财务明细
$config['billing.show_supervised_labs_department_transactions'][] = 'Billing_Transaction::show_supervised_labs_department_transactions';
$config['billing_department.show_supervised_labs'][] = 'Billing_Department::show_supervised_labs';

$config['custom_notification.billing.account.detail'] = 'Billing_Notification::custom_notification_billing_account_detail';

$config['billing.api.v1.accounts.GET'][] = 'Billing_API::accounts_get';
$config['billing.api.v1.stat.GET'][] = 'Billing_API::stat_get';
$config['billing.api.v1.departments.GET'][] = 'Billing_API::departments_get';
$config['billing.api.v1.transactions.GET'][] = 'Billing_API::transactions_get';
