<?php

class Projects_Controller extends Base_Controller {

	function index() {
		$form = Lab::form(function(&$old_form, &$form) {
				if (isset($form['sort'])) {
					if ($old_form['sort'] == $form['sort']) {
						$form['sort_asc'] = !$old_form['sort_asc'];
					}
					else {
						$form['sort_asc'] = TRUE;
					}
				}
			});

		$selector = 'tn_project';

		/* filter */
		if ($form['title']) {
			$title = Q::quote($form['title']);
			$selector .= "[title*={$title}]";
		}
        if ($form['user_name']) {
            $user_name = Q::quote($form['user_name']);
            $selector = "user[name*={$user_name}|name_abbr*={$user_name}]<user ". $selector;
        }

		/* sort */
		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $form['sort_asc'] ? 'A':'D';
		
		switch ($sort_by) {
		case 'title':
			$selector .= ":sort(title {$sort_flag})";
			break;
		default:
		}

		$projects = Q($selector);

		/* pagination */
		$start = (int) $form['st'];
		$per_page = 20;
		$start = $start - ($start % $per_page);

		if ($start > 0) {
			$last = floor($projects->total_count() / $per_page) * $per_page;
			if ($last == $projects->total_count()) {
				$last = max(0, $last - $per_page);
			}
			if ($start > $last) {
				$start = $last;
			}
			$projects = $projects->limit($start, $per_page);
		}
		else {
			$projects = $projects->limit($per_page);
		}

		$pagination = Widget::factory('pagination');
		$pagination->set([
							 'start' => $start,
							 'per_page' => $per_page,
							 'total' => $projects->total_count(),
							 ]);	

		$content = V('project/list');

		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->select('projects');
		$primary_tabs->content = $content;
		$this->layout->body->primary_tabs->
			select('projects')->
			set('content', V('project/list', [
								 'pagination' => $pagination,
								 'projects' => $projects,
								 'sort_by' => $sort_by,
								 'sort_asc' => $sort_asc,
								 'form' => $form,
								 ]));
		}
}
