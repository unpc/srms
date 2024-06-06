<?php

class Timeline_Controller extends Base_Controller {
	
}

class Timeline_AJAX_Controller extends AJAX_Controller {
	
	function index_week_click() {
		
		$form = Form::filter(Input::form());
		$dtstart = $form['st'];
		$dtend = $form['ed'];
		$uniqid = $form['uniqid'];
		$parent_task_id = $form['parent_task_id'];
		
		$parent_task = O('task', $parent_task_id);
		$tasks = Q("task[parent=$parent_task][dtend>=$dtstart][dtstart<=$dtend]");
		
		$dtprev = $dtstart - 604800;
		$dtnext = $dtstart + 604800;
		
		$view = V('timeline/week', ['dtstart'=>$dtstart, 
										 'dtprev'=>$dtprev, 
										 'dtnext'=>$dtnext, 
										 'tasks'=>$tasks, 
										 'parent_task'=>$parent_task,
										 'uniqid'=>$uniqid,
										 'mode'=>'timeline'
									]);
									
		Output::$AJAX['#'.$uniqid] = [
				'data'=>(string) $view,
				'mode'=>'html'
			];
		
	}
}


