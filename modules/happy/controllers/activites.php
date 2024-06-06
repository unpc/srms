<?php

class Activites_Controller extends Base_Controller {

    function index($id=0){
        if (!$id) {
			URI::redirect('!happy');
		}
		
		$role_pass = true;
		$happy_once = true; 
		$me = L('ME');
		if (!$me->is_allowed_to('创建', 'happyhour')) $role_pass = false;
		
		$happyhour = O('happyhour', $id);
		$replys = Q("happy_reply[happyhour={$happyhour->id}]");
	
 
		$form = Form::filter(Input::form());
		
		if ($form['submit']) {
		 	$form->validate('content', 'not_empty', I18N::T('happy', '内容不能为空!'));
		 		
		 	if ($form['stock'] <= 0) {
		 		$form->set_error('stock', I18N::T('happy', '选购数量必须大于零'));
		 	}
		 	
		 	$form['content'] = trim($form['content']);
		    foreach($replys as $reply) {
				if (H($reply->content) == $form['content']) {
					$form->set_error('content', I18N::T('happy', "选购单中已经有".$form['content']));
				}
			}	
		 	
			if ($form->no_error) {
				foreach($replys as $reply) {
					if(!$role_pass && $reply->replyer_id == $me->id) {
						$happy_once = false;
					}		
				}
				if (!$happy_once) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('happy','每个活动每人每天只能选购一次！'));
				}
				else {
					$reply = O('happy_reply');
					$reply->replyer = L('ME');
					$reply->content = $form['content'];
					$reply->stock = $form['stock'];
					$reply->happyhour_id = $id;
					if ($reply->save()) {
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('happy','添加成功!'));
						URI::redirect($happyhour->url(NULL, NULL, NULL, 'activites'));
					}
					else {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('happy','添加失败！'));
					}	
				}
			}    
        }
        
        
        $type = strtolower(Input::form('type'));
		$export_types = ['print', 'csv'];
        
        if (in_array($type, $export_types)) {
			call_user_func([$this, '_export_'.$type], $replys, $happyhour);
		}
		else {
        $panel_buttons = new ArrayIterator;
			if ($me->is_allowed_to('创建', 'happyhour')) {
				$panel_buttons[] = [
					'url' => URI::url('').'?type=csv',
					'text'  => I18N::T('happy', '导出CSV'),
					'extra' => 'class="button button_save "',
					];
			
				$panel_buttons[] = [
					'url' => URI::url('').'?type=print',
					'text'  => I18N::T('happy', '打印'),
					'extra' => 'class="button button_print " target="_blank"',
					];
			
			}
        
		    $this->add_css('happy:happy');
		    $this->layout->body->primary_tabs
		        ->add_tab('activites', [
		            'url' => URI::url(''),
		            'title' => I18N::T('activites', '活动详情'),
		        ])
		        ->select('activites')
		        ->content = V('happy:activites', [
		            'happyhour' => $happyhour,
		            'replys' => $replys,
		            'form' => $form,
		            'panel_buttons' => $panel_buttons,
		        ]);
		}
	}
	
	function _export_csv($replys, $happyhour) {
			$csv = new CSV('php://output', 'w');
			/* 记录日志 */
			$me = L('ME');
			$log = sprintf('[happy] %s[%d]以CSV导出了成员列表',
						   $me->name, $me->id);
			Log::add($log, 'journal');
		
			$csv->write([
							I18N::T('happy', '序号'),
							I18N::T('happy', '选购物品'),
							I18N::T('happy', '数量'),
							]);

					$num=1;
			if ($replys->total_count() > 0) {
				foreach ($replys as $reply) {
					$csv->write( [
									 $num++,
									 $reply->content,
									 $reply->stock,
									 ]);
				}
			}
			$csv->close();
	}
	
	function _export_print($replys, $happyhour) {
		$properties_to_print = [
			'number' => '序号',
			'content' => '选购物品',
			'stock' => '数量',
			];

		$this->layout = V('activite_print', [
							  'replys' => $replys,
							  'happyhour'=>$happyhour,
							  'properties_to_print' => $properties_to_print,
							  ]);
		/* 记录日志 */
		$me = L('ME');
		$log = sprintf('[happy] %s[%d]打印了选购单',
					   $me->name, $me->id);
		Log::add($log, 'journal');
		
	}
	
		
}
