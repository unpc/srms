<?php

class Meeting_Room {

    public static function setup()
    {
        $me = L('ME');
        if ($me->access('添加/修改所有会议室')) {
            Event::bind('admin.meeting.tab', 'Meeting_Room::secondary_room_tab');
            Event::bind('admin.meeting.content', 'Meeting_Room::secondary_room_content', 0, 'group');
        }
    }

    public static function secondary_room_tab ($e, $tabs) 
    {
        $tabs->add_tab('group', [
            'url'   => URI::url('admin/meeting.group'),
            'title' => I18N::T('meeting', '空间分组'),
            'weight' => -1
        ]);
    }

    public static function secondary_room_content ($e, $tabs) 
    {
        $root = Tag_Model::root('room', '空间分组');
		$tags = Q("tag_room[parent={$root}]:sort(weight A)");
		Controller::$CURRENT->add_js('tag_sortable');

		$uniqid = "tag_".uniqid();
        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
		$tabs->content = V('application:admin/tags/tag_root', [
            'tags' => $tags, 
            'root' => $root,
            'uniqid' => $uniqid, 
            'title' => '空间分组', 
            'button_title' => '分组'
        ]);
	}
}
