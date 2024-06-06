<?php

class TODO_Controller extends Base_Controller{

	//任务列表显示
	function index () {
		//多栏关键字搜索
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
				if (!$form['dttime_check']) {
					unset($old_form['dttime_check']);
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
	
		
		
		// 多栏搜索
		$me = L('ME');						                    //存入当前form对象到session对象中		
		$selector = "{$me}<@(worker|supervisor) task";
		if ($form['name']) {					                            //接受到form表单中传来的值进行设定条件比对

			$name = Q::quote($form['name']);
			$selector = $selector."[name*=$name]";
			
		}

		if ($form['supervisor']) {
			$supervisor = Q::quote($form['supervisor']);
			$selector = "({$me}<@(worker|supervisor),user[name*=$supervisor]<supervisor) task";
			
		}

		if ($form['worker']) {
			$worker = Q::quote($form['worker']);
			$selector = "({$me}<@(worker|supervisor),user[name*=$worker]<worker) task";
		}
		if ($form['dttime_check']) {
			$dtstart = $form['dttime'];
			$selector .= "[dtstart>=$dtstart]";
		}
		if ($form['dtend_check']) {
			$dtend = $form['dtend'];
			$selector .= "[dtstart<=$dtend]";
		}
		//排序
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		if ($sort_by) {
			$selector .= ":sort({$sort_by} {$sort_flag})";
		}
		
		//分页设置
		
		$start = (int) $form['st'];
		$per_page = 15;
		
		$tasks = Q($selector);
		$pagination = Lab::pagination($tasks, $start, $per_page);

		$this->layout->body->primary_tabs->select('todo');
		$this->layout->body->primary_tabs->content =
			V('todo/index', [
				'tasks'=>$tasks,
				'pagination'=>$pagination,
				'form'=>$form,
				'sort_asc'=>$sort_asc,
				'sort_by'=>$sort_by,
				
			]);
	}
}
