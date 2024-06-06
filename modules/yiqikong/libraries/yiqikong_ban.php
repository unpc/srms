<?php

class YiQiKong_Ban{

    static function on_eq_banned_saved($e, $ban) {
        $subject = $template['title'];
        if ($ban->equipment_id == 0) {
            $template = Config::get('notification.yiqikong_ban');
            $subject = I18N::T($i18n, $template['title']);
        } else {
            $template = Config::get('notification.yiqikong_eq_ban');
            $subject_params = [':equipment' => $ban->equipment->name];
            $subject = Notification::symbol_to_markup([I18N::T($i18n, $template['title'])], $subject_params);
        }
        $body = $template['body'];
        $body_params = [
            ':user' => $ban->user->name,
            ':reason' => $ban->reason
        ];
        $body = Notification::symbol_to_markup([I18N::T($i18n, $template['body'])], $body_params);
        $msg = [
            'sender' => 0,
            'receiver' => $ban->user->gapper_id,
            'subject' => $subject,
            'body' => $body[0],
            'tags' => ['yiqikong', 'ban'],
            'context' => [],
            'date' => date('Y-m-d H:i:s')
        ];
        Debade_Queue::of('YiQiKong')->push($msg, 'message');
    }
}
