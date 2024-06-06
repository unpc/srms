<?php

class CLI_Treenote {
	static function sphinx_update() {
		//清空sphinx index
		Search_Tn_Task::empty_index();

		$tasks = Q('tn_task');

		$total = $tasks->total_count();

		$per_page = 20;
		$start = 0;

		//分页
		while ($start < $total) {
		    foreach($tasks->limit($start, $per_page + $start) as $task) {
		        //重建
		        Search_Tn_Task::update_index($task);
				ORM_Pool::release($task);
		        echo '.';
		    }
		    $start += $per_page;
		    echo "\n";
		}
	}
}