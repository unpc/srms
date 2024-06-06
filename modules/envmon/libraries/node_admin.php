<?php

class Node_Admin
{

    public static function setup()
    {
        if (L('ME')->access('管理所有环境监控对象')) {
            Event::bind('admin.index.tab', 'Node_Admin::_primary_tab');
        }
    }

    static function _primary_tab($e, $tabs) {
        if (!Event::trigger('db_sync.need_to_hidden', 'envmon')) {
            Event::bind('admin.index.content', 'Node_Admin::_primary_content', 0, 'envmon');
            $tabs->add_tab('envmon', [
                'url'=>URI::url('admin/envmon'),
                'title'=>I18N::T('envmon', '环境监控')
            ]);
        }
    }

    public static function _primary_content($e, $tabs)
    {
        Event::bind('admin.envmon.content', 'Node_Admin::_secondary_notification_content', 0, 'message');

        $tabs->content = V('admin/view');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->add_tab('message', [
                'url'   => URI::url('admin/envmon.message'),
                'title' => I18N::T('envmon', '通知提醒'),
            ])
            ->tab_event('admin.envmon.tab')
            ->content_event('admin.envmon.content');
        $tabs->content->secondary_tabs->select('message');
    }

    public static function _secondary_notification_content($e, $tabs)
    {

        $configs = [
            'notification.envmon.sensor.warning',
            'notification.envmon.sensor.nodata',
        ];

        $vars = [];
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $form
                ->validate('title', 'not_empty', I18N::T('envmon', '消息标题不能为空!'))
                ->validate('body', 'not_empty', I18N::T('envmon', '消息内容不能为空!'));
            $vars['form'] = $form;

            if ($form->no_error && in_array($form['type'], $configs)) {
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

                /* 记录日志 */
                $me = L('ME');
                Log::add(strtr('[envmon] %user_name[%user_id]修改了系统设置中的环境监控通知邮件', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '内容修改成功'));
            }
        } elseif ($form['restore']) {
            Lab::set($form['type'], null);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '恢复系统默认设置成功'));
        }
        $views = Notification::preference_views($configs, $vars, 'envmon');

        $tabs->content = $views;
    }

}
