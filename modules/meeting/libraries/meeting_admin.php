<?php

class Meeting_Admin
{

    public static function setup()
    {
        $me = L('ME');
        if ($me->access('添加/修改所有会议室')) {
            Event::bind('admin.index.tab', 'Meeting_Admin::_primary_tab');
        }
    }

    static function _primary_tab ($e, $tabs) {
        $tabs->add_tab('meeting', [
            'url'=>URI::url('admin/meeting'),
            'title'=> I18N::T('meeting', '会议室管理'),
        ]);
        Event::bind('admin.index.content', 'Meeting_Admin::_primary_content', 0, 'meeting');
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');
        Event::bind('admin.meeting.content', 'Meeting_Admin::_secondary_tag_content', 0, 'tag');

        $secondary_tabs                = Widget::factory('tabs');
        $tabs->content->secondary_tabs = $secondary_tabs
            ->set('class', 'secondary_tabs')
            ->add_tab('tag', [
                'url'   => URI::url('admin/meeting.tag'),
                'title' => I18N::T('meeting', '用户标签'),
            ])
            ->tab_event('admin.meeting.tab')
            ->content_event('admin.meeting.content');

        Event::trigger('admin.meeting.secondary_tabs', $secondary_tabs);

        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);
    }
	
	public static function _secondary_tag_content ($e, $tabs) {
        if (Module::is_installed('db_sync') && DB_SYNC::is_slave()) $is_slave = true;
        $tabs->content = V('meeting:admin/user_tags/tags', ['is_slave' => $is_slave]);
	}
}
