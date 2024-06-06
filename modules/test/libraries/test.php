<?php

class Test {
    static function is_accessible ($e, $name) {
        $me = L('ME');    
        if (!$me->is_allowed_to('执行', 'test')) {
                $e->return = false;
                return FALSE;
        }   
    }
    

    static function test_ACL($e, $user, $perm_name, $happyhour, $opt) {
		
		switch($perm_name) {
			case '执行':
				if ($user->access('执行脚本')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}
	
	
	static function pagination_array($array, $start, $per_page, $url=NULL) {
		$start = $start - ($start % $per_page);
		$total_count = count($array);
		$pagination = Widget::factory('pagination');
		$pagination->set([
			'start' => $start,
			'per_page' => $per_page,
			'total' => $total_count,
			'url' => $url
		]);
		
		return $pagination;
	}
	
	static function pagination_slice($array, $start, $per_page) {
		$start = $start - ($start % $per_page);
		$arr_slice = array_slice($array, $start , $per_page);
		return $arr_slice; 
	}
	
	
	static function path_fix($args) {
		$path = implode('.',$args);
		$path = str_replace('%F.', '/', $path);
		$path = str_replace('%F', '/', $path);	
		$path = rtrim($path,'/');
		return $path;
	}
}
