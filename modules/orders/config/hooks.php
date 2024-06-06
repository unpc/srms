<?php

$config['controller[admin/index].ready'][] = 'Order_Admin::setup';

// follow
$config['controller[!people/profile].ready'][] = 'Order::setup_people';

// update
$config['controller[!update].ready'][] = 'Order::setup_update';
$config['order_model.updating'][] = 'Order::get_update_parameter';
$config['order_model.update.message'][] = 'Order::get_update_message';
$config['order_model.update.message_view'][] = 'Order::get_update_message_view';

// utils
$config['order_model.saved'][] = 'Order::on_order_saved';
$config['order.markup.name'][] = 'Order::markup_name';

// perms
$config['is_allowed_to[列表].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[查看].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[添加申购].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[修改].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[取消].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[确认].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[订出].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[收货].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[管理订单].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[确认收货].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[编辑订单标签].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[导出].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[导入].order'][] = 'Order_Access::order_ACL';
$config['is_allowed_to[列表关注].user'][] = 'Order_Access::operate_follow_is_allowed';
$config['is_allowed_to[列表关注的订单].user'][] = 'Order_Access::operate_follow_is_allowed';
$config['is_allowed_to[关注].order'][] = 'Order_Access::operate_follow_is_allowed';
$config['is_allowed_to[取消关注].order'][] = 'Order_Access::operate_follow_is_allowed';
$config['is_allowed_to[发表评论].order'][] = 'Order_Access::order_comment_ACL';
$config['is_allowed_to[进入商城].order'][] = 'Order_Access::order_ACL';

$config['user_model.perms.enumerates'][] = 'Order::on_enumerate_user_perms';

$config['stock.get.links'][] = 'Order::extend_stock_links';
$config['stock.view'][] = 'Order::extend_stock_view';

$config['newsletter.get_contents[finance]'][] = ['callback' => 'Order::order_newsletter_content', 'weight' => -3];

$config['get_extra_order_confirm_view'][] = 'Order::get_extra_view';
$config['get_extra_order_request_view'][] = 'Order::get_extra_view';
$config['get_extra_order_edit_view'][] = 'Order::get_extra_edit_view';
$config['get_extra_order_order_view'][] = 'Order::get_extra_view';

$config['extra_basic_form_to_order'][] = 'Order::extra_basic_form_to_order';
