<?php 

class Approval_AJAX_Controller extends AJAX_Controller {

    function index_batch_lab_submit() {
        $form = Input::form();
        $me = L('ME');
        $submit = $form['submit'];

        switch ($submit) {
            case "pass":
                $message = '您确定要批量通过吗?';
                break;
            case "reject":
            default:
                $message = '您确定要批量驳回吗?';
        }

        if (JS::confirm(I18N::T('labs', $message))) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $lab = O('lab',$key);
                    if(!$lab->id || !$me->is_allowed_to('修改', $lab)) return;

                    $lab->approval = 1;
                    if ($submit == 'pass') {
                        $lab->atime = time();
                    }
                    $lab->save();
                }
            }

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '批量操作完成'));
            JS::refresh();
        }
    }

	function index_lab_click() {
		$form = Input::form();
		$lab_id = $form['lab_id'];
        $submit = $form['approval'];
		$me = L('ME');
        $lab = O('lab',$lab_id);

        if(!$lab->id || !$me->is_allowed_to('修改', $lab)) return;

		switch ($submit) {
            case "pass":
                $message = '您确定要通过吗?';
                $lab->approval = 1;
                $lab->atime = time();
                break;
            case "reject":
            default:
                $message = '您确定要驳回吗?';
                $lab->approval = 1;
        }

        if (JS::confirm(I18N::T('labs',$message))) {
            if($lab->save()){
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '审核完成'));
                JS::refresh();
            }
        }
	}

}
