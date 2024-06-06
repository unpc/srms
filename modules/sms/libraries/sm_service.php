<?php

class SM_Service
{
    static function edit_message_form($e, $user)
    {
        $e->return_value = (string)V('sms:profile/sms', ['user' => $user]);
    }

    static function edit_message_submit($e, $user, $form)
    {
        $form
            ->validate('binding_phone', 'not_empty', I18N::T('sms', '短信号码不能为空！'));
        $user->binding_phone = H($form['binding_phone']);
        $e->return_value = TRUE;
    }

    static function message_send_way_view($e, $user, $form)
    {
        $smsSend = Config::get('sms.message_send_by_sms');
        if (!$smsSend) return;
        $sendways = (array)Config::get('notification.handlers');

        if (L('ME')->id == $user->id && $sendways['sms']) {
            $e->return_value .= (string)V('sms:message/send.way', ['way' => $sendways['sms'], 'form' => $form]);
        }
    }

    static function message_send_way_submit($e, $message, $form)
    {
        $smsSend = Config::get('sms.message_send_by_sms');
        if (!$smsSend) return;

        $sendways = (array)Config::get('notification.handlers');

        if ($sendways['sms'] && $form['sms'] == 'on') {

            $user = $message->receiver;
            $sender = $message->sender;
            $title = $message->title;
            $body = $message->body;

            Notification_SMS::send($sender, $user, $title, $body);

            Log::add(strtr('[sms] %user_name[%user_id] 给用户[%receiver_name]发送了新短信 [%sms_title]', ['%user_name' => $sender->name, '%user_id' => $sender->id, '%receiver_name' => $user->name, '%sms_title' => $body]), 'journal');

        }
    }
}
