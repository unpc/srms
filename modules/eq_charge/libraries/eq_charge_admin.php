<?php

class EQ_Charge_Admin
{

    public static function setup()
    {
        //Event::bind('lab.notifications.content','EQ_Charge_Admin::_secondary_charge_content');
    }

    //仪器使用费用超额提醒消息
    public static function _secondary_charge_content($e, $tabs, $sections)
    {

        /*
    之前在调整计费信息时候，已经不由实验室这边去控制了。
    $configs = array(
    'notification.eq_charge.charge_need_approve',
    );
    $vars = array();
    $form = Form::filter(Input::form());
    if($form['submit']){
    $form
    ->validate('title', 'not_empty', I18N::T('eq_charge', '消息标题不能为空！'))
    ->validate('body', 'not_empty', I18N::T('eq_charge', '消息内容不能为空！'));
    $vars['form'] = $form;
    if($form->no_error && in_array($form['type'], $configs)){
    $config = Lab::get($form['type'], Config::get($form['type']));
    $tmp = array(
    'description'=>$config['description'],
    'strtr'=>$config['strtr'],
    'title'=>$form['title'],
    'body'=>$form['body'],
    );
    foreach(Lab::get('notification.handlers') as $k=>$v){
    if(isset($form['send_by_'.$k])){
    $value = $form['send_by_'.$k];
    }else{
    $value = 0;
    }
    $tmp['send_by'][$k] = $value;
    }
    Lab::set($form['type'], $tmp);
    if($form->no_error){
    Log::add(strtr('[eq_charge] %user_name[%user_id]修改了系统设置中的使用费用超标提醒', array(
    '%user_name' => L('ME')->name,
    '%user_id' => L('ME')->id,
    )), 'journal');
    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '内容修改成功'));
    }
    }
    }
    elseif ($form['restore']) {
    Lab::set($form['type'], NULL);
    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '恢复系统默认设置成功'));
    }

    $sections[] = Notification::preference_views($configs, $vars, 'eq_charge');
     */

    }

    //equipment/admin/
    public static function secondary_tabs($e, $secondary_tabs)
    {

        /*$secondary_tabs->add_tab('charge', [
            'url'   => URI::url('admin/equipment.charge'),
            'title' => I18N::T('eq_charge', '计费通知提醒'),
        ]);*/

        Event::bind('admin.equipment.content', 'EQ_Charge_Admin::_notif_content', 0, 'charge');
    }

    //notif content
    static function _notif_content($e, $tabs) {
        $configs = self::get_notif_config();

        $vars = [];

        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', I18N::T('eq_charge', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('eq_charge', '消息内容不能为空！'));
                $vars['form'] = $form;
                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];
                    foreach (Lab::get('notification.handlers') as $k => $v) {
                        if (isset($form['send_by_' . $k])) {
                            $value = $form['send_by_' . $k];
                        } else {
                            $value = 0;
                        }
                        $tmp['send_by'][$k] = $value;
                    }
                    Lab::set($form['type'], $tmp);
                }
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '内容修改成功'));

                    $me = L('ME');
                    Log::add(strtr('[eq_sample] %user_name[%user_id]修改了仪器[%title]的提醒消息', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%title'     => $form['title'],
                    ]), 'journal');
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '恢复系统默认设置成功'));
            }
        }
        $views         = Notification::preference_views($configs, $vars, 'eq_sample');
        $tabs->content = $views;
    }

    static function get_notif_config()
    {
	    return [
            'notification.eq_charge.reserv_add_charge_to_user',
            'notification.eq_charge.reserv_add_charge_to_pi',
            'notification.eq_charge.reserv_edit_charge_to_user',
            'notification.eq_charge.reserv_edit_charge_to_pi',
            'notification.eq_charge.reserv_delete_charge_to_user',
            'notification.eq_charge.reserv_delete_charge_to_pi',
            'notification.eq_charge.record_add_charge_to_user',
            'notification.eq_charge.record_add_charge_to_pi',
            'notification.eq_charge.record_edit_charge_to_user',
            'notification.eq_charge.record_edit_charge_to_pi',
            'notification.eq_charge.record_delete_charge_to_user',
            'notification.eq_charge.record_delete_charge_to_pi',
            'notification.eq_charge.edit_sample_charge.sender',
            'notification.eq_charge.edit_sample_charge.pi',
            'notification.eq_charge.delete_sample_charge.sender',
            'notification.eq_charge.delete_sample_charge.pi',
            'notification.eq_charge.add_sample.sender',
            'notification.eq_charge.add_sample.pi',

        ];
    }

}
