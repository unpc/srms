<?php

class add_Controller extends Base_Controller{

	function index() {
	    $me = L('ME');
		if (!$me->is_allowed_to('创建', 'happyhour')) URI::redirect('error/401');	
		$form = Form::filter(Input::form());

		if ($form['submit']){
		 	$form->validate('title', 'not_empty', I18N::T('happy', '主题不能为空!'));
			if ($form['dtime'] < $form['ctime']) {
				$form->set_error('dtime', I18N::T('happy', '截止时间不能小于发起时间'));
			}

			if($form->no_error) {
				

				$happyhour = O('happyhour');	
				$happyhour->creater = L('ME');
				$happyhour->title = $form['title'];
				$happyhour->body = $form['body'];
				$happyhour->ctime = $form['ctime'];
				$happyhour->dtime = $form['dtime'];
				if ($happyhour->save()) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','添加成功!'));	
				URI::redirect($happyhour->url(NULL, NULL, NULL, 'activites'));
				}
				else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('food','添加失败！'));
				}
			}
		}

		$this->layout->body->primary_tabs
					->add_tab('add', [
						'url' => URI::url('!happy/add'),
						'title' => I18N::T('add', '创建活动'),
					])
					->select('add')
					->content = V('happy:add', ['form'=>$form]);
	}
}
