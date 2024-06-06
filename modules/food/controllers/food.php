<?php

class Food_Controller extends Base_Controller {

	function index() {
		//列出所有的菜式
		
		$form = Lab::form();
		$selector = 'food';
		
		//查询判定
		if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*={$name}]";
		}
		
		if ($form['price']) {
			$price = Q::quote($form['price']);
			$selector .= "[price={$price}]";
		}

		//菜式按照供应商和预订日期进行排列	
		$selector .= ':sort(supplier D, reserve A)';

		$foods = Q($selector);

		$pagination = Lab::pagination($foods, $form['st'], 15);
		
		$this->layout->body->primary_tabs
				->select('fd_list')
				->set('content', V('food/index', [
					'foods'=>$foods, 
					'form'=>$form,
					'pagination' => $pagination,
				]));

		$this->add_css('food:common');
			
	}
			
	function add() {
		//增加菜式
		
		
		if (!L('ME')->is_allowed_to('添加', 'food')) {
			URI::redirect('error/401');
		}	
									
		if (Input::form('submit')) {
				
			$form = Form::filter(Input::form())
					->validate('supplier', 'not_empty', I18N::T('food', '菜式供应商名称不能为空'))
					->validate('name', 'not_empty', I18N::T('food', '菜式名称不能为空'))
					->validate('price', 'not_empty', I18N::T('food', '菜式价格不能为空'));
					
			if ($form->no_error) {
				$food = O('food');
				$food->supplier	= $form['supplier'];
				$food->name = $form['name'];
				$food->price = $form['price'];
				$food->reserve = json_encode($form['reserve']);
				$food->description = $form['description'];
				
				if ($food->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','添加成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('food','添加失败！'));
				}
				
				URI::redirect(URI::url('!food/food'));
			}	
		}
		
		$this->layout->body->primary_tabs
				->add_tab('add', [
							'url' => URI::url('!food/food/add'),
							'title' => I18N::T('food', '新增菜式'),
				])
				->select('add')
				->set('content', V('food/add', ['form'=>$form]));
	}	
		
	function edit($food_id = 0, $tab = 'info') {
	
		$food = O('food', $food_id);
		
		if (!$food->id) {
			URI::redirect('error/404');
		}
		if (!L('ME')->is_allowed_to('修改', $food)) {
			URI::redirect('error/401');
		}
		
		Event::bind('food.edit.content', [$this, '_edit_info'], 0 ,'info');
		Event::bind('food.edit.content', [$this, '_edit_photo'], 0 , 'photo');
		
		$this->layout->body->primary_tabs
				->add_tab('edit', [
							'url' => $food->url(NULL, NULL, NULL, 'edit'),
							'title' => I18N::T('food', '修改菜谱'),
				])
				->select('edit');
		
		$content = V('food/edit', ['form'=>$form, 'food'=>$food]);
		$content->secondary_tabs 
				= Widget::factory('tabs');
				
		$content->secondary_tabs 
				->add_tab('info', [
					'url'=> $food->url('info', NULL, NULL, 'edit'),
					'title'=>I18N::T('food', '基本信息'),
				])
				->add_tab('photo', [
					'url'=> $food->url('photo', NULL, NULL, 'edit'),
					'title'=>I18N::T('food', '菜式图片'),
				]);
				
		$content->secondary_tabs->set('class', 'secondary_tabs')
				->set('food', $food)
				->tab_event('food.edit.tab')
				->content_event('food.edit.content')
				->select($tab);
		
		$this->layout->body->primary_tabs->content = $content;		
	}
	
	function _edit_info($e, $tabs) {
	
		$food = $tabs->food;
		
		if (Input::form('submit')) {
		
			$form = Form::filter(Input::form())
					->validate('supplier', 'not_empty', I18N::T('food', '菜式供应商名称不能为空'))
					->validate('name', 'not_empty', I18N::T('food', '菜谱名称不能为空'))
					->validate('price', 'not_empty', I18N::T('food', '菜谱价格不能为空'));
					
			if ($form->no_error) {
				$food->id = $food_id;
				$food->supplier	= $form['supplier'];
				$food->name = $form['name'];
				$food->price = $form['price'];
				$food->reserve = json_encode($form['reserve']);
				$food->description = $form['description'];
				$food->mtime = time();
				
				if ($food->save()) {
					Lab::message(Lab::MESSAGE_NORMAL,I18N::T('food','修改成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('food','新增出现问题'));
				}
				
				URI::redirect(URI::url('!food/food'));
			}	
		}
		
		$tabs->content = V('food/edit.info', ['form'=>$form, 'food'=>$food]);
	}
	
	function _edit_photo($e, $tabs) {
	
		$food = $tabs->food;
		
		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try {
					$ext = File::extension($file['name']);
					$food->save_icon(Image::load($file['tmp_name'], $ext));
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food', '菜式图片更新成功'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '菜式图片更新失败!'));
				}
			}
			else{
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '请选择您要上传的菜式图片。'));
			}
		}
		
		$tabs->content = V('food/edit.photo');
	}
	
	function delete($food_id) {
		//删除菜式
				
		$food = O('food',$food_id);
		
		if (!L('ME')->is_allowed_to('删除', $food)) {
			URI::redirect('error/401');
		}

		if ($food->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','删除成功'));
		}	
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('food','删除失败'));
		}
		
		URI::redirect(URI::url("!food/food"));
	}
	
	function delete_photo($food_id) {
		
		$food = O('food', $food_id);
		
		if (!L('ME')->is_allowed_to('修改', $food)) {
			URI::redirect('error/401');
		}
		
		if (!$food->id) {
			URI::redirect('error/404');
		}
		
		$food->delete_icon();
		
		URI::redirect($food->url('photo', NULL, NULL, 'edit'));
	}
}
