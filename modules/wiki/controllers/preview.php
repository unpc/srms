<?php

class Preview_Controller extends Controller {

	function index($path, $size=NULL){
		Wiki_Preview::show($path, $size);
	}
	
}
