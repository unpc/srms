<?php
class Multi_Labs {
    static function add_member_exsit_token($e, $lab, $form) {
        $me = L('ME');
        $user_id = $form['user_id'];
        $user = O('user', $user_id);
        
        if (!$user->id) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '请选择成员!'));
            if ($form['ajax']) {
                JS::redirect($lab->url());
            } else {
                URI::redirect();
            }
        }
        if (!Q("{$user} {$lab}")->total_count()) {
            Event::trigger('lab.multi_lab.add_member', $lab, $user);
            $user->connect($lab);
            Log::add(strtr('[labs] %user_name[%user_id]添加了实验室%lab_name[%lab_id]的成员%member_name[%member_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%lab_name' => $lab->name, '%lab_id'=> $lab->id, '%member_name'=> $user->name, '%member_id'=> $user->id]), 'journal');
            if (Module::is_installed('nfs_share')) NFS_Share::setup_share($user);
        }
        
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '新成员已添加。'));
        if ($form['ajax']) {
            JS::redirect($lab->url());
        } else {
            URI::redirect($lab->url());
        }
    }
}