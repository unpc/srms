<?php

class Evaluate_Controller extends Base_Controller{
    public function index(){
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_ban_', 300);
//            $form = Lab::form(self::_transform());
            $form['form_token'] = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $this->layout->body->primary_tabs->select('evaluate');

        $this->_filter_and_set_view($form);
    }

    private function _filter_and_set_view($form){
        $me = L('ME');
        $form = Lab::form();

        $selector  = 'eq_record';

        //        $now = time();
        //        $selector .= "[dtstart<=$now]";

        $pre_selector = [];

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id){
            // $tag = O('tag', $form['group_id']);
            // $pre_selector[] = "$tag user";
        }
            //        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            //            if (!$me->is_allowed_to('查看机构', 'eq_banned')) {
            //                $pre_selector['me_equipment'] = "$me<@(incharge|contact) equipment<object";
            //            }
            //            else {
            //                $tag = $me->group;
            //                $pre_selector['tag_equipment'] = "$tag equipment<object";
            //            }
            //        }

        if ($form['name']) {
            $name = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }

        if ($form['eq_name']) {
            $eq_name = Q::quote($form['eq_name']);
            $pre_selector['equipment'] = "equipment[name*=$eq_name]";
        }

        if (!$pre_selector['user'] && $form['sort'] == 'name') $pre_selector['user'] = 'user';
        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        }
        elseif ($form['sort'] == 'eq_name') {
            $selector .= ":sort(eq_abbr {$sort_flag})";
        }
        elseif ($form['sort'] == 'mtime') {
            $selector .= ":sort(mtime {$sort_flag})";
        }
        else {
            $selector .= ':sort(mtime D)';
        }
//        Config::set('debug.database', true);
        $eva = Q($selector);
        $pagination = Lab::pagination($eva, (int)$form['st'], 15);

        $content = V('equipments:evaluate/list', [
            'form' => $form,
            'evas' => $eva,
//            'tag' => $tag,
            'root' => $root,
            'pagination' => $pagination,
//            'panel_buttons' => $panel_buttons,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
        ]);

        $this->layout->body->primary_tabs
            ->set('content', $content);
    }
}
