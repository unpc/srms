<?php 

class Edit_Controller extends Base_Controller {

	function index($id) {	
		$me = L('ME');
		if (!$me->is_allowed_to('创建', 'happyhour')) URI::redirect('error/401');
		
		
		$happyhour = O('happyhour', $id);
		$replys = Q("happy_reply[happyhour={$happyhour->id}]");

		
		if (!$happyhour->id) {
			URI::redirect('!happy');
		}
        
        $form = Form::filter(Input::form());
        
        if ($form['submit']){
        	$form->validate('stock', 'is_numeric', I18N::T('happy', '选购数量必须是以数字表示!'));
		 	
		 	if ($form['stock'] <= 0) {
		 		$form->set_error('stock', I18N::T('happy', '选购数量必须大于零'));
		 	}
		 	
		 	if($form['stocks']) {
			 	foreach($form['stocks'] as $key => $val){		 		
			 		if($val <= 0) {
			 			$form->set_error("stocks[$key]", I18N::T('happy', '选购数量必须是以正数表示'));
			 		}
			 	}
		 	}
		 	
		 	$form->validate('title', 'not_empty', I18N::T('happy', '主题不能为空!'));
		 	
			if ($form['dtime'] < $form['ctime']) {
				$form->set_error('dtime', I18N::T('happy', '截止时间不能小于发起时间'));
			}
			
			$form['content'] = trim($form['content']);
		    foreach($replys as $reply) {
				if (H($reply->content) == $form['content']) {
					$form->set_error('content', I18N::T('happy', "选购单中已经有".$form['content']));
				}
			}
		    
		    if($form->no_error) {
				$happyhour->title = $form['title'];
				$happyhour->body = $form['body'];
				$happyhour->ctime = $form['ctime'];
				$happyhour->dtime = $form['dtime'];
				
				if($form['contents']) {
					foreach($form['contents'] as $key => $val){			
						$reply = O("happy_reply", $key);
						//删除置空的物品
						if ($val == '') {	
							$reply->delete();
						}
						else {			
							$reply->content = $val;
							$reply->stock = $form['stocks'][$key];			
							$reply->save();
						}
					}
				}
				
				if($form['content']) {
					$reply = O('happy_reply');
					$reply->replyer = L('ME');
					$reply->content = $form['content'];
					$reply->stock = $form['stock'];
					$reply->happyhour_id = $id;
					$reply->save();
				}
				
				if ($happyhour->save()) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('happy','修改成功!'));	
				URI::redirect($happyhour->url(NULL, NULL, NULL, 'edit'));
				}
				else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('happy','修改失败！'));
				}
			}
		}
        
        $breadcrumb = [
			[
				'url'=>$happyhour->url(NULL, NULL, NULL, 'activites'),
				'title'=>H($happyhour->title),
			],
			[
				'url'=>URI::url(''),
				'title'=>I18N::HT('happy', '活动修改'),
			]
		];
        
        $this->layout->body->primary_tabs
            ->add_tab('edit', ['*'=>$breadcrumb])
            ->select('edit')
            ->content = V('happy:edit', [
                'happyhour' => $happyhour,
                'replys' => $replys,
                'form' => $form,
            ]);
	}


	function delete($id = 0) {
		$me = L('ME');
		if (!$me->is_allowed_to('创建', 'happyhour')) URI::redirect('error/401');
       
       	$happyhour = O('happyhour', $id);
		$replys = Q("happy_reply[happyhour={$happyhour->id}]");
        
        if (!$happyhour->id) {
            URI::redirect('error/404');
        }

        foreach($replys as $reply) {
        	$reply -> delete();
        }
        
        if ($happyhour->delete()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('happy', '活动删除成功!'));
            URI::redirect('!happy');
        }
        else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('happy', '活动删除失败！')); 
            URI::redirect($happyhour->url(NULL, NULL, NULL, 'edit'));
        }
    }

}
