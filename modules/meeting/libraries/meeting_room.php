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

    static function batch_connect($subjects, $object, $s_name, $type = 'room') {
		switch ($s_name) {
			case 'equipment':
			case 'vidcam':
				break;
			default:
				return;
		}
		
		$old_subjects = Q("{$object}<{$type} {$s_name}")->to_assoc('id', 'id');
		if (count($subjects)) foreach ($subjects as $s_id) {
			if (!$s_id) continue;
			$subject = O("$s_name", $s_id);
			$subject_id = $subject->id;
			if (!$subject_id) continue;
			if (in_array($subject_id, $old_subjects)) {
				unset($old_subjects[$subject_id]);
				continue;
			}
			$subject->connect($object, $type);
			$me = L('ME');
			Log::add(strtr('[eq_door] %user_name[%user_id]关联%subject_name[%subject_id]与%object_name[%object_id]门禁', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%subject_name' => $subject->name,
						'%subject_id' => $subject->id,
						'%object_name' => $object->name,
						'%object_id' => $object->id,				
			]), 'journal');
		}
		
		if (count($old_subjects)) foreach ($old_subjects as $s_id) {
			$subject = O("$s_name", $s_id);
			$subject->disconnect($object, $type);
			$me = L('ME');
			Log::add(strtr('[eq_door] %user_name[%user_id]断开%subject_name[%subject_id]与%object_name[%object_id]的门禁', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%subject_name' => $subject->name,
						'%subject_id' => $subject->id,
						'%object_name' => $object->name,
						'%object_id' => $object->id,
			]), 'journal');
		}
	}
}
