<?php 

class Index_Controller extends Layout_Controller 
{

}

class Index_AJAX_Controller extends AJAX_Controller 
{
	function index_workflow_approval_click() 
    {		
		$me = L('ME');
        $form = Input::form();
        $o_name = H($form['source_name']);
        $o_id = (int)$form['source_id'];
        $object = O($o_name, $o_id);
        if (!$me->is_allowed_to('审核', $object)) return;

		JS::dialog(V('workflow:orm/approval', [
            'object' => $object,
        ]), [
            'title' => I18N::T('workflow', '审核数据')
        ]);
	}

    function index_workflow_approval_submit()
    {		
		$me = L('ME');
        $form = Input::form();
        $o_name = H($form['source_name']);
        $o_id = (int)$form['source_id'];
        $object = O($o_name, $o_id);
        if (!$me->is_allowed_to('审核', $object)) return;

        $workflow = O('workflow', ['source' => $object]);

        if (!$workflow->id) return;

		if ($form['action'] == 'pass') {
            $workflow->pass();
        }

        if ($form['action'] == 'reject') {
            $workflow->reject();
        }

        JS::refresh();
	}
	
}
