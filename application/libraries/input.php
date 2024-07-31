<?php

class Input extends _Input {

    static function url() {
        return self::$url;
    }

    static function setup(){

		$route = $_SERVER['PATH_INFO'];
		if(!$route) $route = $_SERVER['ORIG_PATH_INFO'];
		$route = preg_replace('/^[\/ ]*|[\/ ]*$|'.preg_quote(Config::get('system.url_suffix')).'$/iu','', $route);
		
		Input::$route = $route;
		$args = array();
		if(preg_match_all('/(.*?[^\\\])\//', $route.'/', $parts)){
			foreach($parts[1] as $part) {
				$args[] = strtr($part, array('\/'=>'/'));
			}
		}
		Input::$args = $args;
		Input::$get = $_GET;
		Input::$form = array_merge($_POST, $_GET);
		Input::$files = $_FILES;
		
		$query=$_GET;
		Input::$url = URI::url(Input::$route, $query);
		
		if($_POST['_ajax'] || $_GET['_ajax']){

			Input::$AJAX['widget']=$_POST['_widget'] ?? $_GET['_widget'];			
			Input::$AJAX['object']=$_POST['_object'] ?? $_GET['_object'];
			Input::$AJAX['event']=$_POST['_event'] ?? $_GET['_event'];
			Input::$AJAX['mouse']=$_POST['_mouse'] ?? $_GET['_mouse'];
			Input::$AJAX['view']=$_POST['_view'] ?? $_GET['_view'];

			unset(Input::$form['_ajax']);
			unset(Input::$form['_data']);
			unset(Input::$form['_widget']);
			unset(Input::$form['_object']);
			unset(Input::$form['_event']);
			unset(Input::$form['_mouse']);
			unset(Input::$form['_view']);
		
		}
		
	}
}
