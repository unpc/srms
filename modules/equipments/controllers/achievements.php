<?php

class Achievements_AJAX_Controller extends AJAX_Controller {
	function index_select_lab_change () {
		$form = Form::filter(Input::form());
		if ($GLOBALS['preload']['people.multi_lab']) {
			$ids = join(',', array_keys(json_decode($form['labs'],true)));
		} else {
			$ids = $form['labs'];
		}
		$labs = Q("lab[id={$ids}]");
		$container = $form['container'];

		$object_id = $form['object_id'];
		$object_name = $form['object_name'];
		if (!in_array($object_name, ['publication', 'award', 'patent'])) return;
		$object = O($object_name, $object_id);

		Output::$AJAX['#'.$container] = [
			'data'=>(string)V('equipments:equipment/achievements_equipment', [
				'labs' => $labs,
				'form' => $form,
                'object' => $object,
            ]),
			'mode'=>'replace'
		];
	}
}