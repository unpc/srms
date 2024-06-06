<?php

class EQ_Sample_Admin
{

    //equipment/admin/
    public static function secondary_tabs($e, $secondary_tabs)
    {

        $secondary_tabs->add_tab('sample_setting', [
            'url'   => URI::url('admin/equipment.sample_setting'),
            'title' => I18N::T('eq_sample', '送样设置'),
        ]);
        // Event::bind('admin.equipment.content', 'EQ_Sample_Admin::_notif_content', 0, 'sample');
        Event::bind('admin.equipment.content', 'EQ_Sample_Admin::_sample_setting', 0, 'sample_setting');
    }

    //notif content
    public static function _notif_content($e, $tabs)
    {

        $configs = Config::get('notification.eq_sample.templates');

        $vars = [];

        $form = Form::filter(Input::form());

        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', I18N::T('eq_sample', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('eq_sample', '消息内容不能为空！'));
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

    static function _sample_setting($e, $tabs){

        if (Input::form('submit')) {

            $form = Form::filter(Input::form());//这个验证

            if($form['add_sample_earliest_time'] < 0) {
                $form->set_error('add_sample_earliest_time', I18N::T('eq_sample', '添加预约最早提前时间必须大于等于零!'));
            }

            if($form['add_sample_latest_time'] < 0) {
                $form->set_error('add_sample_latest_time', I18N::T('eq_sample', '添加预约最晚提前时间必须大于等于零!'));
            }

            if($form['modify_sample_latest_time'] < 0) {
                $form->set_error('modify_sample_latest_time', I18N::T('eq_sample', '修改 / 删除预约最晚提前时间必须大于等于零!'));
            }

            if($form->no_error) {

                Lab::set('equipment.add_sample_earliest_limit', NULL, '*');
                Lab::set('equipment.add_sample_latest_limit', NULL, '*');
                Lab::set('equipment.modify_sample_latest_limit', NULL, '*');

                $specific_tags = $form['specific_tags'];
                $seeting_tags = [];

                if ($specific_tags) {
                    foreach ($specific_tags as $i => $tags) {
                        $tags = @json_decode($tags, TRUE);
                        if ($tags) foreach ($tags as $tag) {
                            $seeting_tags[] = $tag;
                            if ($form['specific_add_earliest_limit'][$i] == 'customize') {
                                $add_sample_earliest_limit = Date::convert_interval($form['specific_add_sample_earliest_time'][$i],$form['specific_add_sample_earliest_format'][$i]);
                                Lab::set('equipment.add_sample_earliest_limit', (int) $add_sample_earliest_limit, $tag);
                            }
                            else {
                                Lab::set('equipment.add_sample_earliest_limit', NULL, $tag);
                            }

                            if ($form['specific_add_latest_limit'][$i] == 'customize') {
                                $add_sample_latest_limit = Date::convert_interval($form['specific_add_sample_latest_time'][$i],$form['specific_add_sample_latest_format'][$i]);
                                Lab::set('equipment.add_sample_latest_limit', (int) $add_sample_latest_limit, $tag);
                            }
                            else {
                                Lab::set('equipment.add_sample_latest_limit', NULL, $tag);
                            }

                            if ($form['specific_modify_latest_limit'][$i] == 'customize') {
                                $modify_sample_latest_limit = Date::convert_interval($form['specific_modify_sample_latest_time'][$i],$form['specific_modify_sample_latest_format'][$i]);
                                Lab::set('equipment.modify_sample_latest_limit', (int) $modify_sample_latest_limit, $tag);
                            }
                            else {
                                Lab::set('equipment.modify_sample_latest_limit', NULL, $tag);
                            }

                        }
                    }
                }

                //清除删除的tag
                $tagged = (array) Lab::get('@TAG');
                foreach ($tagged as $tag => $data) {
                    if(!in_array($tag, $seeting_tags)){
                        Lab::set('equipment.add_sample_earliest_limit', NULL, $tag);
                        Lab::set('equipment.add_sample_latest_limit', NULL, $tag);
                        Lab::set('equipment.modify_sample_latest_limit', NULL, $tag);
                    }
                }

                $add_sample_earliest_limit = Date::convert_interval($form['add_sample_earliest_time'], $form['add_sample_earliest_format']);
                $add_sample_latest_limit = Date::convert_interval($form['add_sample_latest_time'], $form['add_sample_latest_format']);
                $modify_sample_latest_limit = Date::convert_interval($form['modify_sample_latest_time'], $form['modify_sample_latest_format']);

                Lab::set('equipment.add_sample_earliest_limit', (int) $add_sample_earliest_limit);
                Lab::set('equipment.add_sample_latest_limit', (int) $add_sample_latest_limit);
                Lab::set('equipment.modify_sample_latest_limit', (int) $modify_sample_latest_limit);

                Lab::set('equipment.need_sample_description', $form['need_sample_description']);

                /* 记录日志 */
                Log::add(strtr('[eq_sample] %user_name[%user_id]修改了系统设置中的预约设置',[
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                ]),'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '信息修改成功！'));

            }
        }

        $tabs->content=V('eq_sample:admin/sample', ['form'=>$form]);
    }

}
