<?php

class Notification_Email implements Notification_Handler {

	static function send($sender, $receivers, $title, $body) {
		if ( !is_object($sender) ) $sender = (object)$sender;
		if ( !$sender->id ){
		 	$attention = I18N::T('people','系统消息,请勿回复');	
		 	$body .= "\n\n[$attention]";
			$sender->email = Config::get('system.email_address');
			$sender->name = Config::get('system.email_name', $sender->email);
		}

		$mails = [];
		foreach ($receivers as $receiver) {
			$mails[] = $receiver->get_binding_email();
		}

        $title = new Markup($title, FALSE);
        $body_plain = new Markup($body, FALSE);
        $body_html = new Markup($body, TRUE);

		$email = new Email($sender);
		$email->to($mails);

		$email->subject($title);
		$email->body($body_plain, $body_html);
		$email->reply_to($sender->email, $sender->name);

		$email->send();
	}

}

