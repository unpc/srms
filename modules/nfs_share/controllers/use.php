<?php

class Use_Controller extends Base_Controller {

	function index(){
		$this->layout->body->primary_tabs
			->select('index')
			->set('content', V('nfs_share:stat'));
			
	}

}
