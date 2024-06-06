<?php

// 末尾必须添加目录分隔符
$config['root'] = ROOT_PATH.'nfs/';
$config['enable_batch_operation'] = FALSE;

// 批量下载支持最大大小
$config['max_batch_size'] = 50 * 1024 * 1024;
// 允许上传的文件格式 默认全部允许
$config['allow'] = [];
$config['not_allow'] = ['php'];

// 开启大文件上传
$config['big_file'] = FALSE;

$config['big_file_max_size'] = 8 * 1024 * 1024 * 1024; // 允许上传的文件最大字节


$config['upload_button_css'] = 'font-weight:normal; font-size:14px; font-family: "Lucida Grande", Helvetica, Arial, sans-serif; margin:5px; padding-top:5px;color:#448EF6';
