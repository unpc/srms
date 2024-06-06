<?php

class All_note_Controller extends Base_Controller {

	function index(){
		$me = L('ME');
		$content = V('note_list',['object'=>$me,'path_type'=>'attachments']);
		$this->layout->body->primary_tabs
			->select('All_note')
			->set('content', $content);
		
	}

}
