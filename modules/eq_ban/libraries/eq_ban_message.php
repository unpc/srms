<?php
class Eq_Ban_Message {
    private static function contact_info() {
        $objects = func_get_args();
        $ci = [];
        foreach ($objects as $object) {
            if (!$ci['phone'] && $object->phone) $ci['phone'] = 'T: '.$object->phone;
            if (!$ci['email'] && $object->email) $ci['email'] = 'M: '.$object->email;
            if (count($ci) == 2) break;
        }
        return implode(', ', $ci);
    }

    static function add ($eq_ban) {
        $me = L('ME');
        $object = $eq_ban->object;
        $user = $eq_ban->user;

        if (!$object->id) {
            Notification::send('eq_ban.eq_banned', $user, [
                '%user'=>Markup::encode_Q($user),
                '%reason'=>$eq_ban->reason,
            ]);
        }
        //如果用户是被单个仪器封禁的话， 则按照该仪器的加黑消息模板方式来发送消息
        elseif ($object->name() == 'equipment' && $object->id) {
            Notification::send('eq_ban.eq_banned.eq|'.$object->id, $user, [
                '%user'=>Markup::encode_Q($user),
                '%reason'=>$eq_ban->reason,
                '%equipment'=>Markup::encode_Q($object),
                '%incharge' => $me->id ? Markup::encode_Q($me) : '系统',
                '%contact_info'=> self::contact_info($object, $me),
            ]);
        }
        elseif ($object->name() == 'tag_group' && $object->id) {
            $root = Tag_Model::root('group');
            Notification::send('eq_ban.eq_banned.tag', $user, [
                '%user'=>Markup::encode_Q($user),
                '%reason'=>$eq_ban->reason,
                '%tag'=> $root->id != $object->id ? $object->name : I18N::T('eq_ban', '全部'),
                '%incharge' => $me->id ? Markup::encode_Q($me) : '系统',
                '%contact_info'=> self::contact_info($object, $me),
            ]);
        }
    }

    static function edit_banned () {
        Event::bind('equipment.edit.tab', 'Eq_Ban_Message::_edit_banned_tab', 0, 'banned');
        Event::bind('equipment.edit.content', 'Eq_Ban_Message::_edit_banned_content', 0, 'banned');
    }

    static function _edit_banned_tab ($e, $tabs) {
        $equipment = $tabs->equipment;
        if($GLOBALS['preload']['equipment.enable_specific_blacklist']) {
            if (L('ME')->is_allowed_to('修改黑名单设置', $equipment)) {
                $tabs->add_tab('banned', [
                    'url'=> $equipment->url('banned', NULL, NULL, 'edit'),
                    'title'=>I18N::T('eq_ban', '黑名单设置'),
                    'weight' => 80
                ]);
            }
        }
    }

    static function _edit_banned_content ($e, $tabs) {
        $equipment = $tabs->equipment;
        $configs = [
            'notification.eq_ban.eq_banned.eq',
        ];
        $vars = [];
        $form = Form::filter(Input::form());
        if($form['submit']){
            if (!L('ME')->is_allowed_to('修改黑名单设置', $equipment)) {
                URI::redirect('error/401');
            }
            $form
                ->validate('title', 'not_empty', I18N::T('eq_ban', '消息标题不能为空！'))
                ->validate('body', 'not_empty', I18N::T('eq_ban', '消息内容不能为空！'));
            $vars['form'] = $form;
            if($form->no_error && in_array($form['type'], $configs)){
                $config = $equipment->banned_setting;
                if (is_null($config)) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                }
                $tmp = [
                    'description'=>$config['description'],
                    'strtr'=>$config['strtr'],
                    'title'=>$form['title'],
                    'body'=>$form['body']
                ];
                foreach(Lab::get('notification.handlers') as $k=>$v){
                    if(isset($form['send_by_'.$k])){
                        $value = $form['send_by_'.$k];
                    }
                    else{
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = [
                            $v['text'],
                            $value
                    ];
                }
                $equipment->banned_setting = $tmp;
                //Lab::set($form['type'], $tmp);
                if($equipment->save()){
                    $me = L('ME');
                    Log::add(strtr('[eq_ban] %user_name[%user_id]修改了%equipment_name[%equipment_id]仪器的黑名单设置', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                        ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '内容修改成功'));
                }
            }
        }
        elseif ($form['restore']) {
            $equipment->banned_setting = NULL;
            if ($equipment->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '恢复系统默认设置成功'));
            }
        }
        $vars += (array) $equipment->banned_setting;
        $vars += ['icon' => $equipment->icon('104')];
        $views = Notification::preference_views($configs, $vars, 'eq_ban');
        /**
         * 
         * @todo
         * 这里只能先小天才一下了
         * 但是我是无法忍受的
         * 
         * 这里是是为views不是View对象，导致无法在view.php里被套上一层标签，记录一下
         */
        $tabs->content = '<div class="tab_content">' . (string) $views . '</div>';
    }


    static function get_template($e, $conf_key) {
        $arr = explode('|', $conf_key);
        if ($arr[0] == 'notification.eq_ban.eq_banned.eq') {
            if ($arr[1]) {
                $equipment = O('equipment', $arr[1]);
                if ($equipment->id) {
                    $configs = Lab::get($arr[0]) ?: Config::get($arr[0]);
                    $e->return_value = $equipment->banned_setting ? $equipment->banned_setting : $configs;
                }
            }
        }
    }

    static function get_template_name($e, $conf_key) {
        $arr = explode('|', $conf_key);
        if ($arr[0] == 'notification.eq_ban.eq_banned.eq') {
            $e->return_value = $arr[0];
        }
    }
}
