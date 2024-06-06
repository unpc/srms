<?php

class Index_Controller extends Base_Controller{
	
	function index($uid=0){
		
		//多栏搜索与排序
		$form = Lab::form(function(&$old_form, &$form) {
			if (isset($form['sort'])) {
				if ($old_form['sort'] == $form['sort']) {
					$form['sort_asc'] = !$old_form['sort_asc'];
				}
				else {
					$form['sort_asc'] = TRUE;
				}
			}
			
			if (isset($form['date_filter'])) {
				if (!$form['dtstart_check']) {
					unset($old_form['dtstart_check']); 
				}	
				if (!$form['dtend_check']) {
					unset($old_form['dtend_check']);
				}
				else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
				}
				unset($form['date_filter']);
			}
		});
		
		$selector_prefix = "task[!parent]";
		$selector_suffix = " project";
		$selector = '';
		
		if ($form['name']) { 
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
		}

		if (!is_null($form['approved']) && $form['approved'] != 'all') {
			$approved =  Q::quote($form['approved']);
			$selector .= "[approved=$approved]";
		}
		
		if ($form['dtstart_check']) {
			$dtstart =  Q::quote($form['dtstart']);
			$selector .= "[dtend>=$dtstart]";
		}
		
		if ($form['dtend_check']) {
			$dtend =  Q::quote($form['dtend']);
			$selector .= "[dtend>0][dtend<=$dtend]";
		}
		
		$selector = $selector_prefix . $selector;
				
		if ($form['supervisor']) {
			$supervisor = Q::quote($form['supervisor']);
			$selector = "(user[name*=$supervisor]<supervisor) ".$selector;
		}
				
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		if ($sort_by) {
			$selector .= ":sort({$sort_by} {$sort_flag})";
		}
		
		$selector.=$selector_suffix;
		$projects = Q($selector);
		
		//项目分页代码
		$pagination = Lab::pagination($projects, (int)$form['st'], 15);
		
		
		$primary_tabs = $this->layout->body->primary_tabs;		
	
		$primary_tabs->select('index');
		
		$search_filters = new ArrayIterator;
		$hint = I18N::T('projects', '请选择负责人');
		$search_filters[] = V('search_filter/users', ['form'=>$form,'hint'=>$hint]);
		
		$primary_tabs->content = V('project/index',[
										'projects'=>$projects,
										'pagination'=>$pagination,
										'search_filters' => $search_filters,
										'form'=>$form,
										'sort_by'=>$sort_by,
										'sort_asc'=>$sort_asc,
									]);
	}
}
