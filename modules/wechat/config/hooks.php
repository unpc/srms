<?php
//$config['controller[admin/index].ready'][] = 'Wechat_Admin::setup';

//绑定
$config['user_model.call.wechat_bind'] = 'Wechat::user_call_bind';

//解绑
$config['user_model.call.wechat_unbind'] = 'Wechat::user_call_unbind';

//links 扩展
// $config['user.links'][] = 'Wechat::user_links';
$config['equipment.links'][] = 'Wechat::equipment_links';

$config['equipment.profile.extra_view'][] = 'Wechat::equipment_qrcode';
