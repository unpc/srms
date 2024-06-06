<?php 

class Approval_AJAX_Controller extends AJAX_Controller {

    function index_batch_people_submit() {
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

        if (JS::confirm(I18N::T('people', $message))) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $user = O('user',$key);
                    if(!$user->id || !$me->is_allowed_to('修改', $user)) continue;
                    $user->approval = 1;
                    if ($submit == 'pass') {
                        $user->atime = time();
                    }
                    $user->save();
                }
            }

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '批量操作完成'));
            JS::refresh();
        }
    }

	function index_people_click() {
		$form = Input::form();
		$user_id = $form['user_id'];
        $submit = $form['approval'];
		$me = L('ME');
        $user = O('user',$user_id);
        if(!$user->id || !$me->is_allowed_to('修改', $user)) return;

		switch ($submit) {
            case "pass":
                $message = '您确定要通过吗?';
                $user->approval = 1;
                $user->atime = time();
                break;
            case "reject":
            default:
                $message = '您确定要驳回吗?';
                $user->approval = 1;
        }

        if (JS::confirm(I18N::T('people',$message))) {
            if($user->save()){
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '审核完成'));
                JS::refresh();
            }
        }
	}

}
