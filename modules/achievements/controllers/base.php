<?php

abstract class Base_Controller extends Layout_Controller{

	function _before_call($method, &$params){	
		parent::_before_call($method, $params);
	
		$this->layout->title = I18N::T('achievements', '科研成果');
		
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		
		$this->layout->body->primary_tabs
			->add_tab('publications',[
 					'url'=>URI::url('!achievements/publications/index'),	
  					'title'=>I18N::T('achievements', '论文'),
			])
			->add_tab('awards',[
 					'url'=>URI::url('!achievements/awards/index'),	
  					'title'=>I18N::T('achievements', '获奖'),
			])
			->add_tab('patents',[
 					'url'=>URI::url('!achievements/patents/index'),	
  					'title'=>I18N::T('achievements', '专利'),
			]);
		$this->add_css('achievements:aumap');
		
	}
}
?>
