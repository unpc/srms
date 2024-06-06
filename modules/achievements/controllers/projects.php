<?php

class Projects_Ajax_Controller extends AJAX_Controller {
    
    function index_select_lab_change() {
        $form = Input::form();

        $object_id = $form['object_id'];
        $object_name = $form['object_name'];
        if (!in_array($object_name, ['publication', 'award', 'patent'])) return;
        $object = O($object_name, $object_id);
        $select_projs = $object->id ? Q("{$object} lab_project")->to_assoc('id', 'id') : [];

        $labs =  join(',' ,array_keys(json_decode($form['labs'], true)));
        $projects = Q("lab[id={$labs}] lab_project");

        $container = $form['container'];
        Output::$AJAX['#'.$container] = [
            'data'=>(string)V('labs:lab/achievements_lab_project_item', [
                'projects' => $projects,
                'select_projs' => $select_projs,
                'container' => $container
            ]),
            'mode'=>'replace'
        ];
    }
}
