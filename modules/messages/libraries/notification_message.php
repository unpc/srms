<?php

class Notification_Message implements Notification_Handler {

	static function send($sender, $receivers, $title, $body) {
        if (!$sender->id) {
            $send_by_system = TRUE;
            $body = $body. "\n\n". I18N::T('messages', "Labscout LIMS Team\n\n[系统消息, 请勿回复]");
        }

		foreach ($receivers as $receiver) {
			$message = O('message');
			$message->sender = $sender;	
			$message->receiver = $receiver;
			$message->title = (string)new Markup($title, FALSE);
			$message->body = addslashes($body);
			$message->save();

            if ($send_by_system) {
                Log::add(strtr('[messages] 系统发送消息给%receiver_name[%receiver_id], 主题[%subject]', [
                    '%receiver_name'=> $receiver->name,
                    '%receiver_id'=> $receiver->id,
                    '%subject'=> $title,
                ]), 'messages');
            }
            else {
                Log::add(strtr('[messages] %sender_name[%sender_id]发送消息给%receiver_name[%receiver_id], 主题[%subject]', [
                    '%sender_name'=> $sender->name,
                    '%sender_id'=> $sender->id,
                    '%receiver_name'=> $receiver->name,
                    '%receiver_id'=> $receiver->id,
                    '%subject'=> $title,
                ]), 'messages');
            }
		}
	}
}
