<?php

class Index_Controller extends Base_Controller {
	
	function real_index() {
	
		$this->layout->body->primary_tabs->select('index');

		$this->add_css('wiki:wiki');		

		$wiki = new Wiki(Config::get('help.wiki'));

		$this->layout->body->content = V('index/default');
		$this->layout->body->content->wiki = $wiki;
	}

	function index() {
	
		$args = func_get_args();
		$wiki_path = implode(':', $args);
		if(!$wiki_path)$wiki_path='index';
	
		$wiki = new Wiki(Config::get('help.wiki'), $wiki_path);
				
		$this->layout->body->primary_tabs->select('index');

		$this->add_css('wiki:wiki');

		$this->layout->body->wiki = $wiki;

		if ($wiki->exists($wiki_path)) {
			$this->layout->body->content = V('page/view');
		} else {
			$this->layout->body->content = V('page/not_found');
		}
		
	}

}

class Index_AJAX_Controller extends AJAX_Controller {


}
