<?php

class List_Controller extends Base_Controller {

	function index() {
        $me = L('ME');	
        if (!$me->is_allowed_to('列表统计', 'eq_stat')) URI::url('error/401');
        //TODO 权限完善

        $form_token = Input::form('form_token');

        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        }
        else {
            $form_token = Session::temp_token('eq_stat_', 300);

       	 	$form = Lab::form(function(&$old_form, &$form) {
       	 		unset($old_form['type']);
       	 		if (isset($form['date_filter'])) {
       	 			if (!$form['dtstart_check']) {
       	 				unset($old_form['dtstart_check']);
       	 			}
       	 			else {
       	 				$form['dtstart'] = strtotime('midnight', $form['dtstart']);
       	 			}
       	 			if (!$form['dtend_check']) {
       	 				unset($old_form['dtend_check']);
       	 			}
					else {
						$form['dtend'] = strtotime('midnight', $form['dtend'] + 86400) - 1;
					}
       	 			unset($form['date_filter']);
       	 		}
            });
            $_SESSION[$form_token] = $form;
       	 }

        $selector = 'equipment';


        $form_selector = [];

        if ($form['dtstart_check']) {
            $dtstart = Q::quote($form['dtstart']);
            $form_selector['dtstart'] = $dtstart;
        }

        if ($form['dtend_check']) {
            $dtend  = Q::quote($form['dtend']);
            $form_selector['dtend'] = $dtend;
        }

        if ($form['name']) {
            $name = Q::quote($form['name']);
            $selector .= "[name*=$name|name_abbr*=$name]";
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        switch($sort_by) {
            case 'name':
                $selector .= ":sort(name {$sort_flag})";
                break;
            default:
                $selector .= ":sort(status A)";
        }
        $pre_selector = [];

        $equipment_root_tag = Tag_Model::root('equipment');
        $group_root_tag = Tag_Model::root('group');

        if ($form['group_tag'] && $form['group_tag'] != $group_root_tag->id) {
            $group_tag = O('tag_group', $form['group_tag']);
            $pre_selector[] = $group_tag;
        }

        if ($form['equipment_tag'] && $form['equipment_tag'] != $equipment_root_tag->id) {
            $equipment_tag = O('tag_group', $form['equipment_tag']);
            $pre_selector[] = $equipment_tag;
        }

        if (count($pre_selector)) {
            $selector = '('. implode(', ', $pre_selector). ') '. $selector;
        }

        $form['selector'] = $selector;

        $_SESSION[$form_token] = $form;

        $equipments = Q($selector);

        $pagination = Lab::pagination($equipments, (int)$form['st'], 15);

        $content = V('eq_stat:list', [
			        	'form'=>$form,
			        	'equipments'=>$equipments,
			        	'pagination' => $pagination,
			        	'form_selector' => $form_selector,
			        	'sort_by' => $sort_by,
			        	'sort_asc' => $sort_asc,
                        'form_token' => $form_token
			        ]);

        $this->add_css('eq_stat:common');

        $this->layout->body->primary_tabs
			        	->select('list')
			        	->set('content', $content);
    }
}

class List_AJAX_Controller extends AJAX_Controller {

    function index_export_click() {
        $form = Input::form();

        $form_token = $form['form_token'];
        $type = $form['type'];
        $columns = EQ_Stat::get_export_columns();
        switch ($type) {
            case 'csv':
                $title = I18N::T('eq_stat', '请选择要导出Excel的列');
                break;
            case 'print':
                $title = I18N::T('eq_stat', '请选择要打印的列');
                break;
        }
        JS::dialog(V('export_form', [
                        'type' => $type,
                        'form_token' => $form_token,
                        'columns' => $columns
                    ]),[
                        'title' => $title
                    ]);
    }

}

