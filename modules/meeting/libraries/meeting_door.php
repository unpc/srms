<?php

class Meeting_Door
{

    public static function setup()
    {
        Event::bind('door.index.tab', 'Meeting_Door::_view_door_tab', 0, 'meeting');
        Event::bind('door.index.tab.content', 'Meeting_Door::_view_door_content', 0, 'meeting');
    }

    public static function _view_door_tab($e, $tabs)
    {
        $door = $tabs->door;
        $tabs->add_tab('meeting', [
            'url'   => $door->url('meeting'),
            'title' => I18N::T('eq_door', '关联会议室'),
        ]);
    }

    public static function _view_door_content($e, $tabs)
    {
        $door          = $tabs->door;
        $meetings      = Q("{$door}<asso meeting");
        $content       = V('meeting:door/index', ['door' => $door, 'meetings' => $meetings]);
        $tabs->content = $content;
    }
}
