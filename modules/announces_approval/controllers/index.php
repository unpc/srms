<?php 

class Index_AJAX_Controller extends AJAX_Controller {

	function index_approval_announce_click($a_id=0) {
		$form = Input::form();
		$id = $form['a_id'];
        $approval = $form['approval'];
		$me = L('ME');
        $announce = O('announce',$id);
        if(!$me->is_allowed_to('审批', $announce)) return;
		switch ($approval) {
            case "pass":
                {
                    $message = '您确定要通过该公告吗?';
                    $announce->flag = 1;
                }
                break;
            case "rebut":
            default:
                {
                    $message = '您确定要驳回该公告吗?';
                    $announce->flag = 2;
                }
        }
		if (JS::confirm(I18N::T('announces',$message))) {
			if(!$announce->id) return;
			if($announce->save()){
			    if ($announce->flag == Announce_Approval_Model::STATUS_PASS) {
			        //发送给每个用户
                    switch ($announce->type) {
                        case 'all':
                            Announce::extract_users($announce, 'all');
                            break;
                        case 'user':
                            $receiver = json_decode($announce->receiver, true);
                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce, 'user', $id);
                            }
                            break;
                        case 'role':
                            $receiver = json_decode($announce->receiver, true);

                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce, 'role', $id);
                            }
                            break;
                        case 'group':
                            $receiver = json_decode($announce->receiver,true);
                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce,'group',$id);
                            }
                            break;
                    }
                }
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '公告审批成功!'));
            	JS::redirect('!announces/extra/approval.approval');
			}
		}
	}

}
