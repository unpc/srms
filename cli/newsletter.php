#!/usr/bin/env php

<?php

require "base.php";

$pi_token = Config::get('lab.pi');
$lab_name = Config::get('lab.name');
$pi = O('user',['token'=>$pi_token]);
$base_url = Config::get('system.base_url');

$categories = Config::get('newsletter.categories');
$nl_cat_vis = json_decode($pi->nl_cat_vis, TRUE);

if (!$nl_cat_vis || in_array("true", $nl_cat_vis)) {
    foreach ($categories as $key => $value) {
        if ($nl_cat_vis[$key] || $nl_cat_vis  == NULL) {
            $content[$key] = Event::trigger('newsletter.get_contents['.$key.']', $pi);
            $category .= Newsletter::get_category_view($content[$key], $key);
        }
    }
    if (strlen($category) == 0) {
        $category = '暂无更新';
    }

    $arr = [];
    if (count($nl_cat_vis)) {
        foreach ($nl_cat_vis as $key => $value) {
            if (!$value) {
                $arr[] = $categories[$key]['title'];
            }
        }
    }
    $note = Newsletter::get_note_view($arr);
    $body = $category.$note;

    $view = Newsletter::get_html($pi, $body);
    $view = wordwrap($view, 900, "\n"); 

    $sender = O('user');
    $sender->email = Config::get('system.email_address');
    $sender->name = Config::get('system.email_name', $sender->email);
	
	if (!$pi->email) {
		Log::add('因为PI没有相应的邮箱地址，故无法正常发送邮件!', 'mail');
	}
	else {
		$mail = new Email($sender);
		$mail->to($pi->email);
		$mail->subject(T('您的实验室每日动态更新'));
		$view_html = new Markup($view, TRUE);
		$mail->body($view, $view_html);
		$mail->send();
	}

	// newsletter 使用初期需发给 support
	$mail->to('support@geneegroup.com');
	$mail->subject("发给 {$pi->name}<{$pi->email}> 的" . HT('实验室的更新内容'));
	$view_html = new Markup($view, TRUE);
	$mail->body($view, $view_html);
	$mail->send();

}

