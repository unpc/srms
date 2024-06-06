<?php

class Work_Controller extends Base_Controller {

	function index($tab='') {
		$me = L('ME');
		if (!$me->is_allowed_to('列表', 'tn_task')) {
			URI::redirect('error/401');
		}

		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->select('work');


		Event::bind('treenote.tab.content[work]', 'Treenote::index_todo_content', 0, 'todo');
		Event::bind('treenote.tab.content[work]', 'Treenote::index_history_content', 0, 'history');

		$deadline = strtotime('tomorrow midnight', Date::time());
		$todo_count = Q("tn_task[is_complete=0][user=$me][deadline<$deadline]")->total_count();

		$stabs = Widget::factory('tabs');
		$stabs->class = 'secondary_tabs';
		$stabs->user = L('ME');

		$stabs
			->add_tab('todo', [
				'title'=>I18N::HT('treenote', 'To-Do'),
				'url'=> URI::url('!treenote/work/index.todo'),
				'number' => $todo_count,
			])
			->add_tab('history', [
				'title'=>I18N::HT('treenote', '历史'),
				'url'=> URI::url('!treenote/work/index.history'),
			])
			->content_event('treenote.tab.content[work]');

		$count = Q("tn_task[is_complete=0][reviewer=$me][user!=$me][status!=0|deadline<$deadline]")->total_count();
		Event::bind('treenote.tab.content[work]', 'Treenote::index_review_content', 0, 'review');
		$stabs->add_tab('review', [
			'url' => URI::url('!treenote/work/index.review'),
			'title' => I18N::HT('treenote', '评审'),
			'number' => $count,
			'weight' => 60,
		]);

		$stabs->select($tab);

		$primary_tabs->stabs = $stabs;
		$primary_tabs->content = V('treenote:work');

	}
}


