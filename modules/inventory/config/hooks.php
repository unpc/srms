<?php
// follow
$config['controller[!people/profile].ready'][] = 'Stock::setup_people';

$config['controller[admin/index].ready'][] = 'Stock_Admin::setup';

// update
$config['controller[!update].ready'][] = 'Stock::setup_update';
$config['stock_model.updating'][] = 'Stock::get_update_parameter';
$config['stock_model.update.message'][] = 'Stock::get_update_message';
$config['stock_model.update.message_view'][] = 'Stock::get_update_message_view';

$config['update_stock_status'][] = 'Stock::update_stock_status';

// stock_use(xiaopei.li@2011.10.20)
$config['stock_use_model.saved'][] = 'Stock::on_stock_use_saved';

$config['stock_model.deleted'][] = 'Stock::on_stock_deleted';

// perms
$config['is_allowed_to[列表].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[查看].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[添加].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[修改].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[删除].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[领用/归还].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[代人领用/归还].stock'][] = 'Stock_Access::stock_ACL';
$config['is_allowed_to[列表关注].user'][] = 'Stock_Access::operate_follow_is_allowed';
$config['is_allowed_to[列表关注的存货].user'][] = 'Stock_Access::operate_follow_is_allowed';

$config['is_allowed_to[关注].stock'][] = 'Stock_Access::operate_follow_is_allowed';
$config['is_allowed_to[取消关注].stock'][] = 'Stock_Access::operate_follow_is_allowed';


//添加打印导出的事件绑定
$config['is_allowed_to[导出].stocks'][] = 'Stock_Access::stock_ACL';

$config['is_allowed_to[管理存货].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[列表文件].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[上传文件].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[创建目录].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[下载文件].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[修改文件].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[删除文件].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[修改目录].stock'][] = 'Stock::user_ACL';
$config['is_allowed_to[删除目录].stock'][] = 'Stock::user_ACL';

$config['is_allowed_to[修改].stock_use'][] = 'Stock_Use::user_ACL';
$config['is_allowed_to[删除].stock_use'][] = 'Stock_Use::user_ACL';

$config['user_model.perms.enumerates'][] = 'Stock::on_enumerate_user_perms';
$config['newsletter.get_contents[finance]'][] = ['callback' => 'Stock::inventory_newsletter_content', 'weight' => -2];
// $config['labnotes.stock.edit'][] = 'Stock::before_labnote_edit';

//RQ133911存货增加过期时间，stock表中没有name字段，需要使用product_name字段
$config['stock.markup.name'] = 'Stock::markup_name';

$config['extra.category_rename'] = 'Stock::type_rename';
$config['extra.category_delete'] = 'Stock::type_delete';
