<?php
//用于检测gapper-token，进行用户登录。
//设置为-10 是为了比application/library/application.php  中的ready先执行,为了让application初始化用户信息
$config['system.ready'][] = ['callback'=>'Gapper::ready', 'weight'=>-10];

$config['user_model.deleted'][] = 'Gapper::on_user_deleted';
