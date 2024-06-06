<?php

class Equipment_AJAX_Controller extends AJAX_Controller {

    function index_equipment_view() {
        $me = L('ME');
        $equipments = $this->get_equipments($me);
        $equipment = array_keys($equipments)[0];
        $im_tags = $this->get_tags($equipment);
        JS::dialog(V('equipment', ['equipments' => $equipments, 'equipment' => $equipment, 'im_tags' => $im_tags]), ['title' => I18N::T('impression', '输入仪器检测的关键词')]);
    }

    function index_equipment_im_tag_submit() {
        $me = L('ME');
        $now = time();
        $form = Form::filter(Input::form());
        $tags = explode('|', $form['tags']);
        if (count($tags) == 1 && !$tags[0]) {
            $form->set_error('im_tag', I18N::T('impression', '标签不能为空!'));
        }
        if (count($tags) < 3) {
            $form->set_error('im_tag', I18N::T('impression', '您至少要为一台仪器添加3个关键字!'));
        }
        if ($form->no_error) {
            foreach ($tags as $tag) {
                $im_tag = O('im_tag', ['name' => trim($tag)]);    
                if (!$im_tag->id) {
                    $im_tag = O('im_tag');  
                    $im_tag->name = trim($tag);
                    $im_tag->name_abbr = PinYin::code(trim($tag));
                    $im_tag->ctime = $now;
                    $im_tag->mtime = $now;
                    $im_tag->save();
                }
                $equipment = O('equipment', $form['equipment']);
                $im_record = O('im_record', ['source' => $equipment, 'user' => $me, 'im_tag' => $im_tag]);
                if (!$im_record->id) {
                    $im_record->source = $equipment;
                    $im_record->user = $me;
                    $im_record->im_tag = $im_tag;
                    $im_record->ctime = $now;
                } else {
                    $im_record->count += 1;
                }
                if ($im_record->save()) {
                    $db = Database::factory();
                    $sql = sprintf('UPDATE eq_record SET has_impression=1 WHERE user_id=%s AND equipment_id=%s', (int)$me->id, (int)$form['equipment']);
                    $eqs = $db->query($sql);
                    JS::redirect(URI::url('/'));
                }
            }
        } else {
            $equipments = $this->get_equipments($me);
            $equipment = array_keys($equipments)[0];
            $im_tags = $this->get_tags($equipment);
            foreach ($im_tags as $key => $im_tag) {
                foreach ($tags as $index => $tag) {
                    if (trim($im_tag) == trim($tag)) {
                        $im_tags[$key] = [trim($im_tag), 'hover'];
                        unset($tags[$index]);
                    }
                }
            }
            JS::dialog(V('equipment', ['equipments' => $equipments, 'equipment' => $equipment, 'im_tags' => $im_tags, 'form' => $form, 'tags' => $tags]));
        }
    }

    function index_equipment_change() {
        $form = Form::filter(Input::form());
        $im_tags = $this->get_tags($form['equipment_id']);
        Output::$AJAX['tags'] = $im_tags;
    }

    function get_equipments($user) {
        $db = Database::factory();
        $sql = sprintf('SELECT equipment_id FROM eq_record WHERE user_id=%s AND has_impression=0 GROUP BY equipment_id ORDER BY dtstart DESC', (int)$user->id);
        $eqs = $db->query($sql)->rows();
        $equipments = [];
        foreach ($eqs as $eq) {
            $eq_object = O('equipment', $eq->equipment_id);
            $equipments[$eq_object->id] = $eq_object->name;
        }

        return $equipments;
    }

    function get_tags($equipment) {
        $db = Database::factory();
        $sql = sprintf("SELECT im_tag_id FROM im_record WHERE source_name='equipment' AND source_id=%s GROUP BY im_tag_id", (int)$equipment);
        $tags = $db->query($sql)->rows();
        $im_tags = [];
        foreach ($tags as $tag) {
            $tag_obj = O('im_tag', $tag->im_tag_id);
            $im_tags[] = $tag_obj->name;
        }
        return $im_tags;
    }
}
