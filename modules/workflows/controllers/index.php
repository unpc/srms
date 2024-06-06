<?php

class Index_Controller extends Base_Controller {

	function index() {


		$content = V('workflows:workflow/list');

		$this->layout->body->primary_tabs->select('index');
		$this->layout->body->primary_tabs->content = $content;
	}

}
