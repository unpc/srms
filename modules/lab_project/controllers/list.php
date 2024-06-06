<?php

class List_Controller extends Base_Controller {

    function index($tab = 'list') {
        Event::bind('lab_projects.list.content', [$this, '_index_list_content'], 0, 'list');
        $this->layout->body->primary_tabs
            ->content_event('lab_projects.list.content')
            ->select($tab);
    }

    function _index_list_content($e, $tabs) {
        if ($GLOBALS['preload']['people.multi_lab']) return;
        $me = L('ME');
        $lab = Q("{$me} lab")->current();
		$form = Lab::form(function(&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                } else {
                    $form['dtstart'] = strtotime('midnight', $form['dtstart']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtend'] = strtotime('midnight', $form['dtend'] + 86400) - 1;
                }
                unset($old_form['date_filter']);
            }
        });
        $status = Lab_Project_Model::STATUS_ACTIVED;
        $selector  = "lab_project[status={$status}]";
        if ($me->access('管理所有内容')) {
        } else {
            $selector = "$me lab " . $selector;
        }
        if($form['name']){
			$name = Q::quote(trim($form['name']));
			$selector .= "[name*=$name]";
		}
        if($form['lab']){
			$lab = Q::quote(trim($form['lab']));
			$selector .= "[lab_id=$lab]";
		}
		if (isset($form['type']) && $form['type'] != -1) {
			$type = Q::quote($form['type']);
			$selector .= "[type=$type]";
		}
        if($form['dtstart_check']){	
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[dtend>=$dtstart]";
		}
		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
            $dtend = Date::get_month_end($dtend);
			$selector .= "[dtend<=$dtend]";
		}
		if(!$form['dtstart_check'] && !$form['dtend_check']) {
            $form['dtend'] = Date::get_month_end(Date::time());
			$form['dtstart'] = Date::prev_time($form['dtend'], 1, 'm') + 1;
		}
        $lab_projects = Q($selector);
		$pagination = Lab::pagination($lab_projects, (int)$form['st'], 20);
        $tabs->content = V('list', [
            'form' => $form,
            'lab_projects' => $lab_projects,
            'pagination' => $pagination,
        ]);
    }
}
