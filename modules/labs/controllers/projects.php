<?php

class Projects_Controller extends Controller {
	
	function index(){
		$user = L('ME')->id ? L('ME') : O('user', ['token'=>Input::form('user')]);
		$status = Lab_Project_Model::STATUS_ACTIVED;
		$projects = Q("$user lab lab_project[status={$status}]");
		echo V('labs:projects.xml', ['projects'=>$projects]);
	}
	
}

class Projects_AJAX_Controller extends AJAX_Controller {
	function index_select_lab_change () {
		$form = Form::filter(Input::form());
		$lab = O('lab', $form['project_lab']);
		$container = $form['container'];
		
		$object_id = $form['object_id'];
		$object_name = $form['object_name'];
		if (!in_array($object_name, ['publication', 'award', 'patent'])) return;
		$object = O($object_name, $object_id);
		$view = Event::trigger('achievement.project.select', $object, $lab, NULL, $form);
    	
		Output::$AJAX['#'.$container] = [
			'data'=>(string)V('labs:lab/achievements_lab_project', [
						'lab'=>$lab, 
						'view'=>$view, 
						'object'=>$object,
					]),
			'mode'=>'replace'
		];			
	}
	
	function index_select_project_change () {		
		$form = Form::filter(Input::form());
		$project= O('lab_project',$form['lab_project']);
		$container = $form['container'];

		$object_id = $form['object_id'];
		$object_name = $form['object_name'];
		if (!in_array($object_name, ['publication', 'award', 'patent'])) return;
		$object = O($object_name, $object_id);

		$me = L('ME');
		
		$view = Event::trigger('achievement.project.select', $object, NULL, $project, $form);

		Output::$AJAX['#'.$container] = [
			'data'=>(string)$view,
			'mode'=>'replace'
		];	
	}

}

