<?php

class Labs_Admin
{
    public static function setup()
    {
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        应用权限判断新规则
         */
        if (L('ME')->is_allowed_to('管理', 'lab')) { //用于操作实验室的学校和院系
            Event::bind('admin.index.tab', 'Labs_Admin::_primary_tab');
        }
    }

    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Labs_Admin::_primary_content', 0, 'labs');

        /*$tabs->add_tab('labs', [
            'url'   => URI::url('admin/labs'),
            'title' => I18N::T('labs', '实验室管理'),
        ]);*/
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.labs.content', 'Labs_Admin::_secondary_notification_content', 0, 'notifications');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->add_tab('notifications', [
                'url'   => URI::url('admin/labs.notifications'),
                'title' => I18N::T('labs', '通知提醒'),
            ])
            ->set('class', 'secondary_tabs')
            ->content_event('admin.labs.content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs->select($params[1]);

    }

    public static function _secondary_notification_content($e, $tabs)
    {

        $sections = new ArrayIterator;
        Event::trigger('lab.notifications.content', $tabs, $sections);
        $tabs->content = V('labs:admin/edit_notifications', ['sections' => $sections]);

    }

}
