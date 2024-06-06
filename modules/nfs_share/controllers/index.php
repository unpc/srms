<?php

class Index_Controller extends Base_Controller {

	function index(){
		URI::redirect('!nfs_share/finder/user');
	}

}
