<?php

class Project_Controller extends Base_Controller {

	function index($id=0, $tab="tasks") {

		$project = O('tn_project', $id);
		if (!$project->id) {
			URI::redirect('error/404');
		}

		if (!L('ME')->is_allowed_to('查看', $project)) {
			URI::redirect('error/401');
		}

		$content = V('project/index', [
						 'project' => $project,
						 ]);

		Event::bind('project.index.content', [$this, '_index_tasks'], 0, 'tasks');

		$content->secondary_tabs = Widget::factory('tabs')
			->set('project', $project)
			->tab_event('project.index.tab')
			->content_event('project.index.content');

		$content->secondary_tabs
			->add_tab('tasks', [
			  'url' => $project->url('tasks'),
			  'title' => I18N::T('treenote', '任务列表')
			]);

		$content->secondary_tabs->select($tab);

		$this->layout->body->primary_tabs
			->add_tab('project_view', [
						  'url'=>$project->url(),
						  'title'=>H($project->title),
						  ])
			->select('project_view')
			->set('content', $content);
	}

	function _index_tasks($e, $tabs) {
		$project = $tabs->project;
		$me = L('ME');

		$form = Lab::form(function(&$old_form, &$form) {
			unset($form['type']);
			if (isset($form['date_filter'])) {
				if (!$form['dtstart_check']) {
					unset($old_form['dtstart_check']);
				}
				if (!$form['dtend_check']) {
					unset($old_form['dtend_check']);
				}
				else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
				}
				unset($form['date_filter']);
			}
		});
		
        $opt = [];
        $opt['project_id'] = $project->id;
        $opt['parent_task_id'] = 0;
        /* filter */
        if($form['dtstart_check']){
            $opt['dstart'] = $form['dtstart'];
        }

        if($form['dtend_check']){
            $opt['dend'] = $form['dtend'];
        }

        if ($form['content']) {
            $opt['content'] = $form['content'];
        }

        if (isset($form['status']) && $form['status'] !== '') {
            $opt['status'] = (int)$form['status'];
        }

        if ($form['priority']) {
            $opt['priority'] = $form['priority'];
        }

        $opt['order_by'] = ' ORDER BY `deadline` ASC, `priority` ASC';

        $tasks = new Search_Tn_Task($opt);

		/* pagination */
		$start = (int) $form['st'];
		$per_page = 15;
		$pagination = Lab::pagination($tasks, $start, $per_page);
		
		$tabs->content = V('treenote:task/list', [
			'form' => $form,
			'pagination' => $pagination,
			'tasks' => $tasks,
			'project' => $project,
		]);

	}

	function add() {
		$me = L('ME');

		if (!$me->is_allowed_to('添加', 'tn_project')) {
			URI::redirect('error/401');
		}

		$project = O('tn_project');

		if (Input::form('submit')) {
			try {
				$form = Form::filter(Input::form());
				$form->validate('title', 'not_empty', I18N::T('treenote', '请填写项目名称'))
					->validate('user', 'not_empty', I18N::T('treenote', '请填写负责人'));

				if ($form->no_error) {
					$user = O('user', $form['user']);
					if (!$user->id) {
						$form->set_error('user', I18N::T('treenote', '请填写负责人'));
					}
				}

				if (!$form->no_error) {
					throw new Exception;
				}

				/* assignment */
				$project->title = $form['title'];
				$project->description = $form['description'];
				$project->user = $user;

				if ($project->save()) {
					/* 记录日志 */
                    Log::add(strtr('[treenote] %user_name[%user_id]添加了项目%project_title[%project_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%project_title'=> $project->title,
                        '%project_id'=> $project->id
                    ]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '项目添加成功！'));
					URI::redirect($project->url());
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '项目添加失败！'));
				}
			}
			catch ( Exception $e) {
			}
		}

		$this->layout->body->primary_tabs
			->add_tab('add', [
						  'url'=>$project->url(NULL, NULL, NULL, 'add'),
						  'title'=>I18N::T('treenote', '添加项目'),
						  ])
			->select('add')
			->set('content', V('project/form', [
								   'project' => $project,
								   'form'=>$form
								   ]));
	}

	function edit($id=0) {

		$project = O('tn_project', $id);

		if (!$project->id) {
			URI::redirect('error/404');
		}
		if (!(L('ME')->is_allowed_to('修改', $project) && $project->is_editable())) {
			URI::redirect('error/401');
		}

		if (Input::form('submit')) {
			try {
				$form = Form::filter(Input::form());
				$form->validate('title', 'not_empty', I18N::T('treenote', '请填写项目名称'))
					->validate('user', 'not_empty', I18N::T('treenote', '请填写负责人'));

				if ($form->no_error) {
					$user = O('user', $form['user']);
					if (!$user->id) {
						$form->set_error('user', I18N::T('treenote', '请填写负责人'));
					}
				}

				if (!$form->no_error) {
					throw new Exception;
				}

				/* assignment */
				$project->title = $form['title'];
				$project->description = $form['description'];
				$project->user = $user;

				if ($project->save()) {
					/* 记录日志 */
                    Log::add(strtr('[treenote] %user_name[%user_id]修改了项目%project_title[%project_id]的基本信息', [
                        '%user_name'=> L('ME')->name,
                        '%user_id'=> L('ME')->id,
                        '%project_title'=> $project->title,
                        '%project_id'=>  $project->id
                    ]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '项目修改成功！'));
					URI::redirect($project->url());
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '项目修改失败！'));
				}
			}
			catch ( Exception $e) {
			}
		}

		$tabs = $this->layout->body->primary_tabs
			->add_tab('project_edit', [
				'*' => [
					[
						'url'=>$project->url(),
						'title'=>H($project->title),
					],
					[
						'url' => $project->url(NULL, NULL, NULL, 'edit'),
						'title'=>I18N::T('treenote', '修改'),
					]
				]
			])
			->select('project_edit');

		$tabs->content = V('project/form', [
							   'project' => $project,
							   'form'=>$form
							   ]);
	}

	function delete($id=0) {
		$project = O('tn_project', $id);
		if (!$project->id) URI::redirect('error/404');

		$me = L('ME');
		if (!$me->is_allowed_to('删除', $project)) {
			URI::redirect('error/401');
		}

		// 删除相关附件
		$project->delete();

		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '该项目已成功删除!'));

		URI::redirect('!treenote/projects');
	}

}
