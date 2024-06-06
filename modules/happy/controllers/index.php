<?php

class Index_Controller extends Base_Controller {

	function index() {


		$form = Lab::form(); 
		//$start = $form['start'] ? $form['start'] : 0;
		$sort_by = $form['sort'] ? $form['sort'] : 'ctime';
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A' : 'D';
		$selector = 'happyhour';
		switch ($sort_by) {
		case 'creater':
			$selector .= ":sort(creater_id $sort_flag)";
			break;
		case 'title':
			$selector .= ":sort(title $sort_flag)";
			break;
		case 'ctime':
			$selector .= ":sort(ctime $sort_flag)";
			break;
		case 'dtime':
			$selector .= ":sort(dtime $sort_flag)";
			break;
		default:
			$selector .= ":sort(ctime D)";
			break;
		}
		
		
		if ($form['title'] != null) {
			$title = Q::quote($form['title']);
			$selector .= "happyhour[title*={$title}]";
		}
		if ($form['creater']) {	 
			$pre_selector = [];
			$creater = Q::quote($form['creater']);
			$pre_selector['creater_id'] = "user[name*=$creater|name_abbr*=$creater]<creater";
			$selector = '('.implode(', ', $pre_selector).') ' . $selector;
		}
		$happyhours = Q("$selector");
		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);
		$pagination = Lab::pagination($happyhours, $start, $per_page);		
		$this->add_css('preview');
		$this->add_js('preview');
		
		
		$this->layout->body->primary_tabs
			 ->select($tabs)
			 ->content = V('happy:index', [
							'form' => $form,
							'pagination' => $pagination,
							'st' => $start,
							'happyhours' => $happyhours,
							'next_start' => $next_start,
							'model_name' => $tabs,
							'sort_asc'=> $sort_asc,
							'sort_by'=> $sort_by,
						]);
	}
}	
class Index_AJAX_Controller extends AJAX_Controller {

	function index_preview_click() {
		 $form = Input::form();
		 $happyhour = O('happyhour',$form['id']);
		 $replys = Q("happy_reply[happyhour={$happyhour}]");
		 if (!$happyhour->id) return;		 
		 Output::$AJAX['preview'] = (string)V('happy:happyhour/preview', ['happyhour' => $happyhour,'replys' => $replys]);
	}
}
