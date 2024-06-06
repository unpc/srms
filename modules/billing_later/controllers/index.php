<?php

class Index_AJAX_Controller extends AJAX_Controller {

	function index_grants_change() {
		if ($GLOBALS['preload']['people.multi_lab']) return;

		$form = Input::form();

	    if ( $form['user_id'] ) {

	    	$user = O('user', $form['user_id']);

		    $user_lab = Q("$user lab")->current();

		    if (!$user_lab) $user_lab = O('lab', Lab::get('equipment.temp_lab_id'));

		    $component = O('cal_component', $form['component_id']);
		
	    	$tr_project_id = $form['tr_grant_id'] ? : ('tr_grant_' . uniqid());

	    	Output::$AJAX["#" . $form['tr_grant_id']] = [
	    			'data' => (string)V('billing_later:view/calendar/calendar_form/grant', [
	    									'lab' => $user_lab,
	    									'user' => $user,
	    									'tr_grant_id' => $tr_project_id,
	    									'component' => $component
                  	]),
                  	'mode' => 'replace',
        	];
	    }
	}

	function index_add_eq_sample_grants_change() {
		if ($GLOBALS['preload']['people.multi_lab']) return;

		$form = Input::form();

		if ( $form['user_id'] ) {

			$user = O('user', $form['user_id']);

		    $user_lab = Q("$user lab")->current();

		    if (!$user_lab) $user_lab = O('lab', Lab::get('equipment.temp_lab_id'));
		
	    	$tr_project_id = $form['tr_grant_id'] ? : ('tr_grant_' . uniqid());

	    	Output::$AJAX["#" . $form['tr_grant_id']] = [
	    			'data' => (string)V('billing_later:view/eq_sample/add_grant', [
	    									'lab' => $user_lab,
	    									'tr_grant_id' => $tr_project_id,
	    									'user' => $user,
                  	]),
                  	'mode' => 'replace',
        	];
	    }
	}

	function index_edit_eq_sample_grants_change() {
		if ($GLOBALS['preload']['people.multi_lab']) return;

		$form = Input::form();

		if ( $form['user_id'] ) {

			$user = O('user', $form['user_id']);
		    $user_lab = Q("$user lab")->current();

		    $sample = O('eq_sample', $form['sample_id']);

		    if (!$user_lab->id) $user_lab = O('lab', Lab::get('equipment.temp_lab_id'));
		
	    	$tr_project_id = $form['tr_grant_id'] ? : ('tr_grant_' . uniqid());

	    	Output::$AJAX["#" . $form['tr_grant_id']] = [
	    			'data' => (string)V('billing_later:view/eq_sample/edit_grant', [
	    									'sample' => $sample,
	    									'lab' => $user_lab,
	    									'tr_grant_id' => $tr_project_id,
                  	]),
                  	'mode' => 'replace',
        	];
	    }
	}
}