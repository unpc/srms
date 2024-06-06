<?php

class Favourite_controller extends Base_Controller {

	function index($id = 0) {
		if (!$id) {
			$happyhour = Q('happyhour:sort(ctime D):limit(1)');
			$replys = Q("happy_reply[happyhour={$happyhour->id}]");
			$id = $happyhour->id;
		}
		else {
			$happyhour = O('happyhour', $id);
			$replys = Q("happy_reply[happyhour={$happyhour->id}]");
		}	


		
 
		$form = Form::filter(Input::form());


		foreach($replys as $reply) {
			if (H($reply->content) == $form['content']) {
				$form->set_error('content', I18N::T('happy', "选购单中已经有".$form['content']));
			}
	
		}
		if ($form['submit']) {
		 	$form->validate('content', 'not_empty', I18N::T('happy', '内容不能为空!'))
		 		->validate('stock', 'is_numeric', I18N::T('happy', '库存必须是以数字表示!'));
			if ($form->no_error) {
				$stocks = Q("happy_reply[happyhour={$happyhour}][content={$form['content']}]");
				if (!$stocks->total_count()) {
					$store = O('happy_stock');
					$store->happyhour = $happyhour;
					$store->content = $form['content'];
					$store->stock = $stock->stock;
					$store->save();
				}
				$reply = O('happy_reply');
				$reply->replyer = L('ME');
				$reply->content = $form['content'];
				$reply->stock = $form['stock'];
				$reply->happyhour_id = $id;
					if ($reply->save()) {
							Lab::message(Lab::MESSAGE_NORMAL, I18N::T('happy','添加成功!'));
							URI::redirect("!happy/activites/index.".$id);
						}else{
							Lab::message(Lab::MESSAGE_ERROR, I18N::T('happy','添加失败！'));
					}	
			}
		}


			$this->layout->body->primary_tabs
						->select('favourite')
						->content = V('happy:favourite',[
										'form' => $form,
										'happyhour' => $happyhour,
										'replys' => $replys,
										'store' => $store,
										]);
	}
}

