<?php

class Notification_Modal implements Notification_Handler
{
    public static function send($sender, $receivers, $title, $body)
    {
        
        $cache = Cache::factory('redis');

        $body = strip_tags(new Markup(stripslashes($body)));

        $params = [
            'title' => $title,
            'body' => $body
        ];

        $md5 = md5($body);

        foreach ($receivers as $receiver) {
            preg_match_all('/\(ID:(\d*?)\)/',$params['body'],$res);
            $equipment_id = $res[1][0] ?? 0;
            $notifications = $cache->get('eq_warning_modal_'.$receiver->id) ? : [];
            $notifications[$equipment_id][$md5] = $params;
            // heartbeat时弹出提醒，仅记录一个heartbeat的时间
            $cache->set('eq_warning_modal_'.$receiver->id, $notifications, 30 * 86400);

            Log::add(strtr('[messages] 系统发送弹框消息给%receiver_name[%receiver_id], 主题[%subject]', [
                '%receiver_name'=> $receiver->name,
                '%receiver_id'=> $receiver->id,
                '%subject'=> $title,
            ]), 'messages');
            
        }
    }
}
