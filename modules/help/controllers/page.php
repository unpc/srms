<?php

class Page_Controller extends Base_Controller {
	
	function index() {
	
		$args = func_get_args();
		$wiki_path = implode(':', $args);
		if(!$wiki_path)$wiki_path='index';
	
		$wiki = new Wiki(Config::get('help.wiki'), $wiki_path);
				
		$this->layout->body->primary_tabs
			->add_tab('page', [
					'url'=>$wiki->url(),
					'title'=>I18N::T('help', '查看页面'),
				])
			->select('page');

		$this->add_css('wiki:wiki');

		$this->layout->body->wiki = $wiki;

		if ($wiki->exists($wiki_path)) {
			$this->layout->body->content = V('page/view');
		} else {
			$this->layout->body->content = V('page/not_found');
		}
		
	}

	function edit($path = NULL){
		
		if (!L('ME')->access('修改帮助中心内容')) {
			URI::redirect('error/401');
		}

		$args = func_get_args();
		if(!$args) $args = ['index'];

		$wiki_path = implode(':', $args);
		$wiki = new Wiki(Config::get('help.wiki'), $wiki_path);
		
		$this->add_css('wiki:wiki');		

		$this->layout->body->primary_tabs
			->add_tab('page', [
				'url' => $wiki->url($wiki_path),
				'title' => I18N::T('help', '查看页面'),
			])
			->add_tab('page_edit', [
				'url' => $wiki->url($wiki_path, 'edit'),
				'title' => I18N::T('help', '修改页面'),
			])
			->select('page_edit');
		
		
		$this->layout->body->content = V('page/edit');
		$this->layout->body->content->wiki = $wiki;

	}

	function delete() {
		if (!L('ME')->access('修改帮助中心内容')) {
			URI::redirect('error/401');
		}
		
		$args = func_get_args();
		if(!$args) $args = ['index'];
		$wiki_path = implode(':', $args);
		
		$wiki = new Wiki(Config::get('help.wiki'), $wiki_path);
		$wiki->delete();
		
		$log = sprintf('[help] %s[%d] 删除了帮助中心页面', 
									L('ME')->name, L('ME')->id);

		Log::add($log, 'journal');
		URI::redirect($wiki->url());
	}
	
}

Core::include_path('wiki', MODULE_PATH.'wiki/');
class Page_AJAX_Controller extends Wiki_Media_AJAX_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$wiki_opt = Config::get('help.wiki');
		$this->root = Wiki::media_base_dir($wiki_opt['base']);
	}

	function index_edit_form_submit(){

		if(L('ME')->access('修改帮助中心内容')) {
			$wiki = new Wiki(Config::get('help.wiki'), Input::form('path'));
			switch(Input::form('submit')) {
			case 'preview':
				$wiki->content = Input::form('content');
				$container_id = Input::form('container_id');
				Output::$AJAX["#{$container_id}"] = [
					'data'=> (string)V('page/preview', ['wiki'=>$wiki])
				];
				//JS::dialog(V('page/preview', array('wiki'=>$wiki)));
				break;
			case 'submit':
				$wiki->content = Input::form('content');
				$wiki->save();

				/*添加记录*/
				$log = sprintf('[help] %s[%d] 修改了帮助中心页面',
											L('ME')->name, L('ME')->id);
				Log::add($log, 'journal');

				JS::redirect($wiki->url());
				break;
			case 'attach':
				parent::dialog(['textarea_id' => Input::form('textarea_id')]);
				break;
			}

		}
		
	}
	
	function index_attachment_open_click() {
	
		# 转换路径格式
		$path = strtr(Input::form('path'), '/', ':');
		$textarea_id = $_SESSION['current_wiki_textarea'];
		Output::$AJAX['dialog'] = '#close';	
		Output::$AJAX['#'.$textarea_id] = ['data'=>'{{'.$path.'}}', 'mode'=>'textarea_insert'];
	}

}
