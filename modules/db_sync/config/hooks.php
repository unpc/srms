<?php

//需要返回从站
$config['db_sync.back_to_slave'][] = 'DB_SYNC::back_to_slave';
//转换主站地址
$config['db_sync.transfer_to_master_url'][] = 'DB_SYNC::transfer_to_master_url';

//是否隐藏从站某些页面
$config['db_sync.need_to_hidden'][] = 'DB_SYNC::need_to_hidden';

// 技术支持更新 系统配置时 rpc更新所有从站点
$config['config_sync_slave_site'][] = 'System_Config_Sync::sync_slave_site';

$config['is_allowed_to[编辑].subsite'][] = 'Db_Sync_Access::subsite_ACL';
$config['is_allowed_to[删除].subsite'][] = 'Db_Sync_Access::subsite_ACL';

//$config['equipment[edit].view'][] = 'DB_SYNC::equipment_edit_info_view';

// 各子站点只可见自己站点下的仪器
// $config['equipment.extra.selector'][] = 'DB_SYNC::equipment_extra_selector';

//财务明细 所属站点字段
$config['extra.transactions.column'][] = "DB_SYNC::extra_site_column";
$config['extra.transactions.row'][] = "DB_SYNC::extra_site_row";
// $config['billing.transactions.extra_selector'][] = "DB_SYNC::extra_site_selector";

// $config['extra.billing_account_select'][] = 'DB_SYNC::extra_billing_account_select';
// $config['billing.department_transactions_selector'][] = 'DB_SYNC::department_traçnsactions_selector';

$config['people_list.panel_buttons'][] = 'DB_SYNC::people_list_panel_buttonsj';
$config['labs_list.panel_buttons'][] = 'DB_SYNC::labs_list_panel_buttons';

// 通用user，lab模块跳转
$config['orm_model.call.url'][] = 'DB_SYNC::orm_model_call_url';
$config['db_sync.message_delete_read_url'][] = 'DB_SYNC::message_delete_read_url';
$config['db_sync.message_batch_action_url'][] = 'DB_SYNC::message_batch_action_url';

// 视频监控添加删除页, 增加"所属站点"字段
// $config['vidcam[edit].view.extra'][] = "DB_Sync_Vidcam::edit_info_view";
// $config['vidcam[edit].post_submit_validate'][] = "DB_Sync_Vidcam::post_submit_validate";
// $config['vidcam[edit].post_submit'][] = "DB_Sync_Vidcam::post_submit";
// $config['extra.vidcam.column'][] = "DB_Sync_Vidcam::extra_site_column";
// $config['extra.vidcam.row'][] = "DB_Sync_Vidcam::extra_site_row";

// 视频监控 1校N区 展示/编辑/跳转逻辑
// $config['vidmon.vidcam.extra_selector'][] = "DB_Sync_Vidcam::extra_site_selector";
// $config['is_allowed_to[添加].vidcam'][] = ['callback' => 'DB_Sync_Vidcam::vidcam_ACL', 'weight' => -999];
// $config['vidcam.exitra.links'][] = "DB_Sync_Vidcam::extra_links";

// 门禁添加删除页, 增加"所属站点"字段
// $config['door[edit].view.extra'][] = "DB_Sync_Door::edit_info_view";
// $config['door[edit].post_submit_validate'][] = "DB_Sync_Door::post_submit_validate";
// $config['door[edit].post_submit'][] = "DB_Sync_Door::post_submit";
// $config['extra.door.column'][] = "DB_Sync_Door::extra_site_column";
// $config['extra.door.row'][] = "DB_Sync_Door::extra_site_row";

// 门禁管理 1校N区 展示/编辑/跳转逻辑
// $config['entrance.door.extra_selector'][] = "DB_Sync_Door::extra_site_selector";

// dc_record
// $config['extra.dc_record.column'][] = "DB_Sync_Dc_Record::extra_site_column";
// $config['extra.dc_record.row'][] = "DB_Sync_Dc_Record::extra_site_row";
// $config['entrance.dc_record.extra_selector'][] = "DB_Sync_Dc_Record::extra_site_selector";
// $config['dc_record.links_edit'][] = 'DB_Sync_Dc_Record::dc_record_links_edit';

//第三方模块同步基本信息额外字段
$config['equipment.info.api.extra'][] = 'DB_SYNC::equipment_info_api_extra';
//匹配子站点条件
// $config['db_sync.site.filter'][] = 'DB_SYNC::site_filter';

//子站管理员
$config['user_model.perms.enumerates'][] = 'Db_Sync_Access::on_enumerate_user_perms';

// 子站点仅可查看当前站点下的仪器
$config['equipment.extra.follows'][] = 'Db_Sync_Equipment::extra_follows';
// 分站管理员在主站仅可修改(操作)所属站点仪器
// 仪器主从同步，将该hooks写至站点下
// $config['is_allowed_to[修改].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[添加送样记录].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[添加仪器使用记录].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[修改仪器状态设置].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[添加公告].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[列表文件].equipment'][] = ['callback'=> 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[确认].eq_charge'][] = ['callback' => 'Db_Sync_Access::charge_confirm_ACL', 'weight' => -5];
// $config['is_allowed_to[修改].door'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[删除].door'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[修改].vidcam'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[删除].vidcam'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[修改].env_node'][]   = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[删除].env_node'][]   = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[修改].gis_building'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];
// $config['is_allowed_to[删除].gis_building'][] = ['callback' => 'Db_Sync_Access::object_ACL', 'weight' => -5];

// eq_record
$config['record.links_edit'][] = 'Db_Sync_Eq_Record::eq_object_links_edit';
$config['eq_record.list.columns'][] = 'DB_Sync_Eq_Record::extra_site_column';
$config['eq_record.list.row'][] = 'DB_Sync_Eq_Record::extra_site_row';
// $config['eq_record.extra_search.pre_selector'][] = 'DB_Sync_Eq_Record::extra_pre_selector';
$config['equipments.get.export.record.columns'][] = 'DB_Sync_Eq_Record::get_export_record_columns';
$config['equipments.export_columns.eq_record.new'][] = 'DB_Sync_Eq_Record::get_export_record_columns';

// eq_reserv
$config['eq_reserv.table_list.columns'][] = 'DB_Sync_Eq_Reserv::extra_site_column';
$config['eq_reserv.table_list.row'][] = 'DB_Sync_Eq_Reserv::extra_site_row';
//$config['eq_reserv.search.filter.submit'][] = 'DB_Sync_Eq_Reserv::reserv_search_filter_submit';
$config['eq_reserv.extra.export_columns'][] = 'DB_Sync_Eq_Reserv::get_export_reserv_columns';

// eq_sample
$config['eq_sample.links'][] = 'DB_Sync_Eq_Sample::eq_sample_links_edit';
$config['eq_sample.table_list.columns'][] = 'DB_Sync_Eq_Sample::extra_site_column';
$config['eq_sample.table_list.row'][] = 'DB_Sync_Eq_Sample::extra_site_row';
//$config['eq_sample.search.filter.submit'][] = 'DB_Sync_Eq_Sample::sample_search_filter_submit';
$config['eq_sample.extra.export_columns'][] = 'DB_Sync_Eq_Sample::get_export_sample_columns';

// eq_charge profile
$config['index_charges.table_list.columns'][] = 'DB_Sync_Eq_Charge::extra_site_column';
$config['index_charges.table_list.row'][] = 'DB_Sync_Eq_Charge::extra_site_row';
$config['eq_charge_export.cloumns'][] = 'DB_Sync_Eq_Charge::get_export_charge_columns';

// eq_charge lab
$config['lab_charges.table_list.row'][] = 'DB_Sync_Eq_Charge::extra_site_row';
$config['lab_charges.table_list.columns'][] = 'DB_Sync_Eq_Charge::extra_site_column';

// approval
$config['approval.table_list.columns'][] = 'DB_Sync_Approval::extra_site_column';
$config['approval.table_list.row'][] = 'DB_Sync_Approval::extra_site_row';

// training
$config['training.table_list.columns'][] = 'DB_Sync_Training::extra_site_column';
$config['training.table_list.row'][] = 'DB_Sync_Training::extra_site_row';

// 地理监控 楼宇
$config['gismon_building[edit].view.extra'][] = "DB_Sync_Gismon::edit_info_view";
$config['gismon_building[edit].post_submit_validate'][] = "DB_Sync_Gismon::post_submit_validate";
$config['gismon_building[edit].post_submit'][] = "DB_Sync_Gismon::post_submit";
$config['extra.gismon_building.column'][] = "DB_Sync_Gismon::extra_site_column";
$config['extra.gismon_building.row'][] = "DB_Sync_Gismon::extra_site_row";
$config['gismon.buildings.extra_selector'][] = "DB_Sync_Gismon::extra_site_selector";
$config['gismon.buildings.extra.links'][] = "DB_Sync_Gismon::extra_links";
$config['gismon.buildings.extra_data'][] = "DB_Sync_Gismon::extra_data";
$config['gismon.device.pickup_url'][] = 'DB_Sync_Gismon::device_pickup_url';
$config['gismon.device.move_url'][] = 'DB_Sync_Gismon::device_move_url';
$config['gismon.device.is_not_display'][] = "DB_Sync_Gismon::device_is_not_display";
$config['gismon.device.extra_selector'][] = "DB_Sync_Gismon::extra_device_site_selector";

//环境监控
$config['envmon[edit].view.extra'][] = "DB_Sync_Envmon::edit_info_view";
$config['envmon[edit].post_submit_validate'][] = "DB_Sync_Envmon::post_submit_validate";
$config['envmon[edit].post_submit'][] = "DB_Sync_Envmon::post_submit";
$config['extra.envmon.column'][] = "DB_Sync_Envmon::extra_site_column";
$config['extra.envmon.row'][] = "DB_Sync_Envmon::extra_site_row";

$config['envmon.envmon.extra_selector'][] = "DB_Sync_Envmon::extra_site_selector";
// $config['is_allowed_to[添加].vidcam'][] = ['callback' => 'DB_Sync_Vidcam::vidcam_ACL', 'weight' => -999];
$config['envmon.exitra.links'][] = "DB_Sync_Envmon::extra_links";

// billing_department
$config['billing_department.list.columns'][] = 'DB_Sync_Department::extra_site_column';
$config['billing_department.list.rows'][] = 'DB_Sync_Department::extra_site_row';
//$config['billing_department.search.filter.submit'][] = 'DB_Sync_Department::department_search_filter_submit';
$config['billing_department[edit].view.extra'][] = "DB_Sync_Department::edit_info_view";
$config['billing_department.links'][] = 'DB_Sync_Department::billing_department_links_edit';

// billing_account
// $config['billing_account.links'][] = 'DB_Sync_Account::billing_account_links_edit';

// billing_transaction
// $config['billing_transaction_model.saved'][] = 'Db_Sync_transaction::on_transaction_saved';
// $config['billing_transaction.links'][] = 'Db_Sync_transaction::billing_transaction_links_edit';

// 是否为子站管理员
$config['db_sync.is_subsite_admin'][] = 'DB_SYNC::is_subsite_admin';

//获取注册地址
$config['db_sync.get_signup_href'][] = 'DB_SYNC::get_signup_href';
$config['db_sync.get_lab_signup_href'][] = 'DB_SYNC::get_lab_signup_href';

// 一个跳转至主站点的按钮
$config['db_sync.jump_master_view'][] = 'DB_SYNC::jump_master_view';
// 仅仅是一个跳转至主站点的链接
$config['db_sync.jump_master_uri'][] = 'DB_SYNC::jump_master_url';
// add_css add_js add_button
$config['db_sync.slave_disable_input'][] = 'DB_SYNC::slave_disable_input';

$config['equipment[add].post_submit'][] = 'DB_Sync_Equipment::equipment_post_submit';
$config['equipment[edit].post_submit'][] = 'DB_Sync_Equipment::equipment_post_submit';
