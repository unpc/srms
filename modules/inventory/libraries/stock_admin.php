<?php

class Stock_Admin
{
    public static function setup()
    {
        //权限判断: 超级管理员或者具有'管理存货'权限的用户才能看到系统设置
        $me = L('ME');
        if (!$me->is_allowed_to('管理存货', 'stock') && !in_array($me->token, Config::get('lab.admin'))) {
            return;
        }

        Event::bind('admin.index.tab', 'Stock_Admin::_primary_tab');
    }

    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Stock_Admin::_primary_content', 100, 'stock');
        $tabs->add_tab('stock', [
            'url'    => URI::url('admin/stock'),
            'title'  => I18N::T('inventory', '存货管理'),
            'weight' => 90,
        ]);

    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.stock.content', 'Stock_Admin::_secondary_expiration_setting', 0, 'expiration');
        Event::bind('admin.stock.content', 'Stock_Admin::_secondary_notification_setting', 1, 'notification');

        if (Module::is_installed('extra')) {
            Event::bind('admin.stock.content', 'Stock_Admin::_secondary_extra_setting', 2, 'extra');
        }

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->add_tab('expiration', [
                'url'   => URI::url('admin/stock.expiration'),
                'title' => I18N::T('inventory', '过期设置'),
            ])
            ->add_tab('notification', [
                'url'   => URI::url('admin/stock.notification'),
                'title' => I18N::T('inventory', '消息提醒'),
            ])
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.stock.tab')
            ->content_event('admin.stock.content');

        if (Module::is_installed('extra')) {
            $tabs->content->secondary_tabs->add_tab('extra', [
                'url'   => URI::url('admin/stock.extra'),
                'title' => I18N::T('inventory', '存货表单'),
            ]);
        }

        $params = (array) Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);
    }

    public static function _secondary_expiration_setting($e, $tabs)
    {
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form
                ->validate('defaut_notice_days', 'number(>=0)', I18N::T('inventory', '提前提醒时间必须大于等于0'));
            if ($form->no_error) {
                $inform_peole = (array) @json_decode($form['inform_people'], true);

                Lab::set('stock.default.expire_notice_days', H($form['defaut_notice_days']));
                Lab::set('stock.default.expire_inform_people', $inform_peole);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '过期设置更新成功!'));
            }

        }

        $tabs->content = V('inventory:admin/expiration', [
            'form' => $form,
        ]);

    }

    public static function _secondary_notification_setting($e, $tabs)
    {
        $configs = (array) Config::get('notification.stock_admin.notification');
        $vars    = [];
        $form    = Form::filter(Input::form());
        if ($form['submit']) {
            $form
                ->validate('title', 'not_empty', I18N::T('inventory', '消息标题不能为空!'))
                ->validate('body', 'not_empty', I18N::T('inventory', '消息内容不能为空!'));
            $vars['form'] = $form;
            if ($form->no_error && in_array($form['type'], $configs)) {
                $config = Lab::get($form['type'], Config::get($form['type']));
                $tmp    = [
                    'description' => $config['description'],
                    'strtr'       => $config['strtr'],
                    'title'       => $form['title'],
                    'body'        => $form['body'],
                ];

                foreach ((array) Lab::get('notification.handlers') as $k => $v) {
                    if (isset($form['send_by_' . $k])) {
                        $value = $form['send_by_' . $k];
                    } else {
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = $value;

                }

                Lab::set($form['type'], $tmp);
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '内容修改成功'));
                }
            }
        } elseif ($form['restore']) {
            Lab::set($form['type'], null);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('inventory', '恢复系统默认设置成功'));
        }

        $views         = Notification::preference_views($configs, $vars, 'inventory');
        $tabs->content = $views;
    }

    public static function _secondary_extra_setting($e, $tabs)
    {
        $views         = V('inventory:admin/extra');
        $tabs->content = $views;
    }
}
