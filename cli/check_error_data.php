#!/usr/bin/env php
<?php
// 此脚本是检查系统中各类错误记录的框架, 会定时(每天)运行
// 运行时将 Config::get('check'), 并根据配置执行检查
// 各 module/lab 可在 config/check.php 中按要求增加配置
//  hourly? dayly? weekly?

require 'base.php';


$checks = (array) Config::get('check');

foreach ($checks as $title => $opts) {
	$title = Config::get('page.title_default') . $opts['title'] ? : $title;

	$result = call_user_func($opts['check_callback']);

	noti($title, $result);
	// error_log_noti($title, $result);
}

function error_log_noti($title, $content) {
	error_log("==== Checking $title");
	error_log($content);
}

function noti($title, $content) {

	$email = new Email;

	$receiver ='support@geneegroup.com';
	$email->to($receiver);
	$email->subject($title . date('Ymd'));
	$email->body($content);

	$email->send();
}
