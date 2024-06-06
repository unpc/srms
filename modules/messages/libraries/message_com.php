<?php

class Message_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;

        $components[] = [
            'id' => 'unRead',
            'key' => 'unRead',
            'name' => '未读消息',
        ];

        $e->return_value = $components;
        return TRUE;
    }

    static function view_unRead ($e, $query) {
        $me = L('ME');
        if (!$me->id) return FALSE;
        
        $selector = "message[receiver={$me}][is_read=0]:sort(ctime DESC)";
        $messages = Q($selector)->limit(10);

        $sys_url = Config::get('redirect.component');
        
        $view = V('messages:components/view/unRead', [
            'sys_url' => $sys_url,
            'messages' => $messages
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

}
