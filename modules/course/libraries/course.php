<?php

class Course {
		
	static function course_ACL($e, $me, $perm, $course, $options) {
		switch($perm) {
			case '列表':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '添加':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '删除':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '修改':
				$e->return_value = TRUE;
				return FALSE;
		}		
	}    
}

